# Fix "Include Active Data" Markdown Block

**Task ID:** Fixing-Data-Directory-Markdown-Inclusion
**Date:** 2026-03-02
**Version:** 1.0

## Latest Comments/Suggestions (YYYYMMDD_@HHMM)

- **20260302_@0050** - Initial plan created to fix markdown file exclusion in data directory.

---

## Summary

The "Download Build" functionality on the Build Generator tab respects the "Include Active Data" toggle by filtering which `json` files get bundled in the `data/` directory. However, a global restriction meant to exclude general markdown files (like `README.md`) implicitly excludes all `md` files in the `data/` directory.

## Proposed Changes

### VAPT-Secure/includes/class-vaptsecure-build.php

Modify `copy_plugin_files` to prevent the global `.md` blocker from applying to files explicitly located in the `data/` directory.

This allows the 'data/' directory specific logic later in the function to correctly bundle the `.md` files alongside the `.json` files.

## Verification

Upon user approval, I will execute the PHP change and manually confirm the generated ZIP file respects these conditions.
