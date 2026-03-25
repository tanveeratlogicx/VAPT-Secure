# Implementation Plan: Tooltip Ambiguity Resolution

**Task Name:** Resolving Technical Implementation Confirmation Ambiguity
**Date Started:** 2026-03-16
**Latest Comments/Suggestions:**
* The Technical Implementation Confirmation tooltip was displaying ambiguous aggregated info (like "wp-config / PHP Hook (Adaptive)" combined with extra PHP code).
* The user requested to see the exact Code/Rule being inserted with the specific file name (which is being actually injected/removed) to physically verify it.
* Eliminate the manual overwrite of the strings.

---

## Revision History / Changelog

### 20260316_@2315 - Review: Stripping Ambiguous Tooltip Overrides

**Objective:**
Remove the forced fallback tooltip information for wp-config targets that combined both the primary target and fallback hook behavior into a single tooltip entry. This ensures the output is precise, showing only the exact file targeted and the exact code injected.

**Actions Taken:**
1. Modified `assets/js/modules/generated-interface.js`:
    - Removed the logic that overrides `displayName` to "wp-config / PHP Hook (Adaptive)" and `target` to "wp-config.php / Hook Driver".
    - Removed the automatic appending of `add_action("init", "block_wp_cron", 1);` for Superadmins.
    - Updated the standard `mapping` rendering block to just show "wp-config.php" instead of "wp-config.php / Hook Driver (Adaptive)".
2. Modified `assets/js/modules/aplus-generator.js`:
    - Removed the equivalent logic for the A+ Generator preview block so that preview matches the exact code injected.

**Status:** Completed. The Technical Implementation Confirmation tooltip now displays exactly what is injected into the specific target without abstract architectural summaries.
