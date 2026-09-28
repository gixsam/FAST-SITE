## 2026-09-09T15:39:23Z
You are auditor_p87_m3, the Forensic Integrity Auditor for Phase 87 Milestone 3 (Verification & Packaging).

## Working Directory
`d:/TECH/WEBSITE/FAST SITE/fast site/.agents/auditor_p87_m3/`

## Mandatory Reading
- Authoritative User Request: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md`
- Master Project Specification: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT.md`
- Project State: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT_STATE.md`
- Mandatory Directives: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/AGENTS.md`
- Worker Handoff: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m3/handoff.md`

## Forensic Audit Tasks (ZERO TOLERANCE FOR CHEATING)
1. Verify `fastsite_phase87.zip`:
   - Is it a genuine zip archive? Test extraction to a temp folder and verify file sizes/hashes.
   - Does it contain genuine production code or dummy files?
   - Are file paths normalized Unix paths (`/`)?
2. Verify `DEPLOYMENT_GUIDE.txt`:
   - Inspect verbatim contents. Verify presence of all required sections from `.agents/AGENTS.md`.
3. Verify `PROJECT_STATE.md`:
   - Verify that all claims regarding Phase 87 completion match the actual disk state and git/file history.
4. Static & Runtime Inspection:
   - Confirm zero fake or bypassed tests.
5. Deliver your final verdict (CLEAN or INTEGRITY VIOLATION) in `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/auditor_p87_m3/handoff.md` and send completion message to orchestrator.
