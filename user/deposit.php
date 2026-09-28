<?php
// =========================================================================
// user/deposit.php – Redirect to Unified Wallet Deposit Hub
// =========================================================================
session_start();
header('Location: /user/wallet.php?action=deposit');
exit;
