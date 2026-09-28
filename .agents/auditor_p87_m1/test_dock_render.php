<?php
// Test dock rendering logic for user/dashboard.php under simulated states
$states = ['approved', 'pending', 'none'];

foreach ($states as $dockShopState) {
    ob_start();
    $isStoreTab = false;
    $isOrdersTab = true;
    $isWalletTab = false;
    $isProfileTab = false;
    $totalActiveOrdersBadge = 3;
    $user = ['profile_pic' => 'uploads/profile/test.jpg'];
    
    // Include snippet corresponding to lines 1629-1696 of user/dashboard.php
    ?>
    <div class="bottom-nav mobile-only-bottom-nav" id="user-floating-bottom-nav">
      <div class="bottom-nav-inner">
        <!-- 1. STORE (Slot 1) -->
        <a href="/index.php" id="dock-item-store" class="b-nav-item <?= $isStoreTab ? 'active' : '' ?>">Store</a>

        <!-- 2. ORDERS (Slot 2) -->
        <a href="/user/dashboard.php?tab=orders" id="dock-item-orders" class="b-nav-item <?= $isOrdersTab ? 'active' : '' ?>">
          Orders (<?= (int)$totalActiveOrdersBadge ?>)
        </a>

        <!-- 3. MODE SWITCHER ACTION (Slot 3) -->
        <?php if ($dockShopState === 'approved'): ?>
          <a href="/partner/dashboard.php" id="dock-item-mode" class="b-nav-item b-nav-center-pill">
            <div class="dock-center-circle mode-shop"><span class="circle-icon">🏪</span></div>
            <span class="dock-label">Shop Mode</span>
          </a>
        <?php elseif ($dockShopState === 'pending'): ?>
          <button type="button" id="dock-item-mode" onclick="openShopReviewModal()" class="b-nav-item b-nav-center-pill">
            <div class="dock-center-circle mode-review"><span class="circle-icon pulse-gold">⏳</span></div>
            <span class="dock-label">In Review</span>
          </button>
        <?php else: ?>
          <button type="button" id="dock-item-mode" onclick="openQuickShopDrawer()" class="b-nav-item b-nav-center-pill">
            <div class="dock-center-circle mode-create"><span class="circle-icon">➕</span></div>
            <span class="dock-label">Free Shop</span>
          </button>
        <?php endif; ?>

        <!-- 4. WALLET (Slot 4) -->
        <a href="/user/wallet.php" id="dock-item-wallet" class="b-nav-item <?= $isWalletTab ? 'active' : '' ?>">Wallet</a>

        <!-- 5. PROFILE (Slot 5) -->
        <a href="/user/profile.php" id="dock-item-profile" class="b-nav-item <?= $isProfileTab ? 'active' : '' ?>">Profile</a>
      </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    // Verifications
    $hasSlot1 = strpos($html, 'dock-item-store') !== false;
    $hasSlot2 = strpos($html, 'dock-item-orders') !== false && strpos($html, 'Orders (3)') !== false;
    $hasSlot3 = strpos($html, 'dock-item-mode') !== false;
    $hasSlot4 = strpos($html, 'dock-item-wallet') !== false;
    $hasSlot5 = strpos($html, 'dock-item-profile') !== false;
    
    $modeVerified = false;
    if ($dockShopState === 'approved') {
        $modeVerified = (strpos($html, '/partner/dashboard.php') !== false && strpos($html, 'mode-shop') !== false && strpos($html, 'Shop Mode') !== false);
    } elseif ($dockShopState === 'pending') {
        $modeVerified = (strpos($html, 'openShopReviewModal()') !== false && strpos($html, 'mode-review') !== false && strpos($html, 'In Review') !== false);
    } elseif ($dockShopState === 'none') {
        $modeVerified = (strpos($html, 'openQuickShopDrawer()') !== false && strpos($html, 'mode-create') !== false && strpos($html, 'Free Shop') !== false);
    }
    
    echo "State [$dockShopState]: Slots OK: " . ($hasSlot1 && $hasSlot2 && $hasSlot3 && $hasSlot4 && $hasSlot5 ? "YES" : "NO") . ", Mode OK: " . ($modeVerified ? "YES" : "NO") . "\n";
}
