<?php
// =========================================================================
// user/withdraw_coins.php – Redirect to Unified Wallet Withdraw Hub
// =========================================================================
session_start();
header('Location: /user/wallet.php?action=withdraw');
exit;
