<?php
// =========================================================================
// admin/settings.php  —  v5: Premium Mobile-First Overhaul
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$isAdmin = ($_SESSION['admin_role'] ?? 'staff') === 'admin';
$msg = $err = '';

$uploadDir = '../uploads/branding/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Load current settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Load current staff user
$currentAdminUser = $_SESSION['admin_user'] ?? ($_SESSION['admin_username'] ?? '');
$me = $pdo->prepare("SELECT * FROM staff_users WHERE username = :u");
$me->execute([':u' => $currentAdminUser]);
$staffUser = $me->fetch() ?: [];

//  Handle saves 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save Navigation Positioning & Visibility
    if ($action === 'save_nav_positioning') {
        $pos = trim($_POST['nav_positioning'] ?? '');
        $vis = trim($_POST['nav_visibility'] ?? '');
        
        // Save Positioning
        $chk1 = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'nav_positioning'");
        $chk1->execute();
        if ($chk1->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'nav_positioning'")
                ->execute([':v' => $pos]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('nav_positioning', :v)")
                ->execute([':v' => $pos]);
        }
        
        // Save Visibility
        $chk2 = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'nav_visibility'");
        $chk2->execute();
        if ($chk2->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'nav_visibility'")
                ->execute([':v' => $vis]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('nav_visibility', :v)")
                ->execute([':v' => $vis]);
        }

        // Save sub-sections positioning & visibility
        $sub_keys = ['all_user', 'partners', 'give_task', 'chat', 'settings', 'view_option'];
        foreach ($sub_keys as $sk) {
            $pos_key = 'sub_nav_pos_' . $sk;
            $vis_key = 'sub_nav_vis_' . $sk;
            
            if (isset($_POST[$pos_key])) {
                $p_val = trim($_POST[$pos_key]);
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
                $chk->execute([$pos_key]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")
                        ->execute([$p_val, $pos_key]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")
                        ->execute([$pos_key, $p_val]);
                }
            }
            
            if (isset($_POST[$vis_key])) {
                $v_val = trim($_POST[$vis_key]);
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
                $chk->execute([$vis_key]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")
                        ->execute([$v_val, $vis_key]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")
                        ->execute([$vis_key, $v_val]);
                }
            }
        }
        
        $msg = 'Navigation and dropdown reordering updated successfully.';
        // Reload settings
        $settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Personal Staff Profile & Socials
    if ($action === 'update_staff_profile') {
        $whatsapp  = trim($_POST['whatsapp'] ?? '');
        $facebook  = trim($_POST['facebook'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $twitter   = trim($_POST['twitter'] ?? '');
        $youtube   = trim($_POST['youtube'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        $pdo->prepare("UPDATE staff_users SET email = :e, whatsapp = :wa, facebook = :fb, instagram = :ig, twitter = :tw, youtube = :yt WHERE username = :u")
            ->execute([
                ':e'  => $email ?: null,
                ':wa' => $whatsapp ?: null,
                ':fb' => $facebook ?: null,
                ':ig' => $instagram ?: null,
                ':tw' => $twitter ?: null,
                ':yt' => $youtube ?: null,
                ':u'  => $_SESSION['admin_user']
            ]);
        $msg = 'Your personal profile and social platforms updated successfully.';
        
        // Reload staff user
        $me = $pdo->prepare("SELECT * FROM staff_users WHERE username = :u");
        $me->execute([':u' => $_SESSION['admin_user']]);
        $staffUser = $me->fetch();
    }

    // Branding
    if ($action === 'save_branding') {
        $keys = ['site_name','whatsapp_number','staff_whatsapp_number','business_hours','global_notice','facebook_url','instagram_url','twitter_url','youtube_url','admin_header_name','admin_header_tag','banner_type','banner_redirect_url','rss_feed_url','bkash_merchant_number','bkash_app_key','bkash_app_secret','bkash_username','bkash_password','bkash_api_mode','banner_custom_html','affi_bangla_url',
        'what_we_offer', 'govt_service_desc', 'website_service_desc', 'dropshipping_api_desc', 'marketplace_desc',
        'gov_slide1_title', 'gov_slide1_desc', 'gov_slide1_target',
        'gov_slide2_title', 'gov_slide2_desc', 'gov_slide2_target',
        'gov_slide3_title', 'gov_slide3_desc', 'gov_slide3_target',
        'gov_slide4_title', 'gov_slide4_desc', 'gov_slide4_target',
        'wc_match_status', 'wc_kickoff_time', 'wc_home_team', 'wc_home_flag', 'wc_away_team', 'wc_away_flag', 'wc_home_score', 'wc_away_score', 'wc_time_elapsed', 'wc_stadium',
        'chatbot_name_fast_site', 'chatbot_name_best_travel', 'chatbot_name_ayra_mart', 'chatbot_name_affi_bangla', 'chatbot_name_enzor',
        'marketplace_whatsapp', 'manual_payment_number', 'shipping_dhaka_in', 'shipping_dhaka_out', 'shipping_soft', 'coin_name', 'gemini_api_key', 'cashback_pct', 'apk_app_name'];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $v = trim($_POST[$k]);
                
                // Database-agnostic ON DUPLICATE KEY UPDATE replacement
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
                $chk->execute([':k' => $k]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                        ->execute([':v' => $v, ':k' => $k]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                        ->execute([':k' => $k, ':v' => $v]);
                }
            }
        }
        // Logo upload
        if (!empty($_FILES['logo_file']['name'])) {
            $logoName = handleSecureUpload($_FILES['logo_file'], $uploadDir, ['jpg','jpeg','png','webp','gif','svg','ico'], 'logo');
            if ($logoName) {
                $logoPath = 'uploads/branding/' . $logoName;
                
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'logo_url'");
                $chk->execute([]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'logo_url'")
                        ->execute([':v' => $logoPath]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('logo_url', :v)")
                        ->execute([':v' => $logoPath]);
                }
            }
        }
        
        // User APK Photo Upload
        if (!empty($_FILES['apk_app_photo_file']['name'])) {
            $apkPhotoName = handleSecureUpload($_FILES['apk_app_photo_file'], $uploadDir, ['jpg','jpeg','png','webp','gif','svg','ico'], 'apk_photo');
            if ($apkPhotoName) {
                $apkPhotoPath = 'uploads/branding/' . $apkPhotoName;
                
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'apk_app_photo'");
                $chk->execute([]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'apk_app_photo'")
                        ->execute([':v' => $apkPhotoPath]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('apk_app_photo', :v)")
                        ->execute([':v' => $apkPhotoPath]);
                }
            }
        }

        // Admin FAST SITE HQ APK Photo Upload
        if (!empty($_FILES['admin_apk_photo_file']['name'])) {
            $adminApkPhotoName = handleSecureUpload($_FILES['admin_apk_photo_file'], $uploadDir, ['jpg','jpeg','png','webp','gif','svg','ico'], 'admin_apk_photo');
            if ($adminApkPhotoName) {
                $adminApkPhotoPath = 'uploads/branding/' . $adminApkPhotoName;
                
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'admin_apk_photo'");
                $chk->execute([]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'admin_apk_photo'")
                        ->execute([':v' => $adminApkPhotoPath]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('admin_apk_photo', :v)")
                        ->execute([':v' => $adminApkPhotoPath]);
                }
            }
        }
        
        // Payment QR uploads (bKash & Nagad)
        $qrUploads = ['bkash_qr_file' => 'bkash_qr_url', 'nagad_qr_file' => 'nagad_qr_url'];
        foreach ($qrUploads as $fileKey => $dbKey) {
            if (!empty($_FILES[$fileKey]['name'])) {
                $qrName = handleSecureUpload($_FILES[$fileKey], $uploadDir, ['jpg','jpeg','png','webp','gif','svg'], $dbKey);
                if ($qrName) {
                    $qrPath = 'uploads/branding/' . $qrName;
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
                    $chk->execute([$dbKey]);
                    if ($chk->fetchColumn() > 0) {
                        $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")
                            ->execute([$qrPath, $dbKey]);
                    } else {
                        $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")
                            ->execute([$dbKey, $qrPath]);
                    }
                }
            }
        }
        
        // Banner files upload (photo/video/etc.) - Support multiple files
        $keptBanners = $_POST['existing_banners'] ?? [];
        $uploadedBanners = [];
        
        if (!empty($_FILES['banner_files']['name'][0])) {
            foreach ($_FILES['banner_files']['name'] as $i => $filename) {
                $fileArray = [
                    'name' => $_FILES['banner_files']['name'][$i],
                    'type' => $_FILES['banner_files']['type'][$i],
                    'tmp_name' => $_FILES['banner_files']['tmp_name'][$i],
                    'error' => $_FILES['banner_files']['error'][$i],
                    'size' => $_FILES['banner_files']['size'][$i]
                ];
                $bannerName = handleSecureUpload($fileArray, $uploadDir, ['jpg','jpeg','png','webp','gif','svg','mp4'], 'banner');
                if ($bannerName) {
                    $uploadedBanners[] = 'uploads/branding/' . $bannerName;
                }
            }
        }
        
        $allBanners = array_merge($keptBanners, $uploadedBanners);
        $allBannersJson = json_encode($allBanners);
        
        // Update banner_media_paths in DB
        $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'banner_media_paths'");
        $chk->execute([]);
        if ($chk->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'banner_media_paths'")
                ->execute([':v' => $allBannersJson]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('banner_media_paths', :v)")
                ->execute([':v' => $allBannersJson]);
        }
        
        // Also save the first banner to banner_media_path for backward compatibility
        $firstBanner = !empty($allBanners) ? $allBanners[0] : '';
        $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'banner_media_path'");
        $chk->execute([]);
        if ($chk->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'banner_media_path'")
                ->execute([':v' => $firstBanner]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('banner_media_path', :v)")
                ->execute([':v' => $firstBanner]);
        }
        $msg = 'Branding settings saved successfully.';
    }

    // Password change
    if ($action === 'change_password') {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $cnf = $_POST['confirm_password'] ?? '';
        $me  = $pdo->prepare("SELECT password_hash FROM staff_users WHERE username=:u");
        $me->execute([':u'=>$_SESSION['admin_user']]);
        $myRow = $me->fetch();
        if (!password_verify($cur, $myRow['password_hash'])) {
            $err = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $err = 'New password must be at least 6 characters.';
        } elseif ($new !== $cnf) {
            $err = 'New passwords do not match.';
        } else {
            $pdo->prepare("UPDATE staff_users SET password_hash=:h WHERE username=:u")
                ->execute([':h'=>password_hash($new,PASSWORD_BCRYPT),':u'=>$_SESSION['admin_user']]);
            $msg = 'Password changed successfully.';
        }
    }

    // Terms & Conditions
    if ($action === 'save_tnc') {
        foreach (['user_tnc','agent_tnc'] as $k) {
            $v = $_POST[$k] ?? '';
            
            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
            $chk->execute([':k' => $k]);
            if ($chk->fetchColumn() > 0) {
                $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                    ->execute([':v' => $v, ':k' => $k]);
            } else {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                    ->execute([':k' => $k, ':v' => $v]);
            }
        }
        $msg = 'Terms & Conditions saved.';
    }

    // Payout / Commission settings
    if ($action === 'save_payout') {
        foreach (['min_payout','default_commission_pct'] as $k) {
            $v = floatval($_POST[$k] ?? 0);
            
            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
            $chk->execute([':k' => $k]);
            if ($chk->fetchColumn() > 0) {
                $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                    ->execute([':v' => $v, ':k' => $k]);
            } else {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                    ->execute([':k' => $k, ':v' => $v]);
            }
        }
        $msg = 'Payout settings saved.';
    }

    // Staff management
    if ($action === 'add_staff' && $isAdmin) {
        $u = trim($_POST['new_username'] ?? '');
        $p = trim($_POST['new_password'] ?? '');
        if ($u && $p) {
            try {
                $pdo->prepare("INSERT INTO staff_users (username,password_hash,role) VALUES(?,?,'staff')")
                    ->execute([$u, password_hash($p, PASSWORD_BCRYPT)]);
                $msg = "Staff '$u' added.";
            } catch (Exception $e) { $err = 'Username already exists.'; }
        }
    }
    if ($action === 'delete_staff' && $isAdmin) {
        $id = (int)$_POST['user_id'];
        if ($id !== 1) { 
            $pdo->prepare("DELETE FROM staff_users WHERE id=?")->execute([$id]); 
            $msg = 'Staff deleted.'; 
        }
    }

    // Save Mobile App Upgrade Settings
    if ($action === 'save_mobile_app') {
        $keys = ['app_client_version_code', 'app_client_version_name', 'app_client_apk_url', 'app_admin_version_code', 'app_admin_version_name', 'app_admin_apk_url'];
        foreach ($keys as $k) {
            $v = trim($_POST[$k] ?? '');
            
            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
            $chk->execute([':k' => $k]);
            if ($chk->fetchColumn() > 0) {
                $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                    ->execute([':v' => $v, ':k' => $k]);
            } else {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                    ->execute([':k' => $k, ':v' => $v]);
            }
        }
        $msg = 'Mobile App auto-upgrade configurations saved successfully.';
    }

    // Save Navigation Labels & Custom Logos
    if ($action === 'save_nav_labels') {
        $keys = [
            'nav_dashboard', 'nav_services', 'nav_partners', 'nav_api_partners', 
            'nav_agents', 'nav_payouts', 'nav_tasks', 'nav_chats', 'nav_settings', 
            'nav_all_user', 'nav_directory', 'nav_view_option', 'nav_access_control'
        ];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $v = trim($_POST[$k]);
                
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
                $chk->execute([':k' => $k]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                        ->execute([':v' => $v, ':k' => $k]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                        ->execute([':k' => $k, ':v' => $v]);
                }
            }
        }

        // Save Logo Only and Custom Logo Uploads for 11 top-level keys
        $sections = ['dashboard', 'service', 'all_user', 'partners', 'directory', 'payouts', 'give_task', 'chat', 'settings', 'view_option', 'access_control'];
        foreach ($sections as $sec) {
            $setting_k = 'logo_only_' . $sec;
            $val = isset($_POST[$setting_k]) ? '1' : '0';

            $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
            $chk->execute([':k' => $setting_k]);
            if ($chk->fetchColumn() > 0) {
                $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                    ->execute([':v' => $val, ':k' => $setting_k]);
            } else {
                $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                    ->execute([':k' => $setting_k, ':v' => $val]);
            }

            // Custom logo upload
            $file_k = 'logo_file_' . $sec;
            if (isset($_FILES[$file_k]) && $_FILES[$file_k]['error'] === UPLOAD_ERR_OK) {
                $logoName = handleSecureUpload($_FILES[$file_k], $uploadDir, ['jpg','jpeg','png','webp','gif','svg','ico'], 'nav_logo_'.$sec);
                if ($logoName) {
                    $logoPath = 'uploads/branding/' . $logoName;
                    
                    $setting_logo_k = 'logo_path_' . $sec;
                    $chk_logo = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = :k");
                    $chk_logo->execute([':k' => $setting_logo_k]);
                    if ($chk_logo->fetchColumn() > 0) {
                        $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = :k")
                            ->execute([':v' => $logoPath, ':k' => $setting_logo_k]);
                    } else {
                        $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (:k, :v)")
                            ->execute([':k' => $setting_logo_k, ':v' => $logoPath]);
                    }
                }
            }
        }
        $msg = 'Navigation labels and logos updated successfully.';
    }

    if ($action === 'save_view_option') {
        $view = trim($_POST['default_dashboard_view'] ?? 'staff_dash');
        $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'default_dashboard_view'");
        $chk->execute();
        if ($chk->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = :v WHERE setting_key = 'default_dashboard_view'")
                ->execute([':v' => $view]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('default_dashboard_view', :v)")
                ->execute([':v' => $view]);
        }
        $msg = 'Dashboard view options updated successfully.';
    }

    if ($action === 'save_homepage_customise') {
        $order = trim($_POST['homepage_sections_order'] ?? '');
        $vis = trim($_POST['homepage_sections_visibility'] ?? '');
        
        $chk_order = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'homepage_sections_order'");
        $chk_order->execute();
        if ($chk_order->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = 'homepage_sections_order'")->execute([$order]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('homepage_sections_order', ?)")->execute([$order]);
        }
        
        $chk_vis = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = 'homepage_sections_visibility'");
        $chk_vis->execute();
        if ($chk_vis->fetchColumn() > 0) {
            $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = 'homepage_sections_visibility'")->execute([$vis]);
        } else {
            $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('homepage_sections_visibility', ?)")->execute([$vis]);
        }
        
        $homepage_keys = ['hero', 'overview', 'search', 'promo', 'trending', 'gov_slider', 'travel_slider', 'our_services', 'features', 'partners', 'track', 'blogs'];
        foreach ($homepage_keys as $hk) {
            if (isset($_POST['homepage_label_' . $hk])) {
                $lbl = trim($_POST['homepage_label_' . $hk]);
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
                $chk->execute(['homepage_label_' . $hk]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")->execute([$lbl, 'homepage_label_' . $hk]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")->execute(['homepage_label_' . $hk, $lbl]);
                }
            }
            
            if (isset($_POST['homepage_title_' . $hk])) {
                $title = trim($_POST['homepage_title_' . $hk]);
                $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
                $chk->execute(['homepage_title_' . $hk]);
                if ($chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")->execute([$title, 'homepage_title_' . $hk]);
                } else {
                    $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")->execute(['homepage_title_' . $hk, $title]);
                }
            }
            
            // Handle logo file upload
            $file_name = 'homepage_logo_file_' . $hk;
            if (isset($_FILES[$file_name]) && $_FILES[$file_name]['error'] === UPLOAD_ERR_OK) {
                $upDir = '../uploads/homepage/';
                if (!is_dir($upDir)) mkdir($upDir, 0755, true);
                
                $logoName = handleSecureUpload($_FILES[$file_name], $upDir, ['jpg','jpeg','png','webp','gif','svg'], 'sec_logo_'.$hk);
                if ($logoName) {
                    $logoPath = 'uploads/homepage/' . $logoName;
                    
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM homepage_settings WHERE setting_key = ?");
                    $chk->execute(['homepage_logo_path_' . $hk]);
                    if ($chk->fetchColumn() > 0) {
                        $pdo->prepare("UPDATE homepage_settings SET setting_value = ? WHERE setting_key = ?")->execute([$logoPath, 'homepage_logo_path_' . $hk]);
                    } else {
                        $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")->execute(['homepage_logo_path_' . $hk, $logoPath]);
                    }
                }
            }
        }
        $msg = 'Homepage customization settings saved successfully.';
    }

    // Reload settings
    $settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
}

$staff = $isAdmin ? $pdo->query("SELECT id,username,role,created_at FROM staff_users ORDER BY role,id")->fetchAll() : [];
$chatCount = (int)$pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status='with_agent'")->fetchColumn();

// Quick stats
$agentCount  = (int)$pdo->query("SELECT COUNT(*) FROM agents WHERE status='active'")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM agents WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin Settings — Fast Site</title>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <h2 class="admin-hero-title">⚙️ SYSTEM ADVANCED SETTINGS</h2>
    <p class="admin-hero-subtitle">Manage branding, API keys, chatbot names, staff controls &amp; system configurations</p>
    <div class="tabs-nav" id="tabs-nav" style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:1rem;">
      <button type="button" class="tab-btn active" id="btn-tab-api" onclick="switchSettingsSection('sec-api')">🔑 Super Admin API &amp; Keys</button>
      <button type="button" class="tab-btn" id="btn-tab-gen" onclick="switchSettingsSection('sec-general')">⚙️ General &amp; SEO</button>
      <button type="button" class="tab-btn" id="btn-tab-logo" onclick="switchSettingsSection('sec-logo-banner-media')">🖼️ Logo, Banners &amp; Media</button>
      <button type="button" class="tab-btn" id="btn-tab-market" onclick="switchSettingsSection('sec-marketplace-config')">💳 Marketplace &amp; Gateways</button>
      <button type="button" class="tab-btn" id="btn-tab-part" onclick="switchSettingsSection('sec-all-partners-program')">🤝 Partner Programs</button>
      <button type="button" class="tab-btn" id="btn-tab-staff" onclick="switchSettingsSection('sec-staff')">🛡️ Staff Access &amp; Security</button>
      <button type="button" class="tab-btn" id="btn-tab-adv" onclick="switchSettingsSection('sec-advance-settings')">⚡ Advanced System</button>
    </div>
  </div>

  <?php if($msg): ?><div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="tabs-content" id="tabs-content">
      <!-- THE SUPER ADMIN / HEADLESS API TAB -->
      <div class="section-card active-tab" id="sec-api">
          <div class="section-head"><h2>🔑 SUPER ADMIN API &amp; KEYS</h2></div>
          <div class="section-body" style="padding:1.5rem;">
              <div class="input-group" style="margin-bottom:1.5rem;">
                  <label style="font-weight:700; color:var(--gold); display:block; margin-bottom:0.4rem;">Master API Key (For Ayra Mart, Enzor Motor, etc.)</label>
                  <div style="display:flex; gap:0.5rem;">
                    <input type="text" id="master-api-key" readonly value="<?= htmlspecialchars("FS_MASTER_" . hash('sha256', 'super_secret_fast_site_key')) ?>" style="background: rgba(0,0,0,0.5); border: 1px solid rgba(252,185,0,0.35); color: var(--gold); font-family: monospace; font-weight:700; padding:0.75rem; border-radius:8px; flex:1;">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('master-api-key').value); alert('API Key copied to clipboard!');" class="btn-sm" style="padding:0.75rem 1.2rem; font-weight:800; cursor:pointer;">📋 Copy Key</button>
                  </div>
                  <small style="color:#94a3b8; font-size:0.75rem; display:block; margin-top:0.3rem;">Copy this key and paste it into the `config.php` or Admin panel of your 7 ecosystem partner websites to securely sync catalog products and route messages.</small>
              </div>
              <div class="input-group" style="margin-bottom:1.5rem;">
                  <label style="font-weight:700; color:#fff; display:block; margin-bottom:0.4rem;">API Feed Endpoint</label>
                  <input type="text" readonly value="https://fastsite.best-travel.ltd/api/master_feed.php?shop_name=AYRA_MART&amp;api_key=..." style="background: rgba(0,0,0,0.4); border: 1px solid var(--border); color: #cbd5e1; font-family: monospace; padding:0.75rem; border-radius:8px; width:100%; box-sizing:border-box;">
              </div>
              <div class="input-group">
                  <label style="font-weight:700; color:#fff; display:block; margin-bottom:0.4rem;">Webhook Messenger Endpoint</label>
                  <input type="text" readonly value="https://fastsite.best-travel.ltd/api/webhook_messenger.php" style="background: rgba(0,0,0,0.4); border: 1px solid var(--border); color: #cbd5e1; font-family: monospace; padding:0.75rem; border-radius:8px; width:100%; box-sizing:border-box;">
              </div>
          </div>
      </div>

      <?php include __DIR__ . '/settings_partials/tab_general.php'; ?>
      <?php include __DIR__ . '/settings_partials/tab_logo_media.php'; ?>
      <?php include __DIR__ . '/settings_partials/tab_marketplace.php'; ?>
      <?php include __DIR__ . '/settings_partials/tab_partners.php'; ?>
      <?php include __DIR__ . '/settings_partials/tab_staff.php'; ?>
      <?php include __DIR__ . '/settings_partials/tab_advanced.php'; ?>
  </div>

  <script>
  function switchSettingsSection(sectionId) {
    document.querySelectorAll('.section-card').forEach(c => {
      c.classList.add('collapsed');
      c.classList.remove('active-tab');
      c.style.display = 'none';
    });
    document.querySelectorAll('.tabs-nav .tab-btn').forEach(b => b.classList.remove('active'));

    const activeCard = document.getElementById(sectionId);
    if (activeCard) {
      activeCard.classList.remove('collapsed');
      activeCard.classList.add('active-tab');
      activeCard.style.display = 'block';
    }

    if (window.event && window.event.currentTarget) {
      window.event.currentTarget.classList.add('active');
    }
  }

  function toggleSection(secId) {
    const sec = document.getElementById(secId);
    if (sec) {
      sec.classList.toggle('collapsed');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    switchSettingsSection('sec-api');
    const firstBtn = document.getElementById('btn-tab-api');
    if (firstBtn) firstBtn.classList.add('active');
  });
  </script>
</div>

<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>
