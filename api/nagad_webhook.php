<?php
// =========================================================================
// api/nagad_webhook.php — Dedicated Nagad IPN Webhook Endpoint
// Delegates to master universal MFS engine with idempotency & audit logging
// =========================================================================
if (!isset($_GET['provider'])) {
    $_GET['provider'] = 'nagad';
}
require_once __DIR__ . '/mfs_webhook.php';
