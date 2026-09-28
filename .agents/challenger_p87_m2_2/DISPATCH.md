## 2026-09-09T15:16:17Z
You are challenger_p87_m2_2, an adversarial DOM & component stress tester for Phase 87 Milestone 2.

## Working Directory
`d:/TECH/WEBSITE/FAST SITE/fast site/.agents/challenger_p87_m2_2/`

## Mandatory Reading
- Authoritative User Request: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md`
- Master Project Specification: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT.md`
- Project State: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT_STATE.md`
- Worker Handoff Report: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m2_exec/handoff.md`

## Adversarial Stress Testing Tasks
1. Write and run a standalone test script to simulate rendering of `partner/nav.php` under various conditions:
   - Case A: `$current_page = 'dashboard.php'` -> verify back button is NOT rendered.
   - Case B: `$current_page = 'orders.php'` -> verify back button IS rendered and links to `dashboard.php`.
   - Case C: `$current_page = 'products.php'` -> verify back button IS rendered.
   - Case D: `$partner_pending_orders_count = 0` vs `> 0` vs `> 99` -> verify badge behavior.
2. Verify bottom dock structure:
   - Exactly 5 slots present: Hub, Orders, + Add, Catalog, Buyer Mode.
   - Verify Slot 5 links to `/user/dashboard.php`.
   - Verify Slot 5 has class `dock-item-buyer`.
   - Verify center slot has class `dock-item-primary`.
3. Check CSS rules in `partner/nav.php`:
   - Check `@media (max-width: 600px)` text collapse.
   - Check touch target dimensions (`min-height: 44px; min-width: 44px;`).
   - Check safe-area insets syntax (`env(safe-area-inset-bottom, 0px)`).
4. Document all test results and output explicit verdict (APPROVE or REQUEST_CHANGES) in `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/challenger_p87_m2_2/handoff.md`. Send completion message to orchestrator.
