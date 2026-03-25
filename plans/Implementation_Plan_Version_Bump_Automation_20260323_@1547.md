# Implementation Plan: Version Bump Automation

## Status: Completed
## Latest Comments / Suggestions
- **20260323_@1547**: User reported that previous version bump rules/workflows never worked reliably. Root cause: versions were stored in 2 locations in `vaptsecure.php` and AI agents would update one but not the other, or use wrong regex. Solution: a standalone PowerShell script that atomically updates both locations.

## Root Cause Analysis

| Problem | Detail |
|---------|--------|
| **Dual version locations** | Plugin header (`* Version: X.Y.Z`) and PHP constant (`VAPTSECURE_VERSION`) both need updating |
| **Version drift** | Header was `2.5.9`, constant was `2.5.8` — already out of sync |
| **AI agent inconsistency** | Different IDEs/extensions would apply different regex patterns, often missing one location |
| **No verification** | No post-update check to confirm both locations matched |

## Solution: Standalone Script

Created `VAPT-Secure/tools/bump-version.ps1` — a self-contained PowerShell script that:

1. Reads the current version from `VAPTSECURE_VERSION` constant (single source of truth)
2. Calculates the new version based on semver bump type
3. Updates BOTH locations atomically in one write
4. Verifies both locations match after the update
5. Reports success/failure with clear color-coded output

## Feature Implementation

### 1. Create bump-version.ps1 [20260323_@1547] - COMPLETED
- **File**: `VAPT-Secure/tools/bump-version.ps1`
- **Supports**: `patch`, `minor`, `major`, `set X.Y.Z`
- **Verification**: Built-in post-update check

### 2. Create Workflow [20260323_@1547] - COMPLETED
- **File**: `.ai/workflows/bump-version.md`
- **Details**: Documents usage, when to bump, and important notes
- **Turbo**: All steps marked `// turbo` for auto-execution

### 3. Fix Existing Version Drift [20260323_@1547] - COMPLETED
- **Before**: Header = `2.5.9`, Constant = `2.5.8`
- **After**: Both = `2.5.9`
- **Command**: `bump-version.ps1 set 2.5.9`

## Usage Examples

```powershell
# Patch bump (bug fix)
powershell -ExecutionPolicy Bypass -File tools/bump-version.ps1 patch

# Minor bump (new feature)
powershell -ExecutionPolicy Bypass -File tools/bump-version.ps1 minor

# Major bump (breaking change)
powershell -ExecutionPolicy Bypass -File tools/bump-version.ps1 major

# Set exact version
powershell -ExecutionPolicy Bypass -File tools/bump-version.ps1 set 3.0.0
```

---
**Timestamp (GMT+5): 20260323_@1547**
