# Worker M2 Fix Workspace
Target: Remediate M2 Review Items
Target files: partner/orders.php, partner/nav.php
Remediation items:
1. partner/orders.php line 398: Remove duplicate `<` from `<<div class="content-wrapper">` -> `<div class="content-wrapper">`.
2. partner/nav.php: In mobile media query `@media (max-width: 900px)`, set `.side-drawer { bottom: 62px; }` so the drawer terminates cleanly above the 62px bottom dock without occluding the bottom Logout link.
