# Implementation Plan - Universal AI Config Setup

**Status:** Draft | **Date:** 2026-03-14 | **Task ID:** Universal_AI_Config_Setup

---

## 💡 Latest Comments/Suggestions (20260314_@0206)
- Initiate the transition from legacy `.agent/` and global `.gemini/` configurations to the new `.ai/` Universal Standard.
- Implement symlinks for Cursor, Gemini, and Claude to ensure a single source of truth (`SOUL.md`).
- Centralized skills and workflows for cross-editor accessibility.

---

## 🛠️ Feature Implementation Sections

### 1. Directory Structure Initialization (20260314_@0208)
- [x] Create missing directories within `.ai/`:
    - `.ai/skills/`
    - `.ai/workflows/`
    - `.ai/rules/`
- [x] Ensure `.ai/README.md`, `.ai/SOUL.md`, and `.ai/AGENTS.md` are correctly placed.

### 2. Skill Migration & Pathing (20260314_@0210)
- [x] Copy `VAPTSchema-Builder` skill from `.agent/skills/` to `.ai/skills/vaptschema-builder/`.
- [x] Copy `wordpress-vapt-expert` skill from global `.gemini/` to `.ai/skills/vapt-expert/`.
- [x] Update any internal references in `SKILL.md` files to reflect the new structure.

### 3. Workflow & Rule Consolidation (20260314_@0212)
- [x] Move `.agent/workflows/*.md` to `.ai/workflows/`.
- [x] Consolidate legacy `.agent/rules/*.agrules` into `.ai/SOUL.md` or organize them in `.ai/rules/` if they are editor-specific.
- [x] Set up editor-specific rule files in `.ai/rules/`:
    - `.ai/rules/cursor.rules` (Symlink to `../SOUL.md`)
    - `.ai/rules/gemini.md` (Symlink to `../SOUL.md`)

### 4. Cross-Editor Compatibility (Symlinks) (20260314_@0215)
- [x] Create `.cursor/` directory:
    - [x] Link `.cursor/skills/` to `../../.ai/skills/`.
    - [x] Link `.cursor/.cursorrules` to `../.ai/rules/cursor.rules`.
- [x] Create `.gemini/` directory:
    - [x] Link `.gemini/antigravity/skills/` to `../../../.ai/skills/`.
    - [x] Link `.gemini/antigravity/rules/` to `../../../.ai/rules/`.
- [x] Create `.claude/` directory:
    - [x] Link `.claude/skills/` to `../../.ai/skills/`.

### 5. Verification & Cleanup (20260314_@0218)
- [x] Verify symlinks are working correctly in the local environment.
- [x] Ensure all AI agents (Antigravity) can still access the skills and rules.
- [x] Remove legacy `.agent/` directory once migration is fully verified (optional/pending user approval).

---

## 📜 Revision History / Changelog

### [20260314_@0206] - Initial Plan
- Created implementation plan for Universal AI Config Pattern.
- Identified core tasks: Directory creation, Skill migration, Symlink setup.

### [20260314_@0440] - Plan Execution Completed
- Created the `.ai` folder structure and its respective directories.
- Copied all VAPT skills and workflows to `.ai/skills` and `.ai/workflows`.
- Set up hardlinks and junctions for `.cursor`, `.gemini`, and `.claude` cross-editor support.
- Updated `.agent` references to `.ai` in the transferred `.agrules` workflow files.
- Completed verification and removed the legacy `.agent` directory.
