## 2026-09-09T15:35:07Z
You are worker_p87_m3, working on Phase 87 Milestone 3: Google Stitch Polish, Verification & Deployment Packaging.

## Working Directory
`d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m3/`

## Mandatory Reading & Directives
- Authoritative User Request: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md`
- Master Project Specification: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT.md`
- Project State: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT_STATE.md`
- Mandatory Directives: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/AGENTS.md`

## MANDATORY INTEGRITY WARNING
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Milestone 3 Scope & Deliverables

### 1. Google Stitch *Nocturne Aurum* Standards Verification:
- Verify that both User Panel (`includes/user_sidebar.php`, `user/dashboard.php`, `assets/css/user.css`) and Shop Panel (`partner/nav.php`, `partner/dashboard.php`) uniformly adhere to Google Stitch *Nocturne Aurum* tokens:
  - Deep obsidian background: `#0A0D1A`
  - High-density frosted glass elevation: `rgba(18, 22, 43, 0.85)` / `rgba(10, 13, 26, 0.94)` with `backdrop-filter: blur(16px–24px)`
  - Amber/gold highlights: `#F59E0B` / `#fcb900`
  - Sky blue Buyer Mode accents: `#38bdf8`
  - Fluid micro-interactions with active tap compression: `transform: scale(0.96)`
  - Guaranteed minimum touch targets: 44px × 44px across all buttons, pills, and dock items
  - Hardware notch safe-area clearance: `env(safe-area-inset-top)` and `env(safe-area-inset-bottom)`

### 2. Comprehensive Code & Encoding Audit:
- Execute `php -l` on all files modified in Phase 87:
  - `includes/user_sidebar.php`
  - `user/dashboard.php`
  - `assets/css/user.css`
  - `partner/nav.php`
  - `partner/dashboard.php`
  - `partner/index.php`
  - `partner/logout.php`
  - `partner/product_add.php`
  - `partner/product_edit.php`
  - `partner/product_delete.php`
  - `partner/profile.php`
  - `partner/api_docs.php`
- Verify 100% pure UTF-8 encoding (no BOM, no mojibake, leading bytes `3C 3F 70` on PHP files).

### 3. Live Server Testing:
- Test Local Server Live Host (`http://localhost:8000`):
  - Request `/index.php` (verify HTTP 200)
  - Request `/partner/dashboard.php` (verify HTTP 302 to `/user/login.php`)
  - Request `/partner/index.php` (verify HTTP 302 to `/user/login.php`, zero 404s)
  - Request `/user/dashboard.php` (verify HTTP 302 to `/user/login.php`)
  - Request `/user/login.php` (verify HTTP 200)
- Test Cloudflare mobile tunnel (`https://zoo-dubai-hopefully-note.trycloudflare.com`) via curl.

### 4. Create `DEPLOYMENT_GUIDE.txt`:
Per Mandatory Directives (`.agents/AGENTS.md`):
- Name: `DEPLOYMENT_GUIDE.txt` placed in root and inside `fastsite_phase87.zip`.
- Must explicitly state:
  1. Exact folder paths on Hostinger where files go (`public_html/`).
  2. Exact workflow step where the AI left off (Phase 87 Complete).
  3. Current active Phase number (Phase 87).
  4. Local Server Live Host reminder: Remind user to turn on Local Server Live Host (`http://localhost:8000`) to test changes before live deployment.
  5. Complete file manifest with checksums/sizes.

### 5. Build `fastsite_phase87.zip`:
- Build `fastsite_phase87.zip` in project root containing:
  - `includes/user_sidebar.php`
  - `user/dashboard.php`
  - `assets/css/user.css`
  - `partner/nav.php`
  - `partner/dashboard.php`
  - `partner/index.php`
  - `partner/logout.php`
  - `partner/product_add.php`
  - `partner/product_edit.php`
  - `partner/product_delete.php`
  - `partner/profile.php`
  - `partner/api_docs.php`
  - `DEPLOYMENT_GUIDE.txt`
- Ensure all zip entries use normalized Unix forward slash `/` paths (no Windows backslashes).
- Verify archive integrity and file listing.

### 6. Update `PROJECT_STATE.md`:
Per Rule 2 of `.agents/AGENTS.md`, update `PROJECT_STATE.md`:
- Mark Phase 87 as 100% COMPLETE.
- Document all deliverables across Milestone 1, Milestone 2, and Milestone 3.

### 7. Documentation & Handoff:
- Write comprehensive handoff report to `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m3/handoff.md`.
- Send completion message to orchestrator.
