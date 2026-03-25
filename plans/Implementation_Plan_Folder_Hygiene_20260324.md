# Implementation Plan — Folder Hygiene Workflow

> **Task**: Clean up plugin root folder and establish a workflow rule to maintain cleanliness going forward.

---

## Latest Updates

### 20260324_@1644 — Active Session Exception ✅

**Status**: COMPLETE

**What was done:**
1. **Restored 17 test/debug/search scripts** back to root — all were created today and part of an active debugging session
2. **Updated workflow** with "Active Session Exception": `test-*`, `debug-*`, `search-*` files < 24 hours old stay in root
3. **Only 3 truly stale files remain in `Debug/`**: `phpcs_summary.txt`, `phpcs_vapt_functions.txt`, `vapt-debug.txt`
4. **Plan files are NOT exempt** — `Implementation_Plan_*.md` and `Fix-*.md` always go directly to `plans/`

**Decision**: User clarified that test files created in the last few hours should stay in root since they may be linked to ongoing processes.

---

### 20260324_@1636 — Initial Implementation ✅

**Status**: COMPLETE (revised by @1644)

**What was done:**
1. **Created `/Debug/` folder** — new subfolder for all test/debug/search scripts
2. **Moved 19 files → `plans/`** — all `Implementation_Plan_*.md`, `Fix-*.md`, `vapt_fix_*.md`, `vapt_finalize_*.md`
3. **Moved 20 files → `Debug/`** — all `test-*.js`, `test-*.php`, `debug-*.js`, `search-*.js`, `DEBUG-MODE.md`, `vapt-debug.txt`, `phpcs_*.txt`
4. **Created workflow** at `.agents/workflows/folder-hygiene.md` — defines patterns for routing files to the correct subfolder
5. **Root now contains only 7 legitimate files**: `vaptsecure.php`, `vapt-functions.php`, `vapt-hermasnet-config-1.0.1.php`, `README.md`, `CLAUDE.md`, `VERSION_HISTORY.md`, `LICENSE`

**Files preserved in root** (by design):
- `CLAUDE.md` — AI configuration document
- `README.md` — Project readme
- `VERSION_HISTORY.md` — Version changelog
- `LICENSE` — License file
- Core `.php` plugin files

**Decision**: User explicitly requested `CLAUDE.md`, `README.md` and similar project-level docs to remain in root.

---

## Revision History

| Date | Change | Author |
|------|--------|--------|
| 20260324_@1644 | Added active session exception — restored 17 recent test/debug files to root, updated workflow | AI Agent |
| 20260324_@1636 | Initial implementation — moved 39 files, created workflow | AI Agent |
