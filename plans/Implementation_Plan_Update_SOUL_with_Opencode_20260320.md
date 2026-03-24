# Implementation Plan - Update SOUL.md with .opencode Support

**Task ID**: `SOUL_OPENCODE_SUPPORT`
**Date**: 2026-03-20
**Status**: Completed

## 📝 Overview

The goal is to update the `SOUL.md` file (Universal AI Configuration) to include support and documentation for the `.opencode` (OpenCode ECC Plugin) directory and tool. This ensures that all AI agents, including OpenCode, adhere to the same "single source of truth" defined in `SOUL.md`.

## 🛠️ Proposed Changes

### 1. Update Editor / Extension Registry

Add OpenCode to the main identification table at the top of `SOUL.md`.

### 2. Update Key Directories

Add `.opencode` to the Key Directories table in the Project Context section.

### 3. Update Symlink Registry

Include `.opencode` in the file tree and symlink explanation section.

### 4. Integration Details

Ensure OpenCode instructions/prompts point to or are informed by `SOUL.md`.

---

## 📅 Changelog / Revision History

### 20260320_@1745 - Initial Plan Creation
- Defined the scope of updates for `SOUL.md` to include `.opencode`.
- Identified specific sections in `SOUL.md` requiring modification.

### 20260320_@1755 - Implementation Completed
- Updated `SOUL.md` with registry, directory, and symlink info.
- Created hardlinks for `.ai/rules/opencode.md` and `.opencode/instructions/SOUL.md`.
- Updated `opencode.json` to load `instructions/SOUL.md`.

---

## 🚀 Implementation Steps

### Step 1: Modify SOUL.md - Registry Tables
**Timestamp**: 20260320_@1746
- Add OpenCode to the Editor/Extension table.
- Add `.opencode` to the Key Directories table.
- **Status**: ✅ Completed

### Step 2: Modify SOUL.md - Symlink Registry
**Timestamp**: 20260320_@1747
- Add `.opencode` section to the Symlink Registry tree and detailed list.
- **Status**: ✅ Completed

### Step 3: Functional Integration
**Timestamp**: 20260320_@1752
- Created hardlinks to `SOUL.md` in `.ai/rules/` and `.opencode/instructions/`.
- Updated `opencode.json` instructions.
- **Status**: ✅ Completed

### Step 4: Verification
**Timestamp**: 20260320_@1756
- Review `SOUL.md` for consistency.
- Ensure all paths are correct.
- **Status**: ✅ Completed
