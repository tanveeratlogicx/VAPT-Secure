<#
.SYNOPSIS
    VAPT-Secure Version Bump Script
.DESCRIPTION
    Bumps the plugin version following semver (major.minor.patch).
    Updates ALL version locations in vaptsecure.php atomically.
    Usage:
      .\bump-version.ps1 patch   # 2.5.9 -> 2.5.10
      .\bump-version.ps1 minor   # 2.5.9 -> 2.6.0
      .\bump-version.ps1 major   # 2.5.9 -> 3.0.0
      .\bump-version.ps1 set 2.6.0  # Set to exact version
#>
param(
    [Parameter(Mandatory=$true, Position=0)]
    [ValidateSet("patch", "minor", "major", "set")]
    [string]$BumpType,

    [Parameter(Position=1)]
    [string]$ExactVersion
)

$ErrorActionPreference = "Stop"

# Resolve the plugin root (script lives in tools/ folder)
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$pluginRoot = Split-Path -Parent $scriptDir
$mainFile = Join-Path $pluginRoot "vaptsecure.php"

if (-not (Test-Path $mainFile)) {
    Write-Error "Cannot find vaptsecure.php at: $mainFile"
    exit 1
}

$content = Get-Content $mainFile -Raw

# Extract current version from the PHP constant (single source of truth)
if ($content -match "define\('VAPTSECURE_VERSION',\s*'(\d+\.\d+\.\d+)'\)") {
    $currentVersion = $Matches[1]
} else {
    Write-Error "Could not find VAPTSECURE_VERSION constant in vaptsecure.php"
    exit 1
}

Write-Host "Current version: $currentVersion" -ForegroundColor Cyan

# Calculate new version
$parts = $currentVersion.Split('.')
[int]$major = $parts[0]
[int]$minor = $parts[1]
[int]$patch = $parts[2]

switch ($BumpType) {
    "patch" {
        $patch++
    }
    "minor" {
        $minor++
        $patch = 0
    }
    "major" {
        $major++
        $minor = 0
        $patch = 0
    }
    "set" {
        if (-not $ExactVersion -or $ExactVersion -notmatch '^\d+\.\d+\.\d+$') {
            Write-Error "Usage: bump-version.ps1 set X.Y.Z"
            exit 1
        }
        $setParts = $ExactVersion.Split('.')
        $major = [int]$setParts[0]
        $minor = [int]$setParts[1]
        $patch = [int]$setParts[2]
    }
}

$newVersion = "$major.$minor.$patch"

if ($newVersion -eq $currentVersion) {
    Write-Host "Version is already $currentVersion. No changes made." -ForegroundColor Yellow
    exit 0
}

Write-Host "Bumping: $currentVersion -> $newVersion" -ForegroundColor Green

# ============================================================
# UPDATE 1: Plugin Header Comment Block ( * Version: X.Y.Z )
# ============================================================
# Match flexible whitespace: " * Version: X.Y.Z" or "  * Version: X.Y.Z"
$content = $content -replace '(\s*\*\s*Version:\s*)\d+\.\d+\.\d+', "`${1}$newVersion"

# ============================================================
# UPDATE 2: VAPTSECURE_VERSION constant
# ============================================================
$content = $content -replace "define\('VAPTSECURE_VERSION',\s*'\d+\.\d+\.\d+'\)", "define('VAPTSECURE_VERSION', '$newVersion')"

# ============================================================
# WRITE BACK (preserve encoding)
# ============================================================
[System.IO.File]::WriteAllText($mainFile, $content)

# ============================================================
# VERIFY: Re-read and confirm both locations match
# ============================================================
$verify = Get-Content $mainFile -Raw

$headerOk = $false
$constantOk = $false

if ($verify -match '\*\s*Version:\s*(\d+\.\d+\.\d+)') {
    if ($Matches[1] -eq $newVersion) { $headerOk = $true }
}
if ($verify -match "define\('VAPTSECURE_VERSION',\s*'(\d+\.\d+\.\d+)'\)") {
    if ($Matches[1] -eq $newVersion) { $constantOk = $true }
}

Write-Host ""
Write-Host "=== Verification ===" -ForegroundColor Cyan
Write-Host "  Plugin Header (Version:)    : $(if($headerOk){'OK'}else{'FAILED'})" -ForegroundColor $(if($headerOk){'Green'}else{'Red'})
Write-Host "  PHP Constant (VAPTSECURE_VERSION): $(if($constantOk){'OK'}else{'FAILED'})" -ForegroundColor $(if($constantOk){'Green'}else{'Red'})

if ($headerOk -and $constantOk) {
    Write-Host ""
    Write-Host "SUCCESS: Version bumped to $newVersion" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "WARNING: Some version locations may not have updated correctly. Please verify manually." -ForegroundColor Red
    exit 1
}
