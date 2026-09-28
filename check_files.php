<?php
// Prevent cron outputs from polluting the page
define('SKIP_AUTO_RELEASE', true);
$expected_files = [".htaccess", "DEPLOYMENT_GUIDE.txt", "PROJECT_STATE.md", "about.php", "admin/accounts.php", "admin/agents.php", "admin/ai_assistant.php", "admin/ajax_quick_product_add.php", "admin/analytics.php", "admin/api_gemini.php", "admin/api_partners.php", "admin/chat_reply.php", "admin/chat_view.php", "admin/chats.php", "admin/clean_hostinger_junk.php", "admin/coin_deposits.php", "admin/content_manager.php", "admin/dashboard.php", "admin/edit.php", "admin/empire_control.php", "admin/escrow.php", "admin/export_mass_payout.php", "admin/give_task_staff.php", "admin/gixsam_editor.php", "admin/guide.php", "admin/impersonate_official.php", "admin/index.php", "admin/internal_chats.php", "admin/login.php", "admin/logout.php", "admin/manage_directory.php", "admin/manage_services.php", "admin/manage_tags.php", "admin/messages.php", "admin/migrate_services.php", "admin/nav.php", "admin/network.php", "admin/network_products.php", "admin/network_sso.php", "admin/network_upload_api.php", "admin/omnichannel_hub.php", "admin/partner_deposits.php", "admin/partner_disputes.php", "admin/partner_requests.php", "admin/partner_settings.php", "admin/partner_shops.php", "admin/partner_withdrawals.php", "admin/payouts.php", "admin/restore_products.php", "admin/run_migrations.php", "admin/schema_phase20.sql", "admin/seed_ecosystem_shops.php", "admin/settings.php", "admin/staff_access.php", "admin/sync_hub.php", "admin/tasks.php", "admin/user_withdrawals.php", "admin/users.php", "admin/view_agent.php", "admin/wallet.php", "affiliate/dashboard.php", "api/app_bootstrap.php", "api/app_version.php", "api/bkash_webhook.php", "api/claim_mission_reward.php", "api/cron_escrow_autorelease.php", "api/customer_cancel_order.php", "api/customer_confirm.php", "api/gemini_bridge.php", "api/gemini_chat.php", "api/get_chatbot_settings.php", "api/get_notifications.php", "api/gixsam_content.php", "api/live_search.php", "api/marketplace_crosspost.php", "api/master_feed.php", "api/news_feed.php", "api/omni_search.php", "api/partner_accept_order.php", "api/partner_apply_role.php", "api/receive_crosspost.php", "api/shop_analytics.php", "api/subscribe_pro.php", "api/sync_credentials.php", "api/toggle_wishlist.php", "api/track_click.php", "api/update_profile.php", "api/upload_proof.php", "api/validate_coupon.php", "api/webhook_courier.php", "api/webhook_messenger.php", "api/webhook_stock.php", "assets/css/admin.css", "assets/css/admin-nav.css", "assets/css/mobile_responsive.css", "assets/css/user.css", "assets/images/default_avatar.png", "assets/images/fast_site_blue_shield_logo.png", "assets/images/fast_site_hq_admin_icon.jpg", "assets/images/fast_site_world_app_icon.jpg", "assets/images/logo.png", "assets/img/fast site logo only.jpeg", "assets/img/google-play.png", "assets/img/logo with letter.jpeg", "assets/img/logo.png", "assets/img/placeholder.png", "assets/js/global_loader.js", "assets/js/pull_to_refresh.js", "audit_services_sql.php", "check_db.php", "checkout.php", "clean_junk.php", "config.php", "directory.php", "download.php", "dropshop_api.php", "gemini_upload_api.php", "get_service_fee.php", "home.php", "includes/cropper_modal.php", "includes/escrow_engine.php", "includes/footer.php", "includes/image_helper.php", "includes/nav_public.php", "includes/user_sidebar.php", "includes/whatsapp_button.php", "index.php", "manifest.json", "marketplace_board.php", "partner/api_docs.php", "partner/bid_on_job.php", "partner/coupons.php", "partner/dashboard.php", "partner/disputes.php", "partner/earnings.php", "partner/index.php", "partner/logout.php", "partner/nav.php", "partner/orders.php", "partner/product_add.php", "partner/product_delete.php", "partner/product_edit.php", "partner/products.php", "partner/profile.php", "partner/return_to_admin.php", "partner/withdraw.php", "policy.php", "product_detail.php", "schema_sql_hostinger.sql", "setup_phase60.php", "shop.php", "shop_wrapper.php", "submit_order.php", "sw.js", "test_nav.php", "track.php", "track_order.php", "uploads/.last_escrow_release.txt", "uploads/audio/.gitkeep", "uploads/branding/logo.png", "uploads/chatbot-widget.js", "uploads/partners/fast_site_logo_new.png", "user/academy.php", "user/become_agent.php", "user/become_partner.php", "user/create_shop.php", "user/dashboard.php", "user/deposit.php", "user/forgot_password.php", "user/index.php", "user/login.php", "user/logout.php", "user/messages.php", "user/missions.php", "user/my_jobs.php", "user/notifications.php", "user/ocr_vault.php", "user/partner_orders.php", "user/place_order.php", "user/post_job.php", "user/profile.php", "user/register.php", "user/reset_password.php", "user/visa_apply.php", "user/wallet.php", "user/withdraw_coins.php"];
$expected_tables = ['users', 'partners', 'services', 'homepage_settings', 'escrow_vault', 'partner_orders', 'shop_reviews', 'user_notifications', 'coin_wallets'];

$results = [
    'db' => false,
    'tables' => [],
    'permissions' => [],
    'files_missing' => 0,
    'files_present' => 0
];

// 1. Check DB Connection
try {
    require_once 'config.php';
    if (isset($pdo)) {
        $results['db'] = true;
        // 2. Check Tables
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $q = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
                $existing_tables = $q->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $q = $pdo->query("SHOW TABLES");
                $existing_tables = $q->fetchAll(PDO::FETCH_COLUMN);
            }
            foreach ($expected_tables as $t) {
                $results['tables'][$t] = in_array($t, $existing_tables);
            }
        } catch (Exception $e) {}
    }
} catch (Exception $e) {}

// 3. Check Permissions
$upload_dir = __DIR__ . '/uploads';
$results['permissions']['uploads_writable'] = is_dir($upload_dir) && is_writable($upload_dir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Site Ultimate System Checker</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; background: #f0f2f5; color: #1c1e21; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #1a73e8; margin-bottom: 30px; }
        h2 { border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; margin-top: 30px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; font-weight: bold; border-left: 5px solid #1a73e8; }
        .card.success { border-left-color: #0f9d58; }
        .card.danger { border-left-color: #d93025; background: #fce8e6; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f1f3f4; position: sticky; top: 0; }
        .present { color: #0f9d58; font-weight: bold; font-size: 1.1em; }
        .missing { color: #d93025; font-weight: bold; font-size: 1.1em; }
        .row-missing { background-color: #fce8e6; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.9em; font-weight: bold; }
        .badge.bg-green { background: #e6f4ea; color: #137333; }
        .badge.bg-red { background: #fce8e6; color: #c5221f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Fast Site Ultimate Diagnostic Tool</h1>
        <p style="text-align:center; color:#555;">Validating Files, SQL Database, Permissions, and System Health.</p>
        
        <div class="summary-grid">
            <div class="card <?php echo $results['db'] ? 'success' : 'danger'; ?>">
                <div style="font-size:24px; margin-bottom:10px;">💾 Database (SQL)</div>
                <?php echo $results['db'] ? 'CONNECTED SUCCESSFULLY' : 'CONNECTION FAILED'; ?>
            </div>
            <div class="card <?php echo $results['permissions']['uploads_writable'] ? 'success' : 'danger'; ?>">
                <div style="font-size:24px; margin-bottom:10px;">📂 Uploads Folder</div>
                <?php echo $results['permissions']['uploads_writable'] ? 'WRITABLE' : 'READ-ONLY (Error)'; ?>
            </div>
            <div class="card" id="file_summary_card">
                <div style="font-size:24px; margin-bottom:10px;">📄 Source Files</div>
                <span id="present_count_display">...</span> / <?php echo count($expected_files); ?> Present
            </div>
        </div>

        <h2>🗄️ Core Database Tables</h2>
        <table>
            <tr><th>Table Name</th><th>Status</th></tr>
            <?php foreach ($expected_tables as $table): ?>
            <tr>
                <td><code><?php echo $table; ?></code></td>
                <td>
                    <?php if (!$results['db']): ?>
                        <span class="badge bg-red">DB DOWN</span>
                    <?php elseif (isset($results['tables'][$table]) && $results['tables'][$table]): ?>
                        <span class="badge bg-green">✔ INSTALLED</span>
                    <?php else: ?>
                        <span class="badge bg-red">✖ MISSING</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>📁 Full Project Files (Phase 1-60)</h2>
        <?php
        $table_rows = '';
        foreach ($expected_files as $file) {
            if (file_exists($file)) {
                $results['files_present']++;
                $status = '<span class="present">✔ YES</span>';
                $row_class = '';
            } else {
                $results['files_missing']++;
                $status = '<span class="missing">✖ MISSING</span>';
                $row_class = 'class="row-missing"';
            }
            $table_rows .= "<tr $row_class>
                <td><code>" . htmlspecialchars($file) . "</code></td>
                <td>$status</td>
            </tr>";
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>File Path</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $table_rows; ?>
            </tbody>
        </table>
        
        <script>
            document.getElementById('present_count_display').innerText = "<?php echo $results['files_present']; ?>";
            let card = document.getElementById('file_summary_card');
            if (<?php echo $results['files_missing']; ?> > 0) {
                card.classList.add('danger');
            } else {
                card.classList.add('success');
            }
        </script>
    </div>
</body>
</html>
