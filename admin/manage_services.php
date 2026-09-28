<?php
// =========================================================================
// admin/manage_services.php – Redirect to Official Fast Site Shop Manager
// =========================================================================
session_start();
header('Location: impersonate_official.php?redirect=product_add.php');
exit;
