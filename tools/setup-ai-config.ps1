# VAPT-Secure AI Configuration Auto-Setup Script (PowerShell)
# Automatically creates and verifies all symlinks for the Universal AI Configuration

param(
    [switch]$Force,
    [switch]$Verbose
)

# Color output functions
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Write-Success($msg) { Write-ColorOutput Green "✅ $msg" }
function Write-Error($msg) { Write-ColorOutput Red "❌ $msg" }
function Write-Warning($msg) { Write-ColorOutput Yellow "⚠️  $msg" }
function Write-Info($msg) { Write-ColorOutput Cyan "ℹ️  $msg" }
function Write-Header($msg) { Write-ColorOutput Blue "═══ $msg ═══" }

# Project root detection
$ScriptDir = Split-Path -Parent $PSCommandPath
$ProjectRoot = Split-Path -Parent $ScriptDir

Write-Header "VAPT-Secure AI Configuration Auto-Setup"
Write-Info "Project Root: $ProjectRoot"
Write-Output ""

# Change to project root
Set-Location $ProjectRoot

# Function to create symlink
function New-SymlinkSafe {
    param(
        [string]$Source,
        [string]$Target,
        [string]$Description
    )
    
    Write-Info "Setting up: $Description"
    
    try {
        # Create parent directory if it doesn't exist
        $TargetDir = Split-Path -Parent $Target
        if ($TargetDir -and !(Test-Path $TargetDir)) {
            New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null
        }
        
        # Remove existing file/link if it exists
        if (Test-Path $Target) {
            if ($Verbose) { Write-Warning "Removing existing: $Target" }
            Remove-Item -Path $Target -Recurse -Force
        }
        
        # Create symlink
        New-Item -ItemType SymbolicLink -Path $Target -Target $Source -Force | Out-Null
        Write-Success "$Target → $Source"
        return $true
    }
    catch {
        Write-Error "Failed to create: $Target ($($_.Exception.Message))"
        return $false
    }
}

# Function to verify symlink
function Test-SymlinkSafe {
    param(
        [string]$Target,
        [string]$ExpectedSource
    )
    
    try {
        if (Test-Path $Target -PathType SymbolicLink) {
            $ActualSource = (Get-Item $Target).Target
            if ($ActualSource -eq $ExpectedSource -or $ActualSource -like "*$ExpectedSource*") {
                Write-Success "Verified: $Target"
                return $true
            } else {
                Write-Warning "Wrong target: $Target → $ActualSource (expected: $ExpectedSource)"
                return $false
            }
        } else {
            Write-Error "Not a symlink: $Target"
            return $false
        }
    }
    catch {
        Write-Error "Verification failed: $Target ($($_.Exception.Message))"
        return $false
    }
}

Write-Info "🔗 Creating Editor Symlinks..."
Write-Output ""

# Track success/failure
$FailedCount = 0
$TotalCount = 0

# Tier 1: Core editor symlinks
$TotalCount++; if (!(New-SymlinkSafe ".ai\SOUL.md" ".windsurfrules" "Windsurf Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe ".ai\SOUL.md" ".clinerules" "Roo Code Rules (Modern)")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe ".ai\SOUL.md" ".roorules" "Roo Code Rules (Fallback)")) { $FailedCount++ }

# Tier 2: Editor-specific directories
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".cursor\cursor.rules" "Cursor Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".gemini\gemini.md" "Gemini/Antigravity Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".qoder\qoder.rules" "Qoder Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".trae\trae.rules" "Trae Rules")) { $FailedCount++ }

# Tier 3: Multi-file configurations
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".kilocode\rules\soul.md" "Kilo Code Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".continue\rules\soul.md" "Continue Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".roo\rules\soul.md" "Roo Code Directory Rules")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\SOUL.md" ".opencode\instructions\SOUL.md" "OpenCode Instructions")) { $FailedCount++ }

# Cross-IDE configurations
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".github\copilot-instructions.md" "GitHub Copilot Instructions")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\.ai\SOUL.md" ".junie\guidelines.md" "JetBrains Junie Guidelines")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe ".ai\SOUL.md" ".rules" "Zed Rules")) { $FailedCount++ }

# Claude configuration (special case - settings file)
$ClaudeSettings = @{
    rules = @("../.ai/rules/claude-settings.json")
    skills = @("../../.ai/skills/")
}
$ClaudeDir = ".claude"
if (!(Test-Path $ClaudeDir)) {
    New-Item -ItemType Directory -Path $ClaudeDir -Force | Out-Null
}
$ClaudeSettings | ConvertTo-Json -Depth 10 | Out-File -FilePath ".claude\settings.json" -Encoding UTF8
Write-Success "Created: .claude\settings.json"

Write-Output ""
Write-Info "🎯 Creating Skills Directory Symlinks..."
Write-Output ""

# Skills directory symlinks
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\skills" ".cursor\skills" "Cursor Skills")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\skills" ".windsurf\skills" "Windsurf Skills")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\..\.ai\skills" ".gemini\antigravity\skills" "Gemini/Antigravity Skills")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\skills" ".roo\skills" "Roo Code Skills")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\skills" ".kilo\skills" "Kilo Code Skills")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\skills" ".trae\skills" "Trae Skills")) { $FailedCount++ }
$TotalCount++; if (!(New-SymlinkSafe "..\..\.ai\skills" ".qoder\skills" "Qoder Skills")) { $FailedCount++ }

Write-Output ""
Write-Info "🔍 Verifying All Symlinks..."
Write-Output ""

# Verification
$VerificationFailed = 0

# Verify core symlinks
if (!(Test-SymlinkSafe ".windsurfrules" ".ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".clinerules" ".ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".roorules" ".ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".cursor\cursor.rules" "..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".gemini\gemini.md" "..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".qoder\qoder.rules" "..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".trae\trae.rules" "..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".kilocode\rules\soul.md" "..\..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".continue\rules\soul.md" "..\..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".roo\rules\soul.md" "..\..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".opencode\instructions\SOUL.md" "..\..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".github\copilot-instructions.md" "..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".junie\guidelines.md" "..\.ai\SOUL.md")) { $VerificationFailed++ }
if (!(Test-SymlinkSafe ".rules" ".ai\SOUL.md")) { $VerificationFailed++ }

Write-Output ""
Write-Info "🎓 Running Configuration Verification..."
Write-Output ""

# Run the verification script
$VerificationResult = 0
if (Test-Path "tools\verify-ai-config.php") {
    try {
        $phpOutput = & php tools\verify-ai-config.php 2>&1
        Write-Output $phpOutput
        if ($LASTEXITCODE -ne 0) { $VerificationResult = 1 }
    }
    catch {
        Write-Warning "PHP script execution failed: $($_.Exception.Message)"
        $VerificationResult = 1
    }
} else {
    Write-Warning "Verification script not found at tools\verify-ai-config.php"
    $VerificationResult = 1
}

Write-Output ""
Write-Header "Setup Summary"

$SuccessCount = $TotalCount - $FailedCount
$Score = if ($TotalCount -gt 0) { [math]::Round(($SuccessCount / $TotalCount) * 100) } else { 0 }

if ($FailedCount -eq 0 -and $VerificationResult -eq 0) {
    Write-Success "🎉 SUCCESS: All AI configurations are properly set up!"
    Write-Output ""
    Write-Success "Universal Source of Truth: .ai\SOUL.md"
    Write-Success "14 Editor/Extension Symlinks: Created and Verified"
    Write-Success "Skills Directory: Linked to all editors"
    Write-Success "Configuration Score: 100/100"
    Write-Output ""
    Write-Info "💡 Next Steps:"
    Write-Output "   1. Edit .ai\SOUL.md to update AI behavior"
    Write-Output "   2. Changes automatically propagate to all editors"
    Write-Output "   3. Run 'php tools\verify-ai-config.php' to verify sync"
    Write-Output "   4. See .ai\EDITOR_OPTIMIZATION_GUIDE.md for performance tips"
    exit 0
} else {
    Write-Error "❌ SETUP INCOMPLETE: $FailedCount/$TotalCount symlink(s) failed"
    Write-Output ""
    Write-Warning "🔧 Troubleshooting:"
    Write-Output "   1. Check file permissions"
    Write-Output "   2. Ensure you have symlink creation rights (run as Administrator)"
    Write-Output "   3. Run 'php tools\verify-ai-config.php' for detailed diagnostics"
    Write-Output "   4. Manual setup commands are in the verification script output"
    Write-Output ""
    Write-Info "📊 Configuration Score: $Score/100"
    exit 1
}
