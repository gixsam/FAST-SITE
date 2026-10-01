<?php
// =========================================================================
// config.php  –  Database-Agnostic Connection Configuration
// Supports both high-performance Hostinger MySQL and Supabase PostgreSQL!
// =========================================================================

// --- Parse .env File ---
if (file_exists(__DIR__ . '/.env')) {
    $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// --- [OPTION A] MYSQL / MARIA DB CONFIGURATION ---
$db_driver = $_ENV['DB_DRIVER'] ?? 'mysql';
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'u422364295_db';
$user = $_ENV['DB_USER'] ?? 'u422364295_admin';
$pass = $_ENV['DB_PASS'] ?? '1590Sayamkhan@';
$port = $_ENV['DB_PORT'] ?? '3306';
$charset = 'utf8mb4';

// --- [OPTION B] SUPABASE POSTGRESQL CONFIGURATION ---
/*
$db_driver = 'pgsql';
$host = 'aws-0-ap-southeast-1.pooler.supabase.com'; // Your Supabase host pooler URL
$port = '5432';
$db   = 'postgres';                                 // Supabase db name
$user = 'postgres.your_username';                   // Supabase db user
$pass = 'YOUR_SUPABASE_PASSWORD';                   // Supabase db password
*/

// --- Dynamic DSN Construction ---
if ($db_driver === 'pgsql') {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
} else {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    if (file_exists(__DIR__ . '/fast_site_local.db')) {
        try {
            $pdo = new PDO('sqlite:' . __DIR__ . '/fast_site_local.db', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $pdo->exec("PRAGMA journal_mode = WAL;");
        } catch (PDOException $e2) {
            die("Database connection failed. Please check your DB credentials in config.php or .env file.<br><br>Error: " . $e->getMessage());
        }
    } else {
        die("Database connection failed. Please check your DB credentials in config.php or .env file.<br><br>Error: " . $e->getMessage());
    }
}

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if (!function_exists('resolveMediaUrl')) {
    /**
     * Converts any media path, relative URL, or localhost URL into a dynamic root-relative path (e.g. /uploads/...)
     * Seamlessly renders across localhost, Cloudflare tunnels, and Hostinger production without environmental configuration changes.
     */
    function resolveMediaUrl($path, $fallback = '') {
        if (empty($path)) {
            return $fallback;
        }
        $raw = trim($path);
        // Normalize backslashes (Windows -> POSIX)
        $raw = str_replace('\\', '/', $raw);
        // Strip localhost / 127.0.0.1 domain and port so local URLs never leak into production
        $raw = preg_replace('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', '', $raw);
        // If it is a real external remote URL (Unsplash, CDNs) or data URI, return as-is
        if (preg_match('#^https?://#i', $raw) || preg_match('#^data:image/#i', $raw)) {
            return $raw;
        }
        // Clean redundant directory traversal or duplicate slashes
        $clean = preg_replace('#^(\.\./)+#', '', $raw);
        $clean = preg_replace('#^(\./)+#', '', $clean);
        $clean = preg_replace('#/{2,}#', '/', $clean);
        return '/' . ltrim($clean, '/');
    }
}

if (!function_exists('resolveShopMedia')) {
    function resolveShopMedia($partner, $type = 'avatar') {
        if (empty($partner) || !is_array($partner)) {
            return $type === 'avatar' ? '/assets/images/logo.png' : '/assets/images/banner_placeholder.jpg';
        }

        $candidates = [];
        if ($type === 'avatar') {
            $candidates = [
                $partner['profile_pic'] ?? '',
                $partner['logo_url'] ?? '',
                $partner['shop_logo'] ?? ''
            ];
            $default = '/assets/images/logo.png';
        } else {
            $candidates = [
                $partner['cover_pic'] ?? '',
                $partner['banner_url'] ?? '',
                $partner['shop_banner'] ?? ''
            ];
            $default = '/assets/images/banner_placeholder.jpg';
        }

        foreach ($candidates as $cand) {
            if (!empty($cand)) {
                $cand = trim($cand);
                $cand = str_replace('\\', '/', $cand);
                // Strip localhost origin
                $cand = preg_replace('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', '', $cand);
                if (preg_match('#^https?://#i', $cand) || preg_match('#^data:image/#i', $cand)) {
                    return $cand;
                }
                // Strip redundant folder prefixes
                $clean = preg_replace('#^(\.\./)*(uploads/(partners|profiles|shops|branding)/?)+#', '', $cand);
                $clean = ltrim($clean, '/');

                // Check physical directories on disk
                $check_dirs = ['uploads/partners/', 'uploads/profiles/', 'uploads/shops/', 'uploads/branding/', 'uploads/'];
                foreach ($check_dirs as $dir) {
                    if (file_exists(__DIR__ . '/' . $dir . $clean) && is_file(__DIR__ . '/' . $dir . $clean)) {
                        return '/' . $dir . $clean;
                    }
                }
                return '/uploads/partners/' . $clean;
            }
        }
        return $default;
    }
}

if (!function_exists('resolveUserAvatar')) {
    function resolveUserAvatar($pic, $fallback = '/assets/images/default_avatar.png') {
        if (empty($pic)) return $fallback;
        $url = resolveMediaUrl($pic, $fallback);
        if (preg_match('#^https?://#i', $url)) return $url;
        $clean = ltrim($url, '/');
        // If bare filename without subfolder
        if (strpos($clean, '/') === false) {
            if (file_exists(__DIR__ . '/uploads/kyc/' . $clean) && is_file(__DIR__ . '/uploads/kyc/' . $clean)) {
                return '/uploads/kyc/' . $clean;
            }
            if (file_exists(__DIR__ . '/uploads/profiles/' . $clean) && is_file(__DIR__ . '/uploads/profiles/' . $clean)) {
                return '/uploads/profiles/' . $clean;
            }
            return '/uploads/kyc/' . $clean;
        }
        return '/' . $clean;
    }
}

// =========================================================================
// MIGRATION GUARD: Only run self-healing migrations ONCE per session
// to avoid executing SQL on every single page load.
// Force re-run anytime by visiting any admin page with ?force_migrate=1
// =========================================================================
$_migration_session_key = 'db_migrations_run_' . $driver;
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_force_migrate = (isset($_GET['force_migrate']) && $_GET['force_migrate'] == '1'
    && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if ($_force_migrate || !isset($_SESSION[$_migration_session_key])) {
    $_SESSION[$_migration_session_key] = true;
    if ($driver === 'sqlite') {
        // --- SQLite Self-Healing Migrations ---
        $pdo->exec("DROP TABLE IF EXISTS affiliate_partners");

        $pdo->exec("CREATE TABLE IF NOT EXISTS agent_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            task_type TEXT DEFAULT 'manual',
            reward_amount REAL DEFAULT 0.00,
            deadline TEXT DEFAULT NULL,
            max_per_agent INTEGER DEFAULT 1,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS agent_task_completions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            agent_id INTEGER NOT NULL,
            proof TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME DEFAULT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_id INTEGER NOT NULL,
            fee REAL DEFAULT 0.00,
            user_name TEXT NOT NULL,
            user_phone TEXT NOT NULL,
            user_email TEXT DEFAULT NULL,
            nid_number TEXT DEFAULT NULL,
            passport_number TEXT DEFAULT NULL,
            driving_license TEXT DEFAULT NULL,
            details TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            sender TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL UNIQUE,
            application_id INTEGER DEFAULT NULL,
            user_name TEXT DEFAULT NULL,
            user_phone TEXT DEFAULT NULL,
            service_identified TEXT DEFAULT NULL,
            language TEXT DEFAULT 'en',
            status TEXT DEFAULT 'chatbot',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT DEFAULT NULL
        )");

        // Seed settings
        $count = $pdo->query("SELECT COUNT(*) FROM homepage_settings")->fetchColumn();
        if ($count == 0) {
            $seeds = [
                'promo_title' => '🔥 Hot Deal',
                'promo_subtitle_1' => 'MELBAT',
                'promo_subtitle_2' => 'APK',
                'promo_url' => 'https://omg10.com/4/10744356',
                'promo_image' => 'https://fastsitee.wordpress.com/wp-content/uploads/2026/02/att.lhaeh6rlmszydbv5r8aj5rxosjlq2txh6jdeqd_dmcq.png.jpeg',
                'promo_code_text' => '🔥 Use promo code <code>ml_2165959</code> to get up to <strong>12,000 BDT</strong> welcome bonus on first deposit.',
                'logo_url' => '',
                'site_name' => 'FAST SITE',
                'whatsapp_number' => '01963601472',
                'user_tnc' => 'Terms and Conditions will be published here.',
                'agent_tnc' => 'Affiliate Agent Terms and Conditions will be published here.',
                'min_payout' => '200',
                'default_commission_pct' => '20',
                'business_hours' => 'Saturday – Thursday, 9am – 6pm',
                'global_notice' => 'Welcome to Fast Site! Exciting new offers available.',
                'nav_positioning' => 'dashboard,service,partners,all_user,payouts,view_option,settings',
                'affi_bangla_url' => 'http://affibangla.best-travel.ltd',
                'cashback_pct' => '2',
                'gemini_api_key' => getenv('GEMINI_API_KEY') ?: ''
            ];
            $stmt = $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($seeds as $k => $v) {
                $stmt->execute([$k, $v]);
            }
        }

        // Self-healing insert for affi_bangla_url if missing
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'affi_bangla_url'");
            $chk->execute();
            if ($chk->fetchColumn() == 0) {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('affi_bangla_url', 'http://affibangla.best-travel.ltd')")->execute();
            }
        } catch (Exception $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            fee REAL DEFAULT 0.00,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            section_name TEXT DEFAULT 'অন�যান�য সেবা',
            sort_order INTEGER DEFAULT 0,
            logo_url TEXT DEFAULT NULL,
            govt_fee REAL DEFAULT 0.00,
            processing_fee REAL DEFAULT 0.00,
            product_price REAL DEFAULT 0.00,
            referral_link TEXT DEFAULT NULL,
            referral_clicks INTEGER DEFAULT 0,
            affiliate_bonus_pct REAL DEFAULT 20.00,
            show_on_homepage INTEGER DEFAULT 1,
            action_text TEXT DEFAULT 'ORDER NOW',
            website_link TEXT DEFAULT NULL
        )");

        // Seed services
        $count = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
        if ($count == 0) {
            $services = [
                [1, 'NID Correction', 'Correction of info on National ID.', 500.00, 'জাতীয় পরিচয়পত�র সেবা', 20],
                [2, 'NID Apply', 'New National ID card application.', 1800.00, 'জাতীয় পরিচয়পত�র সেবা', 10],
                [3, 'Driving License Correction', 'Correction on Driving License.', 1500.00, 'ড�রাইভিং লাইসেন�স সেবা', 20],
                [4, 'Driving License Apply/Renewal', 'New or renewal of Driving License.', 1450.00, 'ড�রাইভিং লাইসেন�স সেবা', 10],
                [5, 'Passport Correction', 'Correction of info on Passport.', 700.00, 'পাসপোর�ট সেবা', 20],
                [6, 'Passport Apply/Renewal', 'New application or renewal of Passport.', 800.00, 'পাসপোর�ট সেবা', 10],
                [7, 'Other Government Service', 'Any other government-related assistance.', 0.00, 'অন�যান�য সেবা', 10],
                [8, 'WEBSITE BUILD UP', '8% off', 2430.00, 'অন�যান�য সেবা', 0],
                [9, 'Birth Certificate Apply', 'REQUIRED FILES', 1530.00, '🪪 জাতীয় পরিচয়পত�র সেবা', 10]
            ];
            $stmt = $pdo->prepare("INSERT INTO services (id, name, description, fee, section_name, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($services as $s) {
                $stmt->execute($s);
            }
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT DEFAULT NULL,
            role TEXT DEFAULT 'staff',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            permissions TEXT DEFAULT NULL
        )");

        // Seed staff
        $count = $pdo->query("SELECT COUNT(*) FROM staff_users")->fetchColumn();
        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT INTO staff_users (id, username, password_hash, role, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([1, 'admin', '$2y$10$jrImLWq448yKCH0MxLAqae8WQCC2Ih7XhKl3QuyVgUXJukyHHp2uS', 'admin', 'zillionprince6@gmail.com']);
            $stmt->execute([2, 'MIM', '$2y$10$eJXmPvx1nmJOdUGRVrPj.eC5rVnU6Jhhf0ho7S8oucS1UhKHZ8BkS', 'staff', null]);
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL UNIQUE,
            email TEXT DEFAULT NULL,
            profile_pic TEXT DEFAULT NULL,
            password_hash TEXT NOT NULL,
            ref_code TEXT NOT NULL UNIQUE,
            missed_commissions REAL DEFAULT 0.00,
            coins_balance REAL DEFAULT 0.00,
            role TEXT DEFAULT 'user',
            is_active INTEGER DEFAULT 1,
            address TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Self-healing columns for users table
        try { $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN coins_balance REAL DEFAULT 0.00"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN missed_commissions REAL DEFAULT 0.00"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN dob DATE DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN facebook VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN instagram VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN twitter VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN youtube VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN district VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN extra_details TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN nid VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN etin VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN passport VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN driving_license VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN whatsapp VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
        
        // Registration & Referral columns (Phase 40/41)
        try { $pdo->exec("ALTER TABLE users ADD COLUMN nid_number TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN registration_number TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN ref_by TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN allow_extra_shop INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN kyc_status TEXT DEFAULT 'pending'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN nid_front_photo TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN nid_back_photo TEXT DEFAULT NULL"); } catch (Exception $e) {}

        // Gamification (Phase 3)
        try { $pdo->exec("ALTER TABLE users ADD COLUMN current_streak INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN longest_streak INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN last_login_date DATE DEFAULT NULL"); } catch (Exception $e) {}

        // Partner products image column (Phase 41)
        try { $pdo->exec("ALTER TABLE partner_products ADD COLUMN image TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partner_products ADD COLUMN thumbnail TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partner_products ADD COLUMN meta_keywords TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partner_products ADD COLUMN weight REAL DEFAULT 0"); } catch (Exception $e) {}

        // Partners table columns
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN user_id INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN registration_number TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN is_official INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN is_hidden INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN seller_level INTEGER DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN tags TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN shop_slug TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN shop_name TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN banner_pic TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN social_instagram TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN social_facebook TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN social_website TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN return_policy TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN shipping_policy TEXT DEFAULT NULL"); } catch (Exception $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_withdrawals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount_coins REAL NOT NULL,
            payout_method TEXT NOT NULL,
            payout_account TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME DEFAULT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_pending_cash (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount REAL NOT NULL DEFAULT 0.00,
            source_order TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS agents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL UNIQUE,
            email TEXT DEFAULT NULL,
            ref_code TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            total_earned REAL DEFAULT 0.00,
            total_withdrawn REAL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS agent_payouts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agent_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            bkash_number TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            admin_note TEXT DEFAULT NULL,
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME DEFAULT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS agent_commissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agent_id INTEGER NOT NULL,
            application_id INTEGER DEFAULT 0,
            order_ref TEXT DEFAULT NULL,
            service_name TEXT DEFAULT NULL,
            order_fee REAL DEFAULT 0.00,
            commission_amount REAL NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS trust_directory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            url TEXT DEFAULT NULL,
            category TEXT NOT NULL,
            safety_rating TEXT DEFAULT 'caution',
            admin_review TEXT DEFAULT NULL,
            redirection_link TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            staff_user_id INTEGER DEFAULT NULL,
            title TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS internal_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER DEFAULT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // --- Partner Shop SQLite Self-Healing Migrations ---
        $pdo->exec("CREATE TABLE IF NOT EXISTS partners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            business_name TEXT NOT NULL,
            owner_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            nid TEXT DEFAULT NULL,
            trade_license TEXT DEFAULT NULL,
            profile_pic TEXT DEFAULT NULL,
            cover_pic TEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            payout_method TEXT DEFAULT 'bkash',
            payout_account TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            rating REAL DEFAULT 0.0,
            total_orders INTEGER DEFAULT 0,
            total_earned REAL DEFAULT 0.0,
            is_official INTEGER DEFAULT 0,
            tags TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Add column if missing
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN tags TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN seller_level INTEGER DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE partners ADD COLUMN user_id INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("UPDATE homepage_settings SET setting_value = 'FAST SITE' WHERE setting_key = 'site_name' AND setting_value LIKE '%Fast Sitee%'"); } catch (Exception $e) {}
        try { $pdo->exec("UPDATE homepage_settings SET setting_value = 'Welcome to Fast Site! Exciting new offers available.' WHERE setting_key = 'global_notice' AND setting_value LIKE '%Fast Sitee%'"); } catch (Exception $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            icon TEXT DEFAULT NULL,
            color TEXT DEFAULT '#ffffff',
            bg_color TEXT DEFAULT '#000000',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Seed default tags
        $tagCount = $pdo->query("SELECT COUNT(*) FROM shop_tags")->fetchColumn();
        if ($tagCount == 0) {
            $pdo->exec("INSERT INTO shop_tags (name, icon, color, bg_color) VALUES ('OFFICIAL', '⚡', '#fcb900', 'rgba(252, 185, 0, 0.15)')");
            $pdo->exec("INSERT INTO shop_tags (name, icon, color, bg_color) VALUES ('VERIFIED', '✅', '#00e676', 'rgba(0, 230, 118, 0.15)')");
            $pdo->exec("INSERT INTO shop_tags (name, icon, color, bg_color) VALUES ('PARTNERS', '�', '#3b82f6', 'rgba(59, 130, 246, 0.15)')");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            price REAL NOT NULL,
            category TEXT DEFAULT NULL,
            scheduled_at DATETIME DEFAULT NULL,
            is_published INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_product_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            image_url TEXT NOT NULL,
            is_thumbnail INTEGER DEFAULT 0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            partner_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            total_coins REAL NOT NULL,
            payment_method TEXT DEFAULT 'coins',
            payment_status TEXT DEFAULT 'completed',
            sender_number TEXT DEFAULT NULL,
            transaction_id TEXT DEFAULT NULL,
            gateway_ref TEXT DEFAULT NULL,
            delivery_location TEXT DEFAULT NULL,
            delivery_charge REAL DEFAULT 0.0,
            shipping_address TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            partner_proof TEXT DEFAULT NULL,
            customer_confirmed_at DATETIME DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS coin_wallets (
            user_id INTEGER PRIMARY KEY,
            balance REAL DEFAULT 0.0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS coin_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            amount REAL NOT NULL,
            reference TEXT DEFAULT NULL,
            status TEXT DEFAULT 'completed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS deposit_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            sender_number TEXT NOT NULL,
            transaction_id TEXT NOT NULL,
            amount REAL NOT NULL,
            screenshot_url TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_withdrawal_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            account_info TEXT DEFAULT NULL,
            status TEXT DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_disputes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            raised_by TEXT NOT NULL,
            reason TEXT DEFAULT NULL,
            evidence_customer TEXT DEFAULT NULL,
            evidence_partner TEXT DEFAULT NULL,
            admin_decision TEXT DEFAULT 'pending',
            resolved_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            customer_id INTEGER NOT NULL,
            partner_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            review TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_wishlist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(customer_id, product_id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS partner_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS api_usage_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            api_partner_id INTEGER NOT NULL,
            endpoint TEXT DEFAULT NULL,
            request_data TEXT DEFAULT NULL,
            response_data TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS api_invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            api_partner_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            due_date TEXT DEFAULT NULL,
            status TEXT DEFAULT 'unpaid',
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            type TEXT DEFAULT 'system',
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        try { $pdo->exec("ALTER TABLE user_notifications ADD COLUMN type TEXT DEFAULT 'system'"); } catch (Exception $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS return_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            customer_id INTEGER NOT NULL,
            partner_id INTEGER NOT NULL,
            reason TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_recently_viewed (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, product_id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS marketplace_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            partner_id INTEGER NOT NULL,
            order_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS marketplace_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conversation_id INTEGER NOT NULL,
            sender_type TEXT NOT NULL, 
            sender_id INTEGER NOT NULL,
            message TEXT,
            file_path TEXT DEFAULT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_id INTEGER NOT NULL,
            code TEXT NOT NULL UNIQUE,
            discount_amount REAL NOT NULL,
            is_percentage INTEGER DEFAULT 0,
            max_uses INTEGER DEFAULT 0,
            current_uses INTEGER DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Phase 3 Job Board Tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            budget REAL NOT NULL,
            status TEXT DEFAULT 'open',
            awarded_to INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_missions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            mission_key TEXT NOT NULL,
            progress REAL NOT NULL DEFAULT 0,
            is_completed INTEGER DEFAULT 0,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, mission_key)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_id INTEGER NOT NULL,
            code TEXT NOT NULL,
            discount_amount REAL NOT NULL DEFAULT 0.00,
            is_percentage INTEGER DEFAULT 1,
            discount_type TEXT DEFAULT 'percentage',
            discount_value REAL DEFAULT 0.00,
            min_purchase REAL DEFAULT 0.00,
            max_uses INTEGER DEFAULT 100,
            current_uses INTEGER DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            expiry_date DATETIME DEFAULT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS job_bids (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER NOT NULL,
            partner_id INTEGER NOT NULL,
            bid_amount REAL NOT NULL,
            proposal TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // Seed partner settings if count is 0
        $partnerSettingsCount = (int)$pdo->query("SELECT COUNT(*) FROM partner_settings")->fetchColumn();
        if ($partnerSettingsCount === 0) {
            $seeds = [
                'coin_name' => 'Fast Points',
                'exchange_rate' => '1',
                'min_withdrawal' => '500',
                'auto_respond_days' => '2',
                'auto_complete_days' => '7',
                'cooling_off_hours' => '24'
            ];
            $stmt = $pdo->prepare("INSERT INTO partner_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($seeds as $k => $v) {
                $stmt->execute([$k, $v]);
            }
        }

        // Add dynamic columns to SQLite tables if missing
        $dyn_cols = ['whatsapp', 'facebook', 'instagram', 'twitter', 'youtube', 'email', 'profile_pic', 'dob', 'gender', 'nid', 'etin', 'passport', 'driving_license', 'extra_details'];
        $dyn_tables = ['users', 'agents', 'staff_users'];
        foreach ($dyn_tables as $tbl) {
            foreach ($dyn_cols as $col) {
                try {
                    @$pdo->exec("ALTER TABLE $tbl ADD COLUMN $col TEXT DEFAULT NULL");
                } catch (Exception $ex) {}
            }
        }

        // Add dynamic columns to partner_orders table if missing
        $dyn_cols_orders = ['payment_method', 'payment_status', 'sender_number', 'transaction_id', 'gateway_ref', 'delivery_location', 'delivery_charge', 'shipping_address', 'order_group_id', 'customer_name', 'customer_phone', 'order_items'];
        foreach ($dyn_cols_orders as $col) {
            try {
                @$pdo->exec("ALTER TABLE partner_orders ADD COLUMN $col TEXT DEFAULT NULL");
            } catch (Exception $ex) {}
        }

        try {
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN listing_type TEXT DEFAULT 'product'");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN stock INTEGER DEFAULT -1");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN shipping_type TEXT DEFAULT 'digital'");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN estimated_time TEXT DEFAULT ''");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN required_docs TEXT DEFAULT ''");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN affiliate_url TEXT DEFAULT ''");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN affiliate_action TEXT DEFAULT ''");
            @$pdo->exec("ALTER TABLE partner_products ADD COLUMN is_trending INTEGER DEFAULT 0");
        } catch (Exception $ex) {}

        try {
            @$pdo->exec("ALTER TABLE partners ADD COLUMN is_official INTEGER DEFAULT 0");
        } catch (Exception $ex) {}

        // Add action_text column to services table if missing (SQLite)
        try {
            @$pdo->exec("ALTER TABLE services ADD COLUMN action_text TEXT DEFAULT 'ORDER NOW'");
        } catch (Exception $ex) {}
        try {
            @$pdo->exec("ALTER TABLE services ADD COLUMN product_price REAL DEFAULT 0.00");
        } catch (Exception $ex) {}
        try {
            @$pdo->exec("ALTER TABLE services ADD COLUMN referral_link TEXT DEFAULT NULL");
        } catch (Exception $ex) {}
        try {
            @$pdo->exec("ALTER TABLE services ADD COLUMN referral_clicks INTEGER DEFAULT 0");
        } catch (Exception $ex) {}
        try {
            @$pdo->exec("ALTER TABLE services ADD COLUMN website_link TEXT DEFAULT NULL");
        } catch (Exception $ex) {}

    } else {
        // --- Original MySQL / PostgreSQL Self-Healing Migrations ---
        // Create core and partner tables for MySQL if they don't exist
        if ($driver !== 'pgsql') {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_requests` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `status` VARCHAR(50) DEFAULT 'pending',
                    `admin_notes` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `phone` VARCHAR(20) NOT NULL UNIQUE,
                    `email` VARCHAR(100) DEFAULT NULL,
                    `profile_pic` VARCHAR(255) DEFAULT NULL,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `ref_code` VARCHAR(20) NOT NULL UNIQUE,
                    `coins_balance` DECIMAL(10,2) DEFAULT 0.00,
                    `role` VARCHAR(20) DEFAULT 'user',
                    `is_active` TINYINT(1) DEFAULT 1,
                    `address` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            // Self-healing columns for MySQL users table
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `profile_pic` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `address` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `coins_balance` DECIMAL(10,2) DEFAULT 0.00"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) DEFAULT 'user'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `dob` DATE DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `gender` VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `facebook` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `instagram` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `twitter` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `youtube` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `whatsapp` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `district` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `extra_details` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `nid` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `etin` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `passport` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `driving_license` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `nid_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `registration_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `ref_by` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `missed_commissions` DECIMAL(10,2) DEFAULT 0.00"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `current_streak` INT DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `longest_streak` INT DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `last_login_date` DATE DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `allow_extra_shop` TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `kyc_status` VARCHAR(50) DEFAULT 'pending'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `nid_front_photo` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `nid_back_photo` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

            // Partner products image column (Phase 41)
            try { $pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `image` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `thumbnail` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `meta_keywords` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `weight` DECIMAL(10,2) DEFAULT 0"); } catch (Exception $e) {}

            // Partners table columns
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `user_id` INT DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `registration_number` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `is_official` TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `is_hidden` TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `seller_level` INT DEFAULT 1"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `tags` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `shop_slug` VARCHAR(150) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `shop_name` VARCHAR(150) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `banner_pic` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `social_instagram` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `social_facebook` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `social_website` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `return_policy` TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE `partners` ADD COLUMN `shipping_policy` TEXT DEFAULT NULL"); } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `services` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `fee` DECIMAL(10,2) DEFAULT 0.00,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `section_name` VARCHAR(100) DEFAULT 'অন্যান্য সেবা',
                    `sort_order` INT DEFAULT 0,
                    `logo_url` VARCHAR(500) DEFAULT NULL,
                    `govt_fee` DECIMAL(10,2) DEFAULT 0.00,
                    `processing_fee` DECIMAL(10,2) DEFAULT 0.00,
                    `affiliate_bonus_pct` DECIMAL(5,2) DEFAULT 20.00,
                    `show_on_homepage` TINYINT(1) DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `homepage_settings` (
                    `setting_key` VARCHAR(50) PRIMARY KEY,
                    `setting_value` TEXT DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `api_partners` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `api_key` VARCHAR(100) NOT NULL UNIQUE,
                    `webhook_url` VARCHAR(500) DEFAULT NULL,
                    `status` ENUM('active','inactive') DEFAULT 'active',
                    `total_orders` INT DEFAULT 0,
                    `subscription_type` VARCHAR(50) DEFAULT NULL,
                    `subscription_expires_at` DATETIME DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `dropship_connections` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `api_partner_id` INT NOT NULL,
                    `local_product_id` INT NOT NULL,
                    `external_product_id` VARCHAR(100) NOT NULL,
                    `sync_status` ENUM('synced', 'failed') DEFAULT 'synced',
                    `last_sync` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`api_partner_id`) REFERENCES `api_partners`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partners` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `business_name` VARCHAR(255) NOT NULL,
                    `owner_name` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(255) UNIQUE NOT NULL,
                    `phone` VARCHAR(50) NOT NULL,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `nid` VARCHAR(100),
                    `trade_license` VARCHAR(255),
                    `profile_pic` VARCHAR(255),
                    `cover_pic` VARCHAR(255),
                    `description` TEXT,
                    `payout_method` ENUM('bkash', 'nagad') DEFAULT 'bkash',
                    `payout_account` VARCHAR(100),
                    `status` ENUM('pending', 'approved', 'suspended') DEFAULT 'pending',
                    `rating` DECIMAL(2,1) DEFAULT 0,
                    `total_orders` INT DEFAULT 0,
                    `total_earned` DECIMAL(10,2) DEFAULT 0,
                    `is_official` TINYINT(1) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `is_official` TINYINT(1) DEFAULT 0");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_products` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `partner_id` INT NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `description` TEXT,
                    `price` DECIMAL(10,2) NOT NULL,
                    `category` VARCHAR(100),
                    `scheduled_at` DATETIME,
                    `is_published` BOOLEAN DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `is_trending` TINYINT(1) DEFAULT 0");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_product_images` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `product_id` INT NOT NULL,
                    `image_url` VARCHAR(500) NOT NULL,
                    `is_thumbnail` BOOLEAN DEFAULT 0,
                    FOREIGN KEY (`product_id`) REFERENCES `partner_products`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_orders` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `customer_id` INT NOT NULL,
                    `partner_id` INT NOT NULL,
                    `product_id` INT NOT NULL,
                    `total_coins` DECIMAL(10,2) NOT NULL,
                    `status` ENUM('pending', 'accepted', 'in_progress', 'waiting_confirmation', 'completed', 'cancelled', 'disputed') DEFAULT 'pending',
                    `partner_proof` VARCHAR(500),
                    `customer_confirmed_at` DATETIME,
                    `cancelled_at` DATETIME,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
                    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`),
                    FOREIGN KEY (`product_id`) REFERENCES `partner_products`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `coin_wallets` (
                    `user_id` INT PRIMARY KEY,
                    `balance` DECIMAL(10,2) DEFAULT 0,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `coin_transactions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `type` ENUM('deposit', 'hold', 'release', 'refund', 'withdrawal') NOT NULL,
                    `amount` DECIMAL(10,2) NOT NULL,
                    `reference` VARCHAR(255),
                    `status` ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `deposit_requests` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `sender_number` VARCHAR(50) NOT NULL,
                    `transaction_id` VARCHAR(100) NOT NULL,
                    `amount` DECIMAL(10,2) NOT NULL,
                    `screenshot_url` VARCHAR(500),
                    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    `admin_notes` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_withdrawal_requests` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `partner_id` INT NOT NULL,
                    `amount` DECIMAL(10,2) NOT NULL,
                    `account_info` TEXT,
                    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    `admin_notes` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_disputes` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` INT NOT NULL,
                    `raised_by` ENUM('customer', 'partner') NOT NULL,
                    `reason` TEXT,
                    `evidence_customer` VARCHAR(500),
                    `evidence_partner` VARCHAR(500),
                    `status` ENUM('open', 'resolved_refunded', 'resolved_released', 'closed') DEFAULT 'open',
                    `admin_notes` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`order_id`) REFERENCES `partner_orders`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_ratings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` INT NOT NULL,
                    `customer_id` INT NOT NULL,
                    `partner_id` INT NOT NULL,
                    `rating` INT NOT NULL,
                    `review` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`order_id`) REFERENCES `partner_orders`(`id`),
                    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
                    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_wishlist` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `customer_id` INT NOT NULL,
                    `product_id` INT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`product_id`) REFERENCES `partner_products`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `partner_settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
                    `setting_value` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}
            
            // Seed partner settings if count is 0
            try {
                $partnerSettingsCount = (int)$pdo->query("SELECT COUNT(*) FROM `partner_settings`")->fetchColumn();
                if ($partnerSettingsCount === 0) {
                    $seeds = [
                        'coin_name' => 'Fast Points',
                        'exchange_rate' => '1',
                        'min_withdrawal' => '500',
                        'auto_respond_days' => '2',
                        'auto_complete_days' => '7',
                        'cooling_off_hours' => '24'
                    ];
                    $stmt = $pdo->prepare("INSERT INTO `partner_settings` (setting_key, setting_value) VALUES (?, ?)");
                    foreach ($seeds as $k => $v) {
                        $stmt->execute([$k, $v]);
                    }
                }
            } catch (Exception $e) {}
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `agents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) NOT NULL UNIQUE,
            `email` VARCHAR(100) DEFAULT NULL,
            `ref_code` VARCHAR(20) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `status` ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
            `total_earned` DECIMAL(10,2) DEFAULT 0.00,
            `total_withdrawn` DECIMAL(10,2) DEFAULT 0.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `agent_payouts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id` INT NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `bkash_number` VARCHAR(50) NOT NULL,
            `status` ENUM('pending', 'paid', 'rejected') DEFAULT 'pending',
            `admin_note` TEXT DEFAULT NULL,
            `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `paid_at` TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `agent_commissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id` INT NOT NULL,
            `application_id` INT DEFAULT 0,
            `order_ref` VARCHAR(50) DEFAULT NULL,
            `service_name` VARCHAR(255) DEFAULT NULL,
            `order_fee` DECIMAL(10,2) DEFAULT 0.00,
            `commission_amount` DECIMAL(10,2) NOT NULL,
            `status` ENUM('pending', 'paid') DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if ($driver === 'pgsql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS trust_directory (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                url VARCHAR(500) DEFAULT NULL,
                category VARCHAR(100) NOT NULL,
                safety_rating VARCHAR(20) DEFAULT 'caution',
                admin_review TEXT DEFAULT NULL,
                redirection_link VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `trust_directory` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `url` VARCHAR(500) DEFAULT NULL,
                `category` VARCHAR(100) NOT NULL,
                `safety_rating` ENUM('verified', 'caution', 'scam') DEFAULT 'caution',
                `admin_review` TEXT DEFAULT NULL,
                `redirection_link` VARCHAR(500) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Add social platforms and profile columns
        $dyn_cols = ['whatsapp', 'facebook', 'instagram', 'twitter', 'youtube', 'email', 'profile_pic', 'dob', 'gender', 'nid', 'etin', 'passport', 'driving_license', 'extra_details'];
        $dyn_tables = ['users', 'agents', 'staff_users'];
        foreach ($dyn_tables as $tbl) {
            foreach ($dyn_cols as $col) {
                try {
                    if ($driver === 'pgsql') {
                        @$pdo->exec("ALTER TABLE $tbl ADD COLUMN $col VARCHAR(255) DEFAULT NULL");
                    } else {
                        @$pdo->exec("ALTER TABLE `$tbl` ADD COLUMN `$col` VARCHAR(255) DEFAULT NULL");
                    }
                } catch (Exception $ex) {
                    // Ignore if column already exists
                }
            }
        }

        // Self-healing columns for users table (KYC, streak, registration, NID)
        $dyn_user_cols = [
            'kyc_status' => "VARCHAR(50) DEFAULT 'pending'",
            'nid_number' => "VARCHAR(100) DEFAULT NULL",
            'registration_number' => "VARCHAR(100) DEFAULT NULL",
            'current_streak' => "INT DEFAULT 0",
            'day_streak' => "INT DEFAULT 0",
            'longest_streak' => "INT DEFAULT 0",
            'last_login_date' => "DATE DEFAULT NULL",
            'allow_extra_shop' => "TINYINT(1) DEFAULT 0",
            'district' => "VARCHAR(100) DEFAULT NULL",
            'nid_front_photo' => "VARCHAR(255) DEFAULT NULL",
            'nid_back_photo' => "VARCHAR(255) DEFAULT NULL",
            'is_active' => "TINYINT(1) DEFAULT 1",
            'status' => "VARCHAR(50) DEFAULT 'active'"
        ];
        foreach ($dyn_user_cols as $col => $definition) {
            try {
                @$pdo->exec("ALTER TABLE `users` ADD COLUMN `$col` $definition");
            } catch (Exception $e) {}
            try {
                @$pdo->exec("ALTER TABLE users ADD COLUMN $col $definition");
            } catch (Exception $e) {}
        }

        // Self-healing columns for user_notifications
        try { @$pdo->exec("ALTER TABLE `user_notifications` ADD COLUMN `type` VARCHAR(50) DEFAULT 'system'"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE user_notifications ADD COLUMN type TEXT DEFAULT 'system'"); } catch (Exception $e) {}

        // Self-healing columns for partners table
        $dyn_partner_cols = [
            'user_id'             => "INT DEFAULT 0",
            'registration_number' => "VARCHAR(100) DEFAULT NULL",
            'status'              => "VARCHAR(50) DEFAULT 'pending'",
            'is_official'         => "TINYINT(1) DEFAULT 0",
            'is_hidden'           => "TINYINT(1) DEFAULT 0",
            'seller_level'        => "INT DEFAULT 1",
            'tags'                => "TEXT DEFAULT NULL",
            'shop_slug'           => "VARCHAR(150) DEFAULT NULL",
            'shop_name'           => "VARCHAR(150) DEFAULT NULL",
            'banner_pic'          => "VARCHAR(255) DEFAULT NULL",
            'profile_pic'         => "VARCHAR(255) DEFAULT NULL",
            'cover_pic'           => "VARCHAR(255) DEFAULT NULL",
            'rating'              => "DECIMAL(2,1) DEFAULT 5.0",
            'total_orders'        => "INT DEFAULT 0",
            'total_earned'        => "DECIMAL(10,2) DEFAULT 0.00"
        ];
        foreach ($dyn_partner_cols as $col => $def) {
            try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `$col` $def"); } catch (Exception $ex) {}
            try { @$pdo->exec("ALTER TABLE partners ADD COLUMN $col $def"); } catch (Exception $ex) {}
        }

        // Add dynamic columns to partner_orders table if missing
        $dyn_cols_orders = [
            'payment_method'      => "VARCHAR(255) DEFAULT NULL",
            'payment_status'      => "VARCHAR(255) DEFAULT NULL",
            'sender_number'       => "VARCHAR(255) DEFAULT NULL",
            'transaction_id'      => "VARCHAR(255) DEFAULT NULL",
            'gateway_ref'         => "VARCHAR(255) DEFAULT NULL",
            'delivery_location'   => "VARCHAR(255) DEFAULT NULL",
            'delivery_charge'     => "VARCHAR(255) DEFAULT NULL",
            'shipping_address'    => "VARCHAR(255) DEFAULT NULL",
            'customer_submission' => "TEXT DEFAULT NULL",
            'submission_files'    => "TEXT DEFAULT NULL",
            'customer_notes'      => "TEXT DEFAULT NULL",
            'order_group_id'      => "VARCHAR(100) DEFAULT NULL",
            'customer_name'       => "VARCHAR(255) DEFAULT NULL",
            'customer_phone'      => "VARCHAR(50) DEFAULT NULL",
            'order_items'         => "TEXT DEFAULT NULL"
        ];
        foreach ($dyn_cols_orders as $col => $def) {
            try { @$pdo->exec("ALTER TABLE `partner_orders` ADD COLUMN `$col` $def"); } catch (Exception $ex) {}
            try { @$pdo->exec("ALTER TABLE partner_orders ADD COLUMN $col $def"); } catch (Exception $ex) {}
        }

        $dyn_cols_prods = [
            'listing_type'        => "VARCHAR(50) DEFAULT 'product'",
            'stock'               => "INT DEFAULT -1",
            'shipping_type'       => "VARCHAR(50) DEFAULT 'digital'",
            'estimated_time'      => "VARCHAR(255) DEFAULT ''",
            'required_docs'       => "TEXT DEFAULT ''",
            'affiliate_url'       => "TEXT DEFAULT ''",
            'affiliate_action'    => "TEXT DEFAULT ''",
            'redirect_url'        => "TEXT DEFAULT NULL",
            'original_website'    => "TEXT DEFAULT NULL",
            'audio_file'          => "VARCHAR(255) DEFAULT NULL",
            'require_submission'  => "TINYINT(1) DEFAULT 0",
            'submission_type'     => "VARCHAR(50) DEFAULT 'text_and_files'",
            'submission_required' => "TINYINT(1) DEFAULT 1",
            'submission_prompt'   => "TEXT DEFAULT NULL"
        ];
        foreach ($dyn_cols_prods as $col => $def) {
            try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `$col` $def"); } catch (Exception $ex) {}
            try { @$pdo->exec("ALTER TABLE partner_products ADD COLUMN $col $def"); } catch (Exception $ex) {}
        }

        // Create shop_coupons table if not exists (MySQL / PostgreSQL / SQLite)
        try {
            if ($driver === 'pgsql') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS shop_coupons (
                    id SERIAL PRIMARY KEY,
                    partner_id INT NOT NULL,
                    code VARCHAR(50) NOT NULL UNIQUE,
                    discount_amount DECIMAL(10,2) NOT NULL,
                    is_percentage INT DEFAULT 0,
                    max_uses INT DEFAULT 0,
                    current_uses INT DEFAULT 0,
                    expires_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `shop_coupons` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `partner_id` INT NOT NULL,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `discount_amount` DECIMAL(10,2) NOT NULL,
                    `is_percentage` TINYINT(1) DEFAULT 0,
                    `max_uses` INT DEFAULT 0,
                    `current_uses` INT DEFAULT 0,
                    `expires_at` DATETIME DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) {}

        // Add permissions column to staff_users if not exists
        try {
            if ($driver === 'pgsql') {
                @$pdo->exec("ALTER TABLE staff_users ADD COLUMN permissions TEXT DEFAULT NULL");
            } else {
                @$pdo->exec("ALTER TABLE `staff_users` ADD COLUMN `permissions` TEXT DEFAULT NULL");
            }
        } catch (Exception $e) {}

        // Add action_text column to services table if not exists (MySQL/PostgreSQL)
        try {
            if ($driver === 'pgsql') {
                @$pdo->exec("ALTER TABLE services ADD COLUMN action_text VARCHAR(255) DEFAULT 'ORDER NOW'");
            } else {
                @$pdo->exec("ALTER TABLE `services` ADD COLUMN `action_text` VARCHAR(255) DEFAULT 'ORDER NOW'");
            }
        } catch (Exception $e) {}
        try {
            if ($driver === 'pgsql') {
                @$pdo->exec("ALTER TABLE services ADD COLUMN product_price DECIMAL(10,2) DEFAULT 0.00");
            } else {
                @$pdo->exec("ALTER TABLE `services` ADD COLUMN `product_price` DECIMAL(10,2) DEFAULT 0.00");
            }
        } catch (Exception $e) {}
        try {
            if ($driver === 'pgsql') {
                @$pdo->exec("ALTER TABLE services ADD COLUMN referral_link VARCHAR(1000) DEFAULT NULL");
            } else {
                @$pdo->exec("ALTER TABLE `services` ADD COLUMN `referral_link` VARCHAR(1000) DEFAULT NULL");
            }
        } catch (Exception $e) {}
        try {
            if ($driver === 'pgsql') {
                @$pdo->exec("ALTER TABLE services ADD COLUMN referral_clicks INT DEFAULT 0");
            } else {
                @$pdo->exec("ALTER TABLE `services` ADD COLUMN `referral_clicks` INT DEFAULT 0");
            }
        } catch (Exception $e) {}
        try {
            if ($driver === 'pgsql') {
                @$pdo->exec("ALTER TABLE services ADD COLUMN website_link VARCHAR(1000) DEFAULT NULL");
            } else {
                @$pdo->exec("ALTER TABLE `services` ADD COLUMN `website_link` VARCHAR(1000) DEFAULT NULL");
            }
        } catch (Exception $e) {}

        // Create staff_tasks table
        try {
            if ($driver === 'pgsql') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS staff_tasks (
                    id SERIAL PRIMARY KEY,
                    staff_user_id INT DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT DEFAULT NULL,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `staff_tasks` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `staff_user_id` INT DEFAULT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `status` VARCHAR(50) DEFAULT 'pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) {}

        // Create internal_messages table
        try {
            if ($driver === 'pgsql') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS internal_messages (
                    id SERIAL PRIMARY KEY,
                    sender_id INT NOT NULL,
                    receiver_id INT DEFAULT NULL,
                    message TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `internal_messages` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `sender_id` INT NOT NULL,
                    `receiver_id` INT DEFAULT NULL,
                    `message` TEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) {}

        // Create API partner logs, invoices, user notifications, returns, and recently viewed tables
        try {
            if ($driver === 'pgsql') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS api_usage_log (
                    id SERIAL PRIMARY KEY,
                    api_partner_id INT NOT NULL,
                    endpoint VARCHAR(100) DEFAULT NULL,
                    request_data TEXT,
                    response_data TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS api_invoices (
                    id SERIAL PRIMARY KEY,
                    api_partner_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    due_date DATE DEFAULT NULL,
                    status VARCHAR(50) DEFAULT 'unpaid',
                    paid_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
                    id SERIAL PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    is_read INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS return_requests (
                    id SERIAL PRIMARY KEY,
                    order_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    partner_id INT NOT NULL,
                    reason TEXT NOT NULL,
                    status VARCHAR(50) DEFAULT 'pending',
                    admin_notes TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_recently_viewed (
                    id SERIAL PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT NOT NULL,
                    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(user_id, product_id)
                )");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `api_usage_log` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `api_partner_id` INT NOT NULL,
                    `endpoint` VARCHAR(100) DEFAULT NULL,
                    `request_data` TEXT,
                    `response_data` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`api_partner_id`) REFERENCES `api_partners`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `api_invoices` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `api_partner_id` INT NOT NULL,
                    `amount` DECIMAL(10,2) NOT NULL,
                    `due_date` DATE DEFAULT NULL,
                    `status` ENUM('unpaid','paid') DEFAULT 'unpaid',
                    `paid_at` DATETIME DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`api_partner_id`) REFERENCES `api_partners`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `user_notifications` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `message` TEXT NOT NULL,
                    `is_read` TINYINT(1) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `return_requests` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` INT NOT NULL,
                    `customer_id` INT NOT NULL,
                    `partner_id` INT NOT NULL,
                    `reason` TEXT NOT NULL,
                    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
                    `admin_notes` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`order_id`) REFERENCES `partner_orders`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `user_recently_viewed` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `product_id` INT NOT NULL,
                    `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY (`user_id`, `product_id`),
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`product_id`) REFERENCES `partner_products`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_conversations` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `partner_id` INT NOT NULL,
                    `order_id` INT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_messages` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `conversation_id` INT NOT NULL,
                    `sender_type` ENUM('user', 'partner') NOT NULL,
                    `sender_id` INT NOT NULL,
                    `message` TEXT,
                    `file_path` VARCHAR(255) DEFAULT NULL,
                    `is_read` TINYINT(1) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`conversation_id`) REFERENCES `marketplace_conversations`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) {}
    }

    // Self-healing settings injection (both MySQL and SQLite)
    $new_seeds = [
        'banner_type' => 'photo',
        'banner_media_path' => '',
        'banner_redirect_url' => '',
        'rss_feed_url' => 'https://www.prothomalo.com/feed',
        'bkash_app_key' => '',
        'bkash_app_secret' => '',
        'bkash_username' => '',
        'bkash_password' => '',
        'bkash_api_mode' => 'sandbox',
        'staff_whatsapp_number' => ''
    ];
    foreach ($new_seeds as $k => $v) {
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
            $chk->execute([':k' => $k]);
            if ($chk->fetchColumn() == 0) {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                    ->execute([':k' => $k, ':v' => $v]);
            }
        } catch (Exception $ex) {}
    }

    // Dynamic seeding for Bangladesh Government Services
    try {
        $extra_services = [
            ['Birth Certificate Correction', 'Online application and correction of birth registration data.', 350.00, '🪪 জাতীয় পরিচয়পত�র সেবা', 5],
            ['Police Clearance Certificate', 'Police clearance certificate for passport, visa or job.', 550.00, '🚔 প�লিশ সেবা', 5],
            ['e-TIN Registration', 'Electronic TIN certificate registration and copy download.', 200.00, '💵 কর ও ভ�যাট সেবা', 5],
            ['Trade License Application', 'Application support for online Trade License in city corporations or unions.', 1200.00, '💼 ব�যবসা ও বাণিজ�যিক সেবা', 5],
            ['Online Land Mutation (Namjari)', 'Apply for land mutation and land record correction online.', 2500.00, '🗺� ভূমি ও জমি সেবা', 5],
            ['Online Land Khatian Search', 'Retrieve online certified copy of land Khatian or Porcha.', 450.00, '🗺� ভূমি ও জমি সেবা', 5],
            ['Universal Pension Scheme', 'Registration support for Universal Pension Scheme (Pragati, Surokkha, Samata, Prabashi).', 300.00, '👵 সরকারি পেনশন সেবা', 5],
            ['BRTA Vehicle Tax Token Renewal', 'Online renewal of motorcycle or car tax token and fees payment.', 1500.00, '🚗 ড�রাইভিং ও যানবাহন সেবা', 5],
            ['Online GD Application', 'Online General Diary application for lost documents, certificates, mobile etc.', 250.00, '🚔 প�লিশ সেবা', 5]
        ];
        foreach ($extra_services as $es) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM services WHERE name = ?");
            $check->execute([$es[0]]);
            if ($check->fetchColumn() == 0) {
                $ins = $pdo->prepare("INSERT INTO services (name, description, fee, section_name, sort_order) VALUES (?, ?, ?, ?, ?)");
                $ins->execute($es);
            }
        }
    } catch (Exception $ex) {}

    // Add columns if missing in MySQL/SQLite
    try {
        @$pdo->exec("ALTER TABLE services ADD COLUMN header_section_name VARCHAR(255) DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("ALTER TABLE services ADD COLUMN sub_section_name VARCHAR(255) DEFAULT NULL");
    } catch (Exception $ex) {}

    // Phase 66: Courier and Tracking Additions
    try {
        @$pdo->exec("ALTER TABLE partner_orders ADD COLUMN courier_name VARCHAR(100) DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("ALTER TABLE partner_orders ADD COLUMN courier_tracking_id VARCHAR(150) DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("ALTER TABLE partner_orders ADD COLUMN delivered_at DATETIME DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("ALTER TABLE partner_orders ADD COLUMN auto_release_deadline DATETIME DEFAULT NULL");
    } catch (Exception $ex) {}
    
    // Phase 67: Storefront Coupon Engine & Announcement Banners
    try {
        @$pdo->exec("ALTER TABLE partners ADD COLUMN announcement_text TEXT DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("ALTER TABLE partners ADD COLUMN announcement_bg VARCHAR(50) DEFAULT '#fcb900'");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("CREATE TABLE IF NOT EXISTS shop_coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_id INTEGER NOT NULL,
            coupon_code VARCHAR(50) NOT NULL,
            discount_type VARCHAR(20) DEFAULT 'percentage',
            discount_value REAL NOT NULL,
            min_order_bdt REAL DEFAULT 0,
            usage_limit INTEGER DEFAULT 100,
            used_count INTEGER DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $ex) {}
    
    // Phase 69: Gamified Daily Streaks & Post-Order Reward Drops
    try {
        @$pdo->exec("ALTER TABLE users ADD COLUMN last_checkin_date DATE DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        @$pdo->exec("ALTER TABLE users ADD COLUMN daily_streak_count INTEGER DEFAULT 0");
    } catch (Exception $ex) {}

    // Load all settings globally
    $GLOBALS['settings'] = [];
    try {
        $GLOBALS['settings'] = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $ex) {
        // Table might not exist yet
    }

    // 16. Seed Fast Site Official Shop (Admin God Mode)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM partners WHERE email = 'official@fastsite.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('official_admin_secure_99', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO partners (business_name, owner_name, email, phone, password_hash, status, is_official) 
            VALUES ('Fast Site Official', 'Admin', 'official@fastsite.com', '01337320544', :h, 'approved', 1)")
            ->execute([':h' => $hash]);
    }

    // 17. Seed Default User (01337320544 / admin)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = '01337320544'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name, phone, password_hash, coins_balance, ref_code) 
            VALUES ('Test User', '01337320544', :h, 1000.00, 'TEST1234')")
            ->execute([':h' => $hash]);
    }

} // end migration guard

// =========================================================================
// upsertSetting() — Database-agnostic INSERT or UPDATE for homepage_settings
// Replaces repetitive CHECK UPDATE/INSERT pattern across settings.php.
// =========================================================================
if (!function_exists('upsertSetting')) {
    function upsertSetting($pdo, $key, $value) {
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
            $chk->execute([$key]);
            if ($chk->fetchColumn() > 0) {
                $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")
                    ->execute([$value, $key]);
            } else {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")
                    ->execute([$key, $value]);
            }
        } catch (Exception $e) {
            error_log("upsertSetting Error [{$key}]: " . $e->getMessage());
        }
    }
}
// Partner settings helper
function getPartnerSetting($key, $default = '') {
    global $pdo;
    static $partner_settings = [];
    if (empty($partner_settings)) {
        try {
            if (isset($pdo)) {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM partner_settings");
                if ($stmt) {
                    $partner_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                }
            }
        } catch (Exception $e) {
            // Table might not exist yet
        }
    }
    if (isset($partner_settings[$key])) {
        return $partner_settings[$key];
    }
    if (isset($GLOBALS['settings'][$key])) {
        return $GLOBALS['settings'][$key];
    }
    return $default;
}

// Helper to fetch remote content using curl (fallback to file_get_contents)
function get_url_contents($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response !== false) {
            return $response;
        }
    }
    
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 6,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    return @file_get_contents($url, false, $ctx);
}

// Custom World Cup API Fetch & Cache Helper
function get_worldcup_data() {
    $cache_file = __DIR__ . '/uploads/worldcup_cache.json';
    
    // Create uploads directory if not exists
    if (!is_dir(__DIR__ . '/uploads')) {
        @mkdir(__DIR__ . '/uploads', 0755, true);
    }
    
    $cache_valid = false;
    if (file_exists($cache_file)) {
        $age = time() - filemtime($cache_file);
        if ($age < 300) { // 5 minutes cache
            $cache_valid = true;
        }
    }
    
    if (!$cache_valid) {
        // Fetch fresh data
        try {
            $games_json = get_url_contents('https://worldcup26.ir/get/games');
            $teams_json = get_url_contents('https://worldcup26.ir/get/teams');
            
            if ($games_json && $teams_json) {
                $games_data = json_decode($games_json, true);
                $teams_data = json_decode($teams_json, true);
                
                if (isset($games_data['games']) && isset($teams_data['teams'])) {
                    // Map teams by ID for O(1) lookup
                    $teams_map = [];
                    foreach ($teams_data['teams'] as $team) {
                        $teams_map[$team['id']] = $team;
                    }
                    
                    // Enrich games with team flags and iso2
                    $enriched_games = [];
                    foreach ($games_data['games'] as $game) {
                        $home_id = $game['home_team_id'];
                        $away_id = $game['away_team_id'];
                        
                        $game['home_flag'] = $teams_map[$home_id]['flag'] ?? '';
                        $game['home_iso2'] = $teams_map[$home_id]['iso2'] ?? '';
                        $game['away_flag'] = $teams_map[$away_id]['flag'] ?? '';
                        $game['away_iso2'] = $teams_map[$away_id]['iso2'] ?? '';
                        
                        $enriched_games[] = $game;
                    }
                    
                    $final_data = [
                        'games' => $enriched_games,
                        'fetched_at' => time()
                    ];
                    
                    file_put_contents($cache_file, json_encode($final_data, JSON_PRETTY_PRINT));
                }
            }
        } catch (Exception $e) {
            // If API fetch fails, keep using expired cache if available
        }
    }
    
    // Read from cache
    if (file_exists($cache_file)) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    return null;
}

// Global Secure Upload Helper for Images & Documents
function handleSecureUpload($fileArray, $destinationDir, $allowedExtensions = ['jpg','jpeg','png','webp','gif','svg','pdf'], $prefix = 'file') {
    if (!isset($fileArray['error']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $maxSizeBytes = 10485760; // 10MB limit
    if ($fileArray['size'] > $maxSizeBytes) {
        return false;
    }

    $tmpName = $fileArray['tmp_name'];
    
    // Verify true MIME type using finfo
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) return false;
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    // Map allowed MIME types to their correct safe extensions
    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/csv' => 'csv',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'video/mp4' => 'mp4',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico'
    ];

    if (!array_key_exists($mimeType, $mimeTypes)) {
        return false; // Invalid or unsafe file type
    }

    $trueExt = $mimeTypes[$mimeType];
    
    // Check if the true extension is in the allowed list for this specific upload
    // Note: We also check alternative extensions (like 'jpeg' for 'jpg')
    $isAllowed = false;
    foreach ($allowedExtensions as $ext) {
        $ext = strtolower(trim($ext));
        if ($trueExt === $ext || ($trueExt === 'jpg' && $ext === 'jpeg') || ($trueExt === 'ico' && $ext === 'icon')) {
            $isAllowed = true;
            break;
        }
    }

    if (!$isAllowed) {
        return false; 
    }

    // Generate a secure, unpredictable filename (ignores user-provided name completely)
    $safeName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $trueExt;
    $finalDestination = rtrim($destinationDir, '/') . '/' . $safeName;

    // Additional Image Processing (EXIF stripping and orientation correction for JPEG)
    if ($trueExt === 'jpg' && function_exists('imagecreatefromjpeg') && function_exists('exif_read_data')) {
        $image = @imagecreatefromjpeg($tmpName);
        if ($image) {
            $exif = @exif_read_data($tmpName);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3: $image = imagerotate($image, 180, 0); break;
                    case 6: $image = imagerotate($image, -90, 0); break;
                    case 8: $image = imagerotate($image, 90, 0); break;
                }
            }
            // Re-saving using GD strips malicious EXIF tags
            $success = imagejpeg($image, $finalDestination, 85);
            imagedestroy($image);
            return $success ? $safeName : false;
        }
    }

    // For non-JPEGs or if GD fails, securely move the file
    // Since we verified MIME type and forced the extension, this is safe from RCE.
    if (move_uploaded_file($tmpName, $finalDestination)) {
        return $safeName; // Return just the generated name
    }
    
    return false;
}

// =========================================================================
// Universal Product Artwork & Thumbnail Resolver
// =========================================================================
if (!function_exists('resolveProductArtwork')) {
    function resolveProductArtwork($thumb = '', $fallback = '', $shopName = '', $category = '', $title = '', $listingType = 'product') {
        $raw = !empty($thumb) ? trim($thumb) : (!empty($fallback) ? trim($fallback) : '');
        
        if (!empty($raw)) {
            // Normalize backslashes (Windows -> POSIX)
            $raw = str_replace('\\', '/', $raw);

            // Strip localhost / 127.0.0.1 domain & port to ensure local URLs never break on Hostinger production
            $raw = preg_replace('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', '', $raw);

            // Clean duplicate / nested domain prefixes (e.g. https://best-travel.ltd/https://... or https://atayramart.com/https://...)
            while (preg_match('#^https?://[^/]+/(https?://.+)#i', $raw, $m)) {
                $raw = $m[1];
            }
            // Clean double slashes in paths (excluding protocol ://)
            $raw = preg_replace('#([^:])//+#', '$1/', $raw);

            // If it is a legitimate remote HTTP URL or data URI
            if (preg_match('#^https?://#i', $raw) || preg_match('#^data:image/#i', $raw)) {
                return $raw;
            }

            // Clean local paths and strip relative ../ or ./
            $cleanRelative = preg_replace('#^(\.\./)+#', '', $raw);
            $cleanRelative = preg_replace('#^(\./)+#', '', $cleanRelative);
            $cleanRelative = ltrim($cleanRelative, '/');
            
            // Check if file exists on disk
            $possiblePaths = [
                __DIR__ . '/' . $cleanRelative,
                __DIR__ . '/uploads/partners/' . basename($cleanRelative),
                __DIR__ . '/uploads/products/' . basename($cleanRelative),
                __DIR__ . '/uploads/' . basename($cleanRelative),
                __DIR__ . '/assets/images/services/' . basename($cleanRelative),
                __DIR__ . '/assets/images/' . basename($cleanRelative)
            ];

            foreach ($possiblePaths as $p) {
                if (file_exists($p) && is_file($p)) {
                    $relFromRoot = str_replace('\\', '/', substr($p, strlen(__DIR__)));
                    return '/' . ltrim($relFromRoot, '/');
                }
            }

            // If it belongs to an ecosystem partner, build remote URL
            if ($shopName === 'Ayra Mart') {
                return 'https://atayramart.com/uploads/products/' . basename($cleanRelative);
            } elseif ($shopName === 'Enzor Motor' || $shopName === 'Enzor Motors') {
                return 'https://enzor.best-travel.ltd/uploads/products/' . basename($cleanRelative);
            } elseif ($shopName === 'Best Travel') {
                return 'https://best-travel.ltd/uploads/' . basename($cleanRelative);
            } elseif ($shopName === 'Affi Bangla') {
                return 'https://affibangla.best-travel.ltd/uploads/' . basename($cleanRelative);
            } elseif ($shopName === 'Manza') {
                return 'https://manza.best-travel.ltd/uploads/' . basename($cleanRelative);
            } elseif ($shopName === 'GixSam') {
                return 'https://gixsam.best-travel.ltd/uploads/' . basename($cleanRelative);
            }

            // If it has a standard image extension, return dynamic root-relative path (e.g. /uploads/...)
            $ext = strtolower(pathinfo($cleanRelative, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'ico'])) {
                if (strpos($cleanRelative, 'uploads/') === 0 || strpos($cleanRelative, 'assets/') === 0) {
                    return '/' . $cleanRelative;
                }
                if (strpos($cleanRelative, 'prod_') === 0) {
                    return '/uploads/products/' . basename($cleanRelative);
                }
                if (strpos($cleanRelative, 'profile_') === 0 || strpos($cleanRelative, 'cover_') === 0 || strpos($cleanRelative, 'logo_') === 0) {
                    return '/uploads/partners/' . basename($cleanRelative);
                }
                if (strpos($cleanRelative, 'pp_') === 0 || strpos($cleanRelative, 'kyc_') === 0) {
                    return '/uploads/kyc/' . basename($cleanRelative);
                }
                return '/uploads/products/' . basename($cleanRelative);
            }
        }

        // Smart Category, Destination & Title Visual Artwork Fallbacks (when NO image was provided or image is loading)
        $t = mb_strtolower($title . ' ' . $category . ' ' . $shopName, 'UTF-8');
        
        // Travel & Tour Package Authentic HD Fallbacks
        if (strpos($t, 'cox') !== false || strpos($t, 'bazar') !== false || strpos($t, 'inani') !== false) {
            return 'https://images.unsplash.com/photo-1608958435020-e8a7109ba809?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'sajek') !== false || strpos($t, 'konglak') !== false || strpos($t, 'ruilui') !== false) {
            return 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'sreemangal') !== false || strpos($t, 'tea capital') !== false || strpos($t, 'lawachara') !== false) {
            return 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'sundarban') !== false || strpos($t, 'mangrove') !== false || strpos($t, 'kotka') !== false) {
            return 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'kashmir') !== false || strpos($t, 'ladakh') !== false || strpos($t, 'gulmarg') !== false || strpos($t, 'srinagar') !== false) {
            return 'https://images.unsplash.com/photo-1566837945700-30057527ade0?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'thailand') !== false || strpos($t, 'phuket') !== false || strpos($t, 'krabi') !== false || strpos($t, 'bangkok') !== false) {
            return 'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'dubai') !== false || strpos($t, 'burj') !== false || strpos($t, 'desert safari') !== false || strpos($t, 'uae') !== false) {
            return 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'vietnam') !== false || strpos($t, 'ha long') !== false || strpos($t, 'hanoi') !== false) {
            return 'https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'umrah') !== false || strpos($t, 'hajj') !== false || strpos($t, 'makkah') !== false || strpos($t, 'madinah') !== false) {
            return 'https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($t, 'tour') !== false || strpos($t, 'travel') !== false || strpos($t, 'holiday') !== false || strpos($t, 'package') !== false) {
            return 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=800&q=80';
        }

        // Official Government & Citizen Services Fallbacks
        if (strpos($t, 'nid') !== false || strpos($t, 'পরিচয়পত্র') !== false || strpos($t, 'national id') !== false) {
            return '/assets/images/services/nid_service.svg';
        }
        if (strpos($t, 'driving') !== false || strpos($t, 'ড্রাইভিং') !== false || strpos($t, 'license') !== false || strpos($t, 'লাইসেন্স') !== false || strpos($t, 'brta') !== false) {
            return '/assets/images/services/driving_license.svg';
        }
        if (strpos($t, 'passport') !== false || strpos($t, 'পাসপোর্ট') !== false || strpos($t, 'visa') !== false || strpos($t, 'ভিসা') !== false) {
            return '/assets/images/services/passport_service.svg';
        }
        if (strpos($t, 'birth') !== false || strpos($t, 'জন্ম') !== false || strpos($t, 'certificate') !== false || strpos($t, 'নিবন্ধন') !== false) {
            return '/assets/images/services/birth_certificate.svg';
        }
        if (strpos($t, 'ভূমি') !== false || strpos($t, 'খাজনা') !== false || strpos($t, 'mutation') !== false || strpos($t, 'land') !== false) {
            return '/assets/images/services/land_service.svg';
        }
        if (strpos($t, 'logo') !== false || strpos($t, 'লোগো') !== false || strpos($t, 'ডিজাইন') !== false || strpos($t, 'poster') !== false || strpos($t, 'পোস্টার') !== false || strpos($t, 'graphic') !== false) {
            return '/assets/images/services/logo_design.svg';
        }
        if (strpos($t, 'video') !== false || strpos($t, 'ভিডিও') !== false || strpos($t, 'animation') !== false || strpos($t, 'অ্যানিমেশন') !== false || strpos($t, 'ad') !== false) {
            return '/assets/images/services/video_animation.svg';
        }
        if (strpos($t, 'web') !== false || strpos($t, 'code') !== false || strpos($t, 'development') !== false || strpos($t, 'software') !== false || strpos($t, 'site') !== false) {
            return '/assets/images/services/web_dev.svg';
        }

        return '/assets/images/services/default_service.svg';
    }
}

// =========================================================================
// Gamification / Trust Level Helpers
// =========================================================================
if (!function_exists('updatePartnerSellerLevel')) {
    function updatePartnerSellerLevel($partner_id, $pdo) {
        // Fetch current orders and rating
        $stmt = $pdo->prepare("SELECT total_orders, rating FROM partners WHERE id = ?");
        $stmt->execute([$partner_id]);
        $partner = $stmt->fetch();
        if ($partner) {
            $orders = (int)$partner['total_orders'];
            $rating = (float)$partner['rating'];
            
            $new_level = 1;
            if ($orders > 50 && $rating >= 4.5) {
                $new_level = 3; // Top Rated
            } elseif ($orders > 10) {
                $new_level = 2; // Level 2
            }
            
            $pdo->prepare("UPDATE partners SET seller_level = ? WHERE id = ?")->execute([$new_level, $partner_id]);
        }
    }
}

// Unconditional Self-Healing Migration for Official Admin Shop & All Partner Columns
try {
    if (isset($pdo)) {
        $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        
        $partnerCols = [
            'registration_number' => $isSqlite ? 'TEXT DEFAULT NULL' : 'VARCHAR(50) DEFAULT NULL',
            'shop_slug'           => $isSqlite ? 'TEXT DEFAULT NULL' : 'VARCHAR(100) DEFAULT NULL',
            'is_hidden'           => 'TINYINT(1) DEFAULT 0',
            'is_official'         => 'TINYINT(1) DEFAULT 0',
            'seller_level'        => 'INT DEFAULT 1',
            'total_orders'        => 'INT DEFAULT 0',
            'rating'              => 'DECIMAL(3,2) DEFAULT 5.00',
            'tags'                => 'TEXT DEFAULT NULL',
            'website_url'         => 'TEXT DEFAULT NULL',
            'cover_pic'           => 'VARCHAR(255) DEFAULT NULL',
            'profile_pic'         => 'VARCHAR(255) DEFAULT NULL',
            'district'            => 'VARCHAR(100) DEFAULT NULL',
            'total_earned'        => 'DECIMAL(12,2) DEFAULT 0.00'
        ];

        foreach ($partnerCols as $col => $type) {
            try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `{$col}` {$type}"); } catch (Exception $ex) {}
        }

        // Unify and delete FS-SHOP-00000 references, ensuring FS-OFFICIAL-1 is the primary official shop
        @$pdo->exec("UPDATE partners SET registration_number = 'FS-OFFICIAL-1', is_official = 1, seller_level = 3, status = 'approved' WHERE is_official = 1 OR registration_number = 'FS-SHOP-00000' OR email = 'admin@fastsite.com' OR id = 1");
        
        // One-Time Historical Migration: Approve PAST registered shopper shops only (Future registrations stay pending)
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS system_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(100) UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS system_migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration_name TEXT UNIQUE,
                    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (Exception $ex) {}
        }

        try {
            $migChk = $pdo->query("SELECT id FROM system_migrations WHERE migration_name = 'approve_past_shopper_shops_phase44' LIMIT 1");
            if (!$migChk || !$migChk->fetch()) {
                @$pdo->exec("UPDATE partners SET status = 'approved' WHERE status IS NULL OR status = '' OR status = 'pending'");
                @$pdo->exec("UPDATE partner_products SET is_published = 1 WHERE is_published IS NULL");
                @$pdo->exec("INSERT INTO system_migrations (migration_name) VALUES ('approve_past_shopper_shops_phase44')");
            }
        } catch (Exception $e) {}
    }
} catch (Exception $e) {}

// Self-Healing Schema for Omni-Task Rewards Engine (User, Agent & Staff Tasks)
try {
    if (isset($pdo)) {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                target_role TEXT DEFAULT 'user',
                title TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                category TEXT DEFAULT 'General',
                reward_points REAL DEFAULT 50.00,
                action_url TEXT DEFAULT NULL,
                proof_type TEXT DEFAULT 'screenshot',
                max_completions INTEGER DEFAULT 1,
                deadline DATE DEFAULT NULL,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS task_submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                proof_text TEXT DEFAULT NULL,
                proof_screenshot TEXT DEFAULT NULL,
                status TEXT DEFAULT 'pending',
                admin_notes TEXT DEFAULT NULL,
                reward_credited REAL DEFAULT 0.00,
                submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                reviewed_at DATETIME DEFAULT NULL
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                target_role VARCHAR(50) DEFAULT 'user',
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                category VARCHAR(100) DEFAULT 'General',
                reward_points DECIMAL(10,2) DEFAULT 50.00,
                action_url TEXT DEFAULT NULL,
                proof_type VARCHAR(50) DEFAULT 'screenshot',
                max_completions INT DEFAULT 1,
                deadline DATE DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS task_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                user_id INT NOT NULL,
                proof_text TEXT DEFAULT NULL,
                proof_screenshot VARCHAR(255) DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                admin_notes TEXT DEFAULT NULL,
                reward_credited DECIMAL(10,2) DEFAULT 0.00,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_at TIMESTAMP NULL DEFAULT NULL,
                INDEX (task_id),
                INDEX (user_id),
                INDEX (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Seed initial starter tasks if empty
        $tCount = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        if ($tCount === 0) {
            $seedTasks = [
                ['user', '💬 Join Fast Site WhatsApp VIP Channel', 'Join our official WhatsApp Community Channel for daily gift codes and announcements. Submit your WhatsApp number / screenshot as proof.', 'Social Media', 50.00, 'https://wa.me/8801337320544', 'screenshot'],
                ['user', '📢 Share Fast Site on Facebook & Tag 3 Friends', 'Post about Fast Site services on your Facebook timeline or in a public group. Submit the post link or screenshot.', 'Social Media', 100.00, 'https://facebook.com', 'both'],
                ['user', '🪪 Complete KYC & Profile Verification', 'Upload your profile avatar, verified phone, and complete your basic KYC details to earn Fast Points bonus.', 'Profile & KYC', 150.00, '/user/profile.php', 'text'],
                ['user', '👥 Invite Your First Friend via Referral Link', 'Share your personal referral link with a friend and earn 200 Fast Points once they sign up!', 'Referral', 200.00, '/user/wallet.php', 'text']
            ];
            $tStmt = $pdo->prepare("INSERT INTO tasks (target_role, title, description, category, reward_points, action_url, proof_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            foreach ($seedTasks as $st) {
                $tStmt->execute($st);
            }
        }
    }
} catch (Exception $e) {}

// =========================================================================
// Phase 69: claimDailyStreakReward()
// =========================================================================
if (!function_exists('claimDailyStreakReward')) {
    function claimDailyStreakReward($pdo, $user_id) {
        $stmt = $pdo->prepare("SELECT last_checkin_date, daily_streak_count FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) return ['success' => false, 'message' => 'User not found.'];

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $last_date = $u['last_checkin_date'];
        $streak = (int)$u['daily_streak_count'];

        if ($last_date === $today) {
            return ['success' => false, 'message' => 'Already claimed today!'];
        }

        if ($last_date === $yesterday) {
            $streak += 1;
        } else {
            $streak = 1; // reset or first time
        }

        $reward = 5;
        if ($streak > 0 && $streak % 7 === 0) {
            $reward = 50; // 7-day milestone
        }

        try {
            $pdo->beginTransaction();
            // Update user
            $upd = $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ?, daily_streak_count = ?, last_checkin_date = ? WHERE id = ?");
            $upd->execute([$reward, $streak, $today, $user_id]);
            
            // Insert transaction
            $ins = $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'streak_reward', ?, ?, 'completed', CURRENT_TIMESTAMP)");
            $ins->execute([$user_id, $reward, "Day {$streak} Streak Bonus"]);
            
            $pdo->commit();
            return ['success' => true, 'reward' => $reward, 'streak' => $streak];
        } catch (Exception $ex) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Database error during claim.'];
        }
    }
}
