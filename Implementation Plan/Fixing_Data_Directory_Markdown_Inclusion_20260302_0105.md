# Fix "Include Active Data" Markdown & JSON Exclusion

**Task ID:** Fixing-Data-Directory-Markdown-Inclusion
**Date:** 2026-03-02
**Version:** 1.1

## Latest Comments/Suggestions (YYYYMMDD_@HHMM)

- **20260302_@0105** - Revised plan after user review. Discovered that `.json` files were *also* excluded because the regex extracting JSON filenames from the `.md` content failed to account for periods (like `...v2.0.json`). Fixing both issues.
- **20260302_@0050** - Initial plan created to fix markdown file exclusion in data directory.

---

## Summary

The "Download Build" functionality respects the "Include Active Data" toggle, but a couple of logic bugs prevent the `data/` files from being bundled:

1. A global restriction meant to exclude general markdown files implicitly blocks all `.md` files in the `data/` directory.
2. The regex used to lazy-load allowed `.json` files from the `.md` contents is missing a period in its character class `[a-zA-Z0-9_\-]`. Therefore, files like `interface_schema_v2.0.json` only match as `0.json`, causing the `in_array` check to fail and block all `.json` files.

## Proposed Changes

### VAPT-Secure/includes/class-vaptsecure-build.php

Modify `copy_plugin_files` to fix the two issues:

1. **Fix Markdown Exclusion (Line ~180):** Prevent the global `.md` blocker from applying to files explicitly located in the `data/` directory.
2. **Fix JSON Extraction Regex (Line ~211):** Update the regex block from `preg_match_all('/([a-zA-Z0-9_\-]+\.json)/i', ...)` to `preg_match_all('/([a-zA-Z0-9_\-\.]+\.json)/i', ...)`.

## Verification

Upon user approval, I will execute the PHP changes and manually confirm the generated ZIP file respects these conditions and includes both the `.md` and `.json` data files.
