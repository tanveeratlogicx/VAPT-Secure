# VAPT-Secure AI Configuration Cleanup - MASTER EXECUTION SCRIPT
# Run this to execute all cleanup phases
# Requires: PowerShell 5.1+ (Run as Administrator)

#requires -RunAsAdministrator
#requires -Version 5.1

param(
    [switch]$WhatIf,
    [switch]$SkipBackup,
    [string]$BackupPath,
    [switch]$Force
)

# ============================================================================
# CONFIGURATION
# ============================================================================

$Script:StartTime = Get-Date
$Script:Errors = @()
$Script:Warnings = @()
$Script:Actions = @()
$Script:BackupLoc = if ($BackupPath) { $BackupPath } else { ".archive/ai-cleanup-$(Get-Date -Format 'yyyyMMdd_HHmmss')" }

# ============================================================================
# UI FUNCTIONS
# ============================================================================

function Write-Banner($text) {
    Write-Host ""
    Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║ $text".PadRight(63) "║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
}

function Write-Phase($number, $name) {
    Write-Host ""
    Write-Host "────────────────────────────────────────────────────────────────" -ForegroundColor Blue
    Write-Host "  PHASE $number`: $name" -ForegroundColor Blue
    Write-Host "────────────────────────────────────────────────────────────────" -ForegroundColor Blue
}

function Write-Action($message) {
    Write-Host "  [ACTION] $message" -ForegroundColor DarkCyan
    $Script:Actions += $message
}

function Write-Success($message) {
    Write-Host "    ✓ $message" -ForegroundColor Green
}

function Write-Error-Custom($message) {
    Write-Host "    ✗ $message" -ForegroundColor Red
    $Script:Errors += $message
}

function Write-Warning-Custom($message) {
    Write-Host "    ⚠ $message" -ForegroundColor Yellow
    $Script:Warnings += $message
}

function Write-Info($message) {
    Write-Host "    ℹ $message" -ForegroundColor Gray
}

function Stop-OnErrors {
    if ($Script:Errors.Count -gt 0 -and -not $Force) {
        Write-Host ""
        Write-Error-Custom "Errors encountered during execution. Use -Force to continue."
        exit 1
    }
}

# ============================================================================
# PHASE 1: BACKUP
# ============================================================================

function Invoke-Phase1-Backup {
    Write-Phase "1" "Backup Existing Configuration"
    
    if ($SkipBackup) {
        Write-Info "Skipping backup (--SkipBackup)"
        return
    }
    
    Write-Action "Creating backup at: $Script:BackupLoc"
    
    if ($WhatIf) {
        Write-Info "[WHATIF] Would create: $Script:BackupLoc"
        return
    }
    
    New-Item -ItemType Directory -Path $Script:BackupLoc -Force | Out-Null
    
    $backupItems = @(
        @{ Source = ".ai"; Type = "Directory" },
        @{ Source = ".agent"; Type = "Directory" },
        @{ Source = ".cursor"; Type = "Directory" },
        @{ Source = ".roo"; Type = "Directory" },
        @{ Source = ".kilo"; Type = "Directory" },
        @{ Source = ".kilocode"; Type = "Directory" },
        @{ Source = ".trae"; Type = "Directory" },
        @{ Source = ".qoder"; Type = "Directory" },
        @{ Source = ".claude"; Type = "Directory" },
        @{ Source = ".gemini"; Type = "Directory" },
        @{ Source = ".opencode"; Type = "Directory" },
        @{ Source = ".windsurfrules"; Type = "File" },
        @{ Source = ".clinerules"; Type = "File" },
        @{ Source = ".roorules"; Type = "File" }
    )
    
    foreach ($item in $backupItems) {
        if (Test-Path $item.Source) {
            $dest = Join-Path $Script:BackupLoc $item.Source
            if (-not (Test-Path $dest)) {
                if ($item.Type -eq "Directory") {
                    Copy-Item -Path $item.Source -Destination $dest -Recurse -Force
                } else {
                    Copy-Item -Path $item.Source -Destination $dest -Force
                }
                Write-Success "Backed up: $($item.Source)"
            }
        }
    }
    
    Write-Success "Backup complete: $Script:BackupLoc"
}

# ============================================================================
# PHASE 2: ARCHIVE DUPLICATES
# ============================================================================

function Invoke-Phase2-ArchiveDuplicates {
    Write-Phase "2" "Archive Duplicate SOUL Files"
    
    $duplicates = @(
        ".ai/SOUL-Claude.md",
        ".ai/SOUL-Claude.md",
        ".ai/SOUL_comprehensive.md",
        ".ai/SOUL_enhanced.md",
        ".ai/SOUL_with_selfcheck.md"
    )
    
    $archiveDir = Join-Path $Script:BackupLoc "archived-duplicates"
    
    foreach ($dup in $duplicates) {
        if (Test-Path $dup) {
            Write-Action "Archiving: $dup"
            
            if (-not $WhatIf) {
                New-Item -ItemType Directory -Path $archiveDir -Force | Out-Null
                $dest = Join-Path $archiveDir (Split-Path $dup -Leaf)
                Move-Item -Path $dup -Destination $dest -Force
            }
            Write-Success "Archived: $dup"
        } else {
            Write-Info "Not found (already cleaned): $dup"
        }
    }
}

# ============================================================================
# PHASE 3: CREATE SYMLINKS
# ============================================================================

function Invoke-Phase3-CreateSymlinks {
    Write-Phase "3" "Create Tier 2 Symlinks"
    
    $symlinks = @(
        # Core rules in root
        @{ Path = ".windsurfrules"; Target = ".ai\SOUL.md"; Description = "Windsurf" },
        @{ Path = ".clinerules"; Target = ".ai\SOUL.md"; Description = "Claude CLI" },
        @{ Path = ".roorules"; Target = ".ai\SOUL.md"; Description = "Roo Code" },
        
        # Editor directories
        @{ Path = ".cursor\cursor.rules"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".cursor"; Description = "Cursor" },
        @{ Path = ".gemini\gemini.md"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".gemini"; Description = "Gemini" },
        @{ Path = ".trae\trae.rules"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".trae"; Description = "Trae" },
        @{ Path = ".qoder\qoder.rules"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".qoder"; Description = "Qoder" },
        @{ Path = ".roo\roo.rules"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".roo"; Description = "Roo" },
        @{ Path = ".roo\rules\soul.md"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".roo\rules"; Description = "Roo Rules Dir" },
        @{ Path = ".kilo\kilo.rules"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".kilo"; Description = "Kilo" },
        @{ Path = ".kilocode\rules\soul.md"; Target = "..\..\..\.ai\SOUL.md"; CreateDir = ".kilocode\rules"; Description = "KiloCode" },
        @{ Path = ".opencode\instructions\SOUL.md"; Target = "..\..\..\.ai\SOUL.md"; CreateDir = ".opencode\instructions"; Description = "OpenCode" },
        @{ Path = ".claude\SOUL.md"; Target = "..\..\.ai\SOUL.md"; CreateDir = ".claude"; Description = "Claude" }
    )
    
    foreach ($link in $symlinks) {
        $path = $link.Path
        $target = $link.Target
        $desc = $link.Description
        
        # Create parent directory if needed
        if ($link.CreateDir -and -not $WhatIf) {
            New-Item -ItemType Directory -Path $link.CreateDir -Force | Out-Null
        }
        
        Write-Action "Creating symlink: $desc ($path)"
        
        if ($WhatIf) {
            Write-Info "[WHATIF] $path -> $target"
            continue
        }
        
        # Backup existing file if it exists and is not already a symlink
        if (Test-Path $path) {
            $item = Get-Item $path -Force -ErrorAction SilentlyContinue
            if ($item -and ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint)) {
                Write-Success "Already a symlink: $path"
                continue
            }
            
            # Backup
            $backupName = "$path.backup.$(Get-Date -Format 'yyyyMMddHHmmss')"
            Rename-Item -Path $path -NewName $backupName -Force
            Write-Info "Backed up: $path -> $backupName"
        }
        
        # Create symlink (requires admin)
        try {
            New-Item -ItemType SymbolicLink -Path $path -Target $target -Force | Out-Null
            Write-Success "Created: $path -> $target"
        } catch {
            Write-Error-Custom "Failed to create $path`: $_"
        }
    }
}

# ============================================================================
# PHASE 4: SKILL JUNCTIONS
# ============================================================================

function Invoke-Phase4-CreateSkillJunctions {
    Write-Phase "4" "Create Skill Directory Junctions"
    
    $junctions = @(
        @{ Path = ".cursor\skills"; Target = "..\..\.ai\skills"; Description = "Cursor Skills" },
        @{ Path = ".windsurf\skills"; Target = "..\..\.ai\skills"; Description = "Windsurf Skills" },
        @{ Path = ".roo\skills"; Target = "..\..\.ai\skills"; Description = "Roo Skills" },
        @{ Path = ".kilo\skills"; Target = "..\..\.ai\skills"; Description = "Kilo Skills" },
        @{ Path = ".trae\skills"; Target = "..\..\.ai\skills"; Description = "Trae Skills" },
        @{ Path = ".qoder\skills"; Target = "..\..\.ai\skills"; Description = "Qoder Skills" },
        @{ Path = ".gemini\antigravity\skills"; Target = "..\..\..\.ai\skills"; Description = "Gemini Skills" },
        @{ Path = ".claude\skills"; Target = "..\..\.ai\skills"; Description = "Claude Skills" },
        @{ Path = ".opencode\skills"; Target = "..\..\.ai\skills"; Description = "OpenCode Skills" }
    )
    
    foreach ($junction in $junctions) {
        $path = $junction.Path
        $target = $junction.Target
        $desc = $junction.Description
        
        # Ensure parent directory exists
        $parent = Split-Path $path -Parent
        if (-not $WhatIf -and -not (Test-Path $parent)) {
            New-Item -ItemType Directory -Path $parent -Force | Out-Null
        }
        
        Write-Action "Creating junction: $desc"
        
        if ($WhatIf) {
            Write-Info "[WHATIF] $path -> $target"
            continue
        }
        
        # Check existing
        if (Test-Path $path) {
            $item = Get-Item $path -Force -ErrorAction SilentlyContinue
            if ($item -and ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint)) {
                Write-Success "Already a junction: $path"
                continue
            }
            Write-Warning-Custom "Directory exists but not a junction: $path"
            continue
        }
        
        # Create junction
        try {
            # Resolve target path
            $resolvedTarget = Resolve-Path -Path $target -ErrorAction Stop
            New-Item -ItemType Junction -Path $path -Target $resolvedTarget -Force | Out-Null
            Write-Success "Created: $path -> $target"
        } catch {
            Write-Error-Custom "Failed to create $path`: $_"
        }
    }
}

# ============================================================================
# PHASE 5: TIER 3 CONFIGURATION
# ============================================================================

function Invoke-Phase5-Tier3Configuration {
    Write-Phase "5" "Create Tier 3 Extension Configurations"
    
    # GitHub Copilot
    Write-Action "Creating GitHub Copilot instructions"
    if (-not $WhatIf) {
        New-Item -ItemType Directory -Path ".github" -Force | Out-Null
        $copilotPath = ".github\copilot-instructions.md"
        New-Item -ItemType SymbolicLink -Path $copilotPath -Target "..\.ai\SOUL.md" -Force -ErrorAction SilentlyContinue | Out-Null
        if (Test-Path $copilotPath) {
            Write-Success "Created: $copilotPath"
        } else {
            Write-Warning-Custom "Could not create copilot symlink (may need manual creation)"
        }
    }
    
    # Junie
    Write-Action "Creating JetBrains Junie guidelines"
    if (-not $WhatIf) {
        New-Item -ItemType Directory -Path ".junie" -Force | Out-Null
        $juniePath = ".junie\guidelines.md"
        New-Item -ItemType SymbolicLink -Path $juniePath -Target "..\.ai\SOUL.md" -Force -ErrorAction SilentlyContinue | Out-Null
        if (Test-Path $juniePath) {
            Write-Success "Created: $juniePath"
        }
    }
    
    # Zed
    Write-Action "Creating Zed rules"
    if (-not $WhatIf) {
        New-Item -ItemType Directory -Path ".zed" -Force | Out-Null
        $zedPath = ".zed\.rules"
        New-Item -ItemType SymbolicLink -Path $zedPath -Target "..\.ai\SOUL.md" -Force -ErrorAction SilentlyContinue | Out-Null
        if (Test-Path $zedPath) {
            Write-Success "Created: $zedPath"
        }
    }
    
    # Claude Code settings
    Write-Action "Creating Claude Code settings.json"
    if (-not $WhatIf) {
        $claudeSettings = @{
            "projectRoot" = "."
            "soul_path" = ".ai/SOUL.md"
            "commands" = @{
                "vapt-expert" = "Load .ai/skills/vapt-expert/SKILL.md"
                "schema-build" = "Load .ai/skills/vaptschema-builder/SKILL.md"
            }
        } | ConvertTo-Json -Depth 3
        
        $claudePath = ".claude\settings.json"
        if (Test-Path $claudePath) {
            Write-Info "settings.json already exists"
        } else {
            $claudeSettings | Set-Content -Path $claudePath -Encoding UTF8 -Force
            Write-Success "Created: $claudePath"
        }
    }
    
    # Kilo agent manager
    Write-Action "Creating Kilo agent-manager.json"
    if (-not $WhatIf) {
        $kiloConfig = @{
            "base_config" = ".ai/SOUL.md"
            "agents" = @(
                @{
                    "id" = "vapt-expert"
                    "name" = "VAPT Security Expert"
                    "instructions" = ".ai/skills/vapt-expert/SKILL.md"
                    "triggers" = @("security", "htaccess", "risk", "vapt")
                },
                @{
                    "id" = "schema-builder"
                    "name" = "Schema Builder"
                    "instructions" = ".ai/skills/vaptschema-builder/SKILL.md"
                    "triggers" = @("schema", "interface", "json", "mapping")
                }
            )
            "routing" = @{
                "default" = "vapt-expert"
                "context_keywords" = @{
                    "schema" = "schema-builder"
                    "security" = "vapt-expert"
                    "htaccess" = "vapt-expert"
                }
            }
        } | ConvertTo-Json -Depth 5
        
        $kiloPath = ".kilo\agent-manager.json"
        $kiloConfig | Set-Content -Path $kiloPath -Encoding UTF8 -Force
        Write-Success "Created/Updated: $kiloPath"
    }
}

# ============================================================================
# PHASE 6: VERIFICATION
# ============================================================================

function Invoke-Phase6-Verification {
    Write-Phase "6" "Verification"
    
    if ($WhatIf) {
        Write-Info "[WHATIF] Skipping verification in dry-run mode"
        return
    }
    
    Write-Action "Running verification script..."
    
    if (Test-Path "tools\verify-ai-config.php") {
        & php "tools\verify-ai-config.php"
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Verification passed"
        } else {
            Write-Warning-Custom "Verification reported issues (review output above)"
        }
    } else {
        Write-Warning-Custom "Verification script not found: tools\verify-ai-config.php"
    }
}

# ============================================================================
# SUMMARY
# ============================================================================

function Write-Summary {
    Write-Host ""
    Write-Banner "CLEANUP COMPLETE"
    Write-Host ""
    
    $duration = (Get-Date) - $Script:StartTime
    Write-Host "  Duration: $($duration.ToString('mm\:ss'))" -ForegroundColor Cyan
    Write-Host "  Backup: $Script:BackupLoc" -ForegroundColor Cyan
    Write-Host ""
    
    Write-Host "  Summary:" -ForegroundColor White
    Write-Host "    • Actions: $($Script:Actions.Count)" -ForegroundColor Gray
    Write-Host "    • Errors: $($Script:Errors.Count)" -ForegroundColor $(if($Script:Errors.Count -gt 0){'Red'}else{'Green'})
    Write-Host "    • Warnings: $($Script:Warnings.Count)" -ForegroundColor $(if($Script:Warnings.Count -gt 0){'Yellow'}else{'Green'})
    Write-Host ""
    
    if ($Script:Errors.Count -gt 0) {
        Write-Host "  Errors encountered:" -ForegroundColor Red
        foreach ($err in $Script:Errors | Select-Object -First 5) {
            Write-Host "    - $err" -ForegroundColor Red
        }
        if ($Script:Errors.Count -gt 5) {
            Write-Host "    ... and $($Script:Errors.Count - 5) more" -ForegroundColor Red
        }
    }
    
    if ($WhatIf) {
        Write-Host ""
        Write-Host "  [This was a DRY RUN - no files were modified]" -ForegroundColor Yellow
        Write-Host "  Re-run without -WhatIf to apply changes" -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "  Next steps:" -ForegroundColor White
    Write-Host "    1. Review backup at: $Script:BackupLoc" -ForegroundColor Gray
    Write-Host "    2. Test in your preferred IDE" -ForegroundColor Gray
    Write-Host "    3. Verify with: php tools\verify-ai-config.php" -ForegroundColor Gray
    Write-Host ""
}

# ============================================================================
# MAIN
# ============================================================================

function Main {
    Write-Banner "VAPT-Secure AI Configuration Cleanup v1.0"
    
    Write-Host ""
    Write-Host "  Mode: $($(if($WhatIf){'DRY-RUN (no changes)'}else{'LIVE (will modify files)'}))" -ForegroundColor $(if($WhatIf){'Yellow'}else{'Green'})
    Write-Host "  Backup: $Script:BackupLoc" -ForegroundColor Cyan
    Write-Host ""
    
    if (-not $Force) {
        Write-Host "  WARNING: This script will modify AI configuration files." -ForegroundColor Yellow
        Write-Host "  A backup will be created, but existing files may be renamed." -ForegroundColor Yellow
        Write-Host ""
        $confirm = Read-Host "  Continue? [Y/N]"
        if ($confirm -ne 'Y') {
            Write-Host "  Cancelled." -ForegroundColor Red
            exit 0
        }
    }
    
    # Execute phases
    Invoke-Phase1-Backup
    Invoke-Phase2-ArchiveDuplicates
    Invoke-Phase3-CreateSymlinks
    Invoke-Phase4-CreateSkillJunctions
    Invoke-Phase5-Tier3Configuration
    Invoke-Phase6-Verification
    
    Write-Summary
}

# Run
Main
