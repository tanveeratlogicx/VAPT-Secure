---
description: Bump version, update history, and commit changes to Git/GitHub
---

# Build Bump & Sync Workflow

This workflow automates the process of bumping the plugin version, updating version history, and syncing changes to Git and GitHub.

## 1. Determine Bump Type
Choose the appropriate bump type based on changes:
- **patch**: Bug fixes, minor tweaks, CSS/JS adjustments.
- **minor**: New features, new UI components, new REST endpoints.
- **major**: Breaking changes, architecture overhaul.

## 2. Execute Version Bump
// turbo
1. Run the bump script (replace `patch` with `minor` or `major` if needed):
   `powershell -ExecutionPolicy Bypass -File tools/bump-version.ps1 patch`

## 3. Update Version History
2. Open `VERSION_HISTORY.md` and add a new entry for the new version.
3. Summarize the changes made in this build.

## 4. Git Commit & Push
This step commits all modified and untracked files (excluding temporary files) with a descriptive message.

// turbo
4. Stage changes:
   `git add .`

5. Create commit with details of modified files:
   `git commit -m "Build: Bump version to $(Get-Content vaptsecure.php | Select-String 'VAPTSECURE_VERSION' | ForEach-Object { $_.ToString().Split("'")[3] }) - Syncing modifications"`

// turbo
6. Push to GitHub:
   `git push origin main`

## 📋 Requirements
- Follows **Semantic Versioning (SemVer)**.
- Updates `vaptsecure.php` (Plugin Header & Constant).
- Updates `VERSION_HISTORY.md`.
- Commits all relevant modified/untracked files.
- Excludes temporary files via `.gitignore`.
