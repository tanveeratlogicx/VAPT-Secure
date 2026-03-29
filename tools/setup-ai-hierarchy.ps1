# VAPT-Secure AI Configuration Hierarchy Setup Script
# Creates the Three-Tier AI configuration system
# Run: powershell -ExecutionPolicy Bypass -File tools/setup-ai-hierarchy.ps1

param(
    [switch]$WhatIf,
    [switch]$Force
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  VAPT-Secure AI Configuration Setup" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

if ($WhatIf) {
    Write-Host "[DRY RUN MODE - No files will be modified]" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================================================
# PART 1: Create Tier 1 Structure (.ai/)
# ============================================================================

Write-Host "Part 1: Creating Tier 1 (Universal Core)..." -ForegroundColor Green
Write-Host ""

$Tier1Dirs = @(
    ".ai",
    ".ai/skills",
    ".ai/workflows",
    ".ai/rules"
)

foreach ($dir in $Tier1Dirs) {
    if (Test-Path $dir) {
        Write-Host "  [exists]    $dir" -ForegroundColor Gray
    } else {
        if (-not $WhatIf) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
        Write-Host "  [created]   $dir" -ForegroundColor Cyan
    }
}

# ============================================================================
# PART 2: Create Tier 2 Symlinks (Editor Rules)
# ============================================================================

Write-Host ""
Write-Host "Part 2: Creating Tier 2 (Editor Symlinks)..." -ForegroundColor Green
Write-Host ""

$Tier2Links = @(
    @{ Path = ".windsurfrules"; Target = ".ai\SOUL.md"; Name = "Windsurf" },
    @{ Path = ".clinerules"; Target = ".ai\SOUL.md"; Name = "Claude Rules" },
    @{ Path = ".roorules"; Target = ".ai\SOUL.md"; Name = "Roo Rules" }
)

foreach ($link in $Tier2Links) {
    $path = $link.Path
    $target = $link.Target
    $name = $link.Name
    
    if (Test-Path $path) {
        # Check if it's already a link
        $item = Get-Item $path -Force -ErrorAction SilentlyContinue
        if ($item -and ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint)) {
            Write-Host "  [symlink]   $name -> $path" -ForegroundColor Green
        } else {
            # Backup existing file
            $backup = "$path.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
            if (-not $WhatIf) {
                Rename-Item -Path $path -NewName $backup -Force
                Write-Host "  [backed]    $path -> $backup" -ForegroundColor Yellow
            } else {
                Write-Host "  [would backup] $path" -ForegroundColor Yellow
            }
            
            # Create symlink
            if (-not $WhatIf) {
                New-Item -ItemType SymbolicLink -Path $path -Target $target -Force | Out-Null
            }
            Write-Host "  [symlink]   $name -> $path" -ForegroundColor Cyan
        }
    } else {
        # Create symlink
        if (-not $WhatIf) {
            try {
                New-Item -ItemType SymbolicLink -Path $path -Target $target -Force | Out-Null
                Write-Host "  [symlink]   $name -> $path" -ForegroundColor Green
            } catch {
                Write-Host "  [error]     $name (run as Admin)" -ForegroundColor Red
            }
        } else {
            Write-Host "  [would create] $name -> $path" -ForegroundColor Cyan
        }
    }
}

# ============================================================================
# PART 3: Create Editor Directories with Skill Junctions
# ============================================================================

Write-Host ""
Write-Host "Part 3: Creating Editor Skill Junctions..." -ForegroundColor Green
Write-Host ""

$EditorConfig = @(
    @{ Dir = ".cursor"; SkillsLink = "..\.ai\skills" },
    @{ Dir = ".windsurf"; SkillsLink = "..\.ai\skills" },
    @{ Dir = ".roo"; SkillsLink = "..\.ai\skills" },
    @{ Dir = ".kilo"; SkillsLink = "..\.ai\skills" },
    @{ Dir = ".trae"; SkillsLink = "..\.ai\skills" },
    @{ Dir = ".qoder"; SkillsLink = "..\.ai\skills" },
    @{ Dir = ".gemini\antigravity"; SkillsLink = "..\..\.ai\skills" }
)

foreach ($cfg in $EditorConfig) {
    $dir = $cfg.Dir
    $skillsTarget = $cfg.SkillsLink
    $skillsPath = Join-Path $dir "skills"
    
    # Create parent directory
    if (-not (Test-Path $dir)) {
        if (-not $WhatIf) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
        Write-Host "  [created]   $dir/" -ForegroundColor Cyan
    } else {
        Write-Host "  [exists]    $dir/" -ForegroundColor Gray
    }
    
    # Create skills junction
    if (Test-Path $skillsPath) {
        $item = Get-Item $skillsPath -Force -ErrorAction SilentlyContinue
        if ($item -and ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint)) {
            Write-Host "  [junction]  $skillsPath" -ForegroundColor Green
        } else {
            Write-Host "  [exists]    $skillsPath (not a junction)" -ForegroundColor Yellow
        }
    } else {
        if (-not $WhatIf) {
            try {
                New-Item -ItemType Junction -Path $skillsPath -Target (Resolve-Path $skillsTarget) -Force | Out-Null
                Write-Host "  [junction]  $skillsPath" -ForegroundColor Green
            } catch {
                Write-Host "  [error]     $skillsPath`: $_" -ForegroundColor Red
            }
        } else {
            Write-Host "  [would create] $skillsPath" -ForegroundColor Cyan
        }
    }
}

# ============================================================================
# PART 4: Summary
# ============================================================================

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  Setup Complete!" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor White
Write-Host "  1. Ensure .ai/SOUL.md is your canonical source" -ForegroundColor Gray
Write-Host "  2. Run verification: php tools/verify-ai-config.php" -ForegroundColor Gray
Write-Host "  3. Test in your preferred IDE" -ForegroundColor Gray
Write-Host ""
Write-Host "Directory Structure:" -ForegroundColor White
Write-Host "  Tier 1 (Universal):  .ai/SOUL.md" -ForegroundColor Cyan
Write-Host "  Tier 2 (Editor):     .windsurfrules -> .ai/SOUL.md" -ForegroundColor Green
Write-Host "  Tier 3 (Extension):  .roomodes, .kilo/agent-manager.json" -ForegroundColor Green
Write-Host ""
Write-Host "Skills Junctions:" -ForegroundColor White
Write-Host "  .cursor/skills -> .ai/skills" -ForegroundColor Gray
Write-Host "  .roo/skills -> .ai/skills" -ForegroundColor Gray
Write-Host "  .kilo/skills -> .ai/skills" -ForegroundColor Gray
Write-Host ""

if ($WhatIf) {
    Write-Host "[This was a dry run. No files were modified.]" -ForegroundColor Yellow
    Write-Host "Run without -WhatIf to apply changes." -ForegroundColor Yellow
    Write-Host ""
}
