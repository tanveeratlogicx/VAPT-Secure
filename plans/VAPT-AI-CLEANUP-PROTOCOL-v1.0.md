# VAPT-Secure AI Configuration Cleanup Protocol
> **Version**: 1.0.0 | **Date**: 2026-03-27  
> **Priority**: 🔴 CRITICAL — Duplicated SOUL configs causing AI inconsistency

---

## 🎯 CLAUDE.md vs SOUL.md — Critical Distinction

**CLAUDE.md (root)** ≠ **SOUL.md (.ai/)** — they serve **different purposes**:

| File | Purpose | Audience | Kept? |
|------|---------|----------|-------|
| `CLAUDE.md` | **Command reference** for Claude Code CLI | Developers using `claude` command | ✅ **KEEP** |
| `.ai/SOUL.md` | **AI behavior definition** (identity, rules, workflows) | AI agents (all IDEs) | ✅ **KEEP as Tier 1** |
| `.ai/CLAUDE.md` (if exists) | ❌ **Remove** — duplicate purpose | N/A | ❌ **DELETE** |

**CLAUDE.md** (root) contains:
- `composer run lint` commands
- `npm test` commands
- API documentation links
- **Tool commands reference**

**SOUL.md** (.ai/) contains:
- AI identity ("You are a VAPT security specialist...")
- Security guardrails
- Feature lifecycle rules
- **AI behavior and constraints**

### Claude Code Configuration Structure

```
Project Root/
│
├── CLAUDE.md                    ← Commands reference (KEEP)
│
├── .claude/                     ← Claude Code extensions
│   ├── settings.json → .ai/rules/claude.json  ← Symlink
│   ├── CLAUDE.md → ../CLAUDE.md               ← Symlink to root
│   └── skills/ → ../.ai/skills               ← Junction
│
└── .ai/SOUL.md                  ← AI behavior (Tier 1)
```

---

## 📊 Current State Analysis

### The Problem
Your "Agent Soul" approach has **fragmented into multiple independent copies**:

| File | Version | Status | Issue |
|------|---------|--------|-------|
| `.ai/SOUL.md` | **2.5.9** | ✅ Canonical | Keep as Tier 1 |
| `.kilo/kilo.rules` | **2.4.13** | ❌ Standalone | Should be symlink |
| `.trae/trae.rules` | **2.4.11** | ❌ Standalone | Should be symlink |
| `.ai/SOUL-Claude.md` | **2.4.11** | ❌ Duplicate | Archive/Delete |
| `.ai/SOUL_comprehensive.md` | 2.4.11 | ❌ Duplicate | Archive/Delete |
| `.ai/SOUL_enhanced.md` | 2.4.11 | ❌ Duplicate | Archive/Delete |
| `.ai/SOUL_with_selfcheck.md` | 2.4.11 | ❌ Duplicate | Archive/Delete |
| `.gemini/gemini.md` | 2.4.11 | ❌ Standalone | Should be symlink |
| `.opencode/instructions/SOUL.md` | **2.5.9** | ⚠️ Copy | Should be symlink |
| `.roo/rules/soul.md` | **2.5.9** | ⚠️ Copy | Should be symlink |
| `.windsurfrules` | Unknown | ❌ Standalone | Should be symlink |
| `.clinerules` | Unknown | ❌ Standalone | Should be symlink |
| `.roorules` | Unknown | ❌ Standalone | Should be symlink |

**Impact**: AI agents receive **different instructions** depending on which IDE/extension is used → Inconsistent behavior, security rule violations.

---

## 🎯 Target State Architecture

### Tier 1: Universal (1 file only)
```
.ai/
├── SOUL.md              ← SINGLE SOURCE OF TRUTH (v2.5.9+)
├── AGENTS.md            ← Multi-agent orchestration
├── VAPTSECURE.md        ← Project context
├── WORKFLOWS.md         ← Common workflows
├── skills/              ← Shared skills
│   └── vapt-expert/
├── workflows/           ← Automation
└── rules/               ← T2 helper symlinks
    ├── cursor.rules → ../SOUL.md
    └── gemini.md → ../SOUL.md
```

### Tier 2: Editor Rules (symlinks only)
```
.windsurfrules → .ai/SOUL.md
.clinerules → .ai/SOUL.md
.roorules → .ai/SOUL.md
.cursor/cursor.rules → ../.ai/SOUL.md
.gemini/gemini.md → ../.ai/SOUL.md
.qoder/qoder.rules → ../.ai/SOUL.md
.trae/trae.rules → ../.ai/SOUL.md
.opencode/instructions/SOUL.md → ../../.ai/SOUL.md
.roo/roo.rules → ../.ai/SOUL.md
.claude/settings.json → ../.ai/rules/claude.json
.github/copilot-instructions.md → ../.ai/SOUL.md
.junie/guidelines.md → ../.ai/SOUL.md
.zed/.rules → ../.ai/SOUL.md
.continue/rules/soul.md → ../../.ai/SOUL.md
.kilo/kilo.rules → ../.ai/SOUL.md
.kilocode/rules/soul.md → ../../.ai/SOUL.md
```

### Tier 3: Extension Modes (extension-specific)
```
.roomodes              ← Roo custom modes (overrides)
.kilo/agent-manager.json  ← Kilo agent routing
.continue/config.json     ← Continue-specific
.kilocode/agents/          ← Kilo Code agents
```

---

## 🗑️ Files To Delete/Archive

### Delete Immediately (Duplicates)
```bash
# Old SOUL variants
.ai/SOUL-Claude.md
.ai/SOUL-Claude.md
.ai/SOUL_comprehensive.md
.ai/SOUL_enhanced.md
.ai/SOUL_with_selfcheck.md

# Keep in archive, delete from active:
# Moving these to .archive/{date}/
```

### Archive After Cleanup (Legacy)
```bash
# After migration is complete:
.agent/                 → .archive/.agent-{date}/
wildly out-of-date rule directories
```

---

## 🔧 Cleanup Execution Plan

### PHASE 1: Backup (5 minutes)
Execute: `tools/cleanup-phase1-backup.ps1`

```powershell
# Creates backup archive of all AI config before changes
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = ".archive/ai-config-backup-$timestamp"
# ... backs up all .ai, .agent, .roo, .kilo, etc
```

### PHASE 2: Consolidate SOUL Files (10 minutes)

**Step 2.1**: Identify canonical
- `.ai/SOUL.md` at v2.5.9 is the **latest and most complete**
- This becomes the Tier 1 source

**Step 2.2**: Merge unique content from old SOULs (if any)
```bash
# Compare old variants for any unique content
# Most are just older versions of the same content
.diff .ai/SOUL.md .ai/SOUL-Claude.md
.diff .ai/SOUL.md .ai/SOUL_enhanced.md
# If any unique sections found, merge into .ai/SOUL.md
```

**Step 2.3**: Archive old SOUL files
```powershell
$archiveDir = ".archive/2026-03-27-soul-cleanup"
New-Item -ItemType Directory -Path $archiveDir -Force

Move-Item ".ai/SOUL-Claude.md" "$archiveDir/"
Move-Item ".ai/SOUL-Claude.md" "$archiveDir/"
Move-Item ".ai/SOUL_comprehensive.md" "$archiveDir/"
Move-Item ".ai/SOUL_enhanced.md" "$archiveDir/"
Move-Item ".ai/SOUL_with_selfcheck.md" "$archiveDir/"
```

### PHASE 3: Symlink Creation (15 minutes)
Execute: `tools/cleanup-phase3-symlinks.ps1`

Replace all standalone rules files with symlinks to `.ai/SOUL.md`:

#### Windows (PowerShell - Admin Required)
```powershell
# CORE TIER 2 FILES
# These MUST be symlinks
$Tier2Symlinks = @(
    @{ Path = ".windsurfrules"; Target = ".ai\SOUL.md" },
    @{ Path = ".clinerules"; Target = ".ai\SOUL.md" },
    @{ Path = ".roorules"; Target = ".ai\SOUL.md" }
)

foreach ($link in $Tier2Symlinks) {
    if (Test-Path $link.Path) {
        # Backup existing file
        $backup = "$($link.Path).pre-symlink.backup"
        Rename-Item -Path $link.Path -NewName $backup -Force
    }
    # Create symlink (requires admin)
    New-Item -ItemType SymbolicLink -Path $link.Path -Target $link.Target -Force
}
```

#### Editor-Specific Symlinks

```powershell
# EDITOR DIRECTORIES (create if missing)
$editors = @(
    @{ Dir = ".cursor"; RulesPath = ".cursor\cursor.rules"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".gemini"; RulesPath = ".gemini\gemini.md"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".trae"; RulesPath = ".trae\trae.rules"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".qoder"; RulesPath = ".qoder\qoder.rules"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".roo"; RulesPath = ".roo\roo.rules"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".opencode\instructions"; RulesPath = ".opencode\instructions\SOUL.md"; RulesTarget = "..\..\..\.ai\SOUL.md" },
    @{ Dir = ".kilo"; RulesPath = ".kilo\kilo.rules"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".kilocode\rules"; RulesPath = ".kilocode\rules\soul.md"; RulesTarget = "..\..\..\.ai\SOUL.md" },
    @{ Dir = ".claude"; RulesPath = ""; RulesTarget = "" },  # Uses settings.json
    @{ Dir = ".junie"; RulesPath = ".junie\guidelines.md"; RulesTarget = "..\..\.ai\SOUL.md" },
    @{ Dir = ".zed"; RulesPath = ".zed\.rules"; RulesTarget = "..\..\.ai\SOUL.md" }
)
```

#### GitHub Copilot
```powershell
# Create .github/copilot-instructions.md
gitHubDir = ".github"
if (-not (Test-Path $gitHubDir)) {
    New-Item -ItemType Directory -Path $gitHubDir -Force
}
$copilotInstructions = ".github\copilot-instructions.md"

# This requires [CmdletBinding()] attribute support
# Can't use New-Item SymbolicLink for copilot-instructions
# Instead, use hardlink or just symlink

New-Item -ItemType SymbolicLink -Path $copilotInstructions -Target "..\.ai\SOUL.md" -Force
```

### PHASE 4: Skill Directory Links (10 minutes)

Create junctions for shared skills:

```powershell
$skillLinks = @(
    @{ Path = ".cursor\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".windsurf\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".roo\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".kilo\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".trae\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".qoder\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".gemini\antigravity\skills"; Target = "..\..\..\.ai\skills" },
    @{ Path = ".claude\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".opencode\skills"; Target = "..\..\.ai\skills" },
    @{ Path = ".continue\skills"; Target = "..\..\.ai\skills" }
)

foreach ($link in $skillLinks) {
    if (-not (Test-Path $link.Path)) {
        New-Item -ItemType Junction -Path $link.Path -Target $link.Target -Force
    }
}
```

### PHASE 5: Tier 3 Configuration (15 minutes)

#### 5.1 Roo Code Extension (.roomodes)

Verify `.roomodes` references Tier 1:

```yaml
# The roomodes file should REFERENCE .ai/SOUL.md in role definitions
customModes:
  - slug: vapt-security
    name: 🔒 VAPT Security
    roleDefinition: |-
      Base behavior defined in `.ai/SOUL.md`.
      See that file for core identity, security guardrails, 
      and feature lifecycle rules.
      
      This mode adds: Role-specific capabilities...
```

#### 5.2 Kilo Code Agent Configuration

Create `.kilo/agent-manager.json`:

```json
{
  "base_config": ".ai/SOUL.md",
  "agents": [
    {
      "id": "vapt-expert",
      "name": "VAPT Security Expert",
      "instructions": ".ai/skills/vapt-expert/SKILL.md",
      "triggers": ["security", "htaccess", "risk", "vapt"]
    },
    {
      "id": "schema-builder", 
      "name": "Schema Builder",
      "instructions": ".ai/skills/vaptschema-builder/SKILL.md",
      "triggers": ["schema", "interface", "json", "mapping"]
    }
  ],
  "routing": {
    "default": "vapt-expert",
    "context_keywords": {
      "schema": "schema-builder",
      "security": "vapt-expert",
      "htaccess": "vapt-expert"
    }
  }
}
```

#### 5.3 Continue Extension

Create `.continue/config.json`:

```json
{
  "custom_commands": [
    {
      "name": "vapt",
      "description": "VAPT security expert mode",
      "prompt": "Read .ai/SOUL.md and respond as a VAPT security specialist."
    }
  ],
  "system_message": ".ai/SOUL.md"
}
```

#### 5.4 Claude Code Settings

`.claude/settings.json`:

```json
{
  "projectRoot": ".",
  "soul_path": ".ai/SOUL.md",
  "commands": {
    "vapt-expert": "Load .ai/skills/vapt-expert/SKILL.md",
    "schema-build": "Load .ai/skills/vaptschema-builder/SKILL.md"
  }
}
```

### PHASE 6: Verification (10 minutes)

Run the verification script:

```bash
php tools/verify-ai-config.php
```

Expected output:
```
✅ All AI configuration files are in sync!
Plugin Version: 2.5.9
Tier 1 (SOUL): ✓ Valid
Tier 2 (Symlinks): ✓ All linked
```

---

## 🚨 SECURITY FINDING: API Keys Exposed

### URGENT: `.claude/settings.json` and `settings_.json`

```json
// Contains hardcoded OpenRouter API keys:
"ANTHROPIC_AUTH_TOKEN": "sk-or-v1-571c1234..."
"ANTHROPIC_AUTH_TOKEN": "sk-or-v1-5064452..."  // in settings_.json
```

**CRITICAL ACTION REQUIRED:**
1. **Immediately rotate these API keys** at OpenRouter
2. **Add to `.gitignore`**: `.claude/settings*.json` and `!.claude/settings.template.json`
3. **Create template**: `.claude/settings.template.json` with placeholder values
4. **Update CLAUDE.md**: Add setup instructions

### Recommended Structure

```
.claude/
├── settings.template.json        ← Template with placeholders
├── settings.json                 ← User-created, gitignored
├── settings.local.json           ← Already exists, gitignored
├── CLAUDE.md → ../CLAUDE.md      ← Symlink to root
└── skills/ → ../.ai/skills       ← Junction
```

---

## 📋 Complete File Checklist

### CLAUDE.md Files (Different Purpose - KEEP)

| # | Path | Action | Reason |
|---|------|--------|--------|
| ✅ | `CLAUDE.md` (root) | **KEEP** | Command reference for `claude` CLI |
| ✅ | `.claude/CLAUDE.md` | CREATE → `../CLAUDE.md` | Symlink to root |
| ❌ | `.ai/CLAUDE.md` | DELETE (if exists) | Duplicate of root |

### SOUL.md Sources (Keep Only These)

| # | Path | Action | Reason |
|---|------|--------|--------|
| ✅ | `.ai/SOUL.md` | KEEP | Canonical Tier 1 source |
| ✅ | `.ai/rules/cursor.rules` | KEEP | Symlink to SOUL.md |
| ✅ | `.ai/rules/gemini.md` | KEEP | Symlink to SOUL.md |
| ✅ | `.ai/AGENTS.md` | KEEP | Multi-agent spec |
| ✅ | `.ai/VAPTSECURE.md` | KEEP | Project context |
| ✅ | `.ai/README.md` | KEEP | Documentation |
| ❌ | `.ai/SOUL-Claude.md` | ARCHIVE | Duplicate |
| ❌ | `.ai/SOUL-Claude.md` | ARCHIVE | Duplicate |
| ❌ | `.ai/SOUL_comprehensive.md` | ARCHIVE | Duplicate |
| ❌ | `.ai/SOUL_enhanced.md` | ARCHIVE | Duplicate |
| ❌ | `.ai/SOUL_with_selfcheck.md` | ARCHIVE | Duplicate |

### Editor Rules (Must Be Symlinks)

| # | Editor | Path | Expected Target | Action |
|---|--------|------|-----------------|--------|
| 1 | Windsurf | `.windsurfrules` | → `.ai/SOUL.md` | 🔄 Convert |
| 2 | Claude CLI | `.clinerules` | → `.ai/SOUL.md` | 🔄 Convert |
| 3 | Roo Code | `.roorules` | → `.ai/SOUL.md` | 🔄 Convert |
| 4 | Cursor | `.cursor/cursor.rules` | → `../.ai/SOUL.md` | 🔄 Convert |
| 5 | Gemini | `.gemini/gemini.md` | → `../.ai/SOUL.md` | 🔄 Convert |
| 6 | Trae | `.trae/trae.rules` | → `../.ai/SOUL.md` | 🔄 Convert |
| 7 | Qoder | `.qoder/qoder.rules` | → `../.ai/SOUL.md` | 🔄 Convert |
| 8 | Kilo | `.kilo/kilo.rules` | → `../.ai/SOUL.md` | 🔄 Convert |
| 9 | KiloCode | `.kilocode/rules/soul.md` | → `../../.ai/SOUL.md` | 🔄 Convert |
| 10 | OpenCode | `.opencode/instructions/SOUL.md` | → `../../.ai/SOUL.md` | 🔄 Convert |
| 11 | GitHub Copilot | `.github/copilot-instructions.md` | → `../.ai/SOUL.md` | 🔄 Create |
| 12 | Junie | `.junie/guidelines.md` | → `../.ai/SOUL.md` | 🔄 Create |
| 13 | Zed | `.zed/.rules` | → `../.ai/SOUL.md` | 🔄 Create |
| 14 | Claude | `.claude/settings.json` | Reference to SOUL | 🔄 Create |
| 15 | Continue | `.continue/rules/soul.md` | → `../../.ai/SOUL.md` | 🔄 Create |

### Extension Modes (Keep)

| # | Extension | Path | Type |
|---|-----------|------|------|
| 1 | Roo | `.roomodes` | Custom modes |
| 2 | Kilo | `.kilo/agent-manager.json` | Agent routing |
| 3 | Continue | `.continue/config.json` | Config |

---

## 🛠️ Executable Scripts

### cleanup-execute-all.ps1
Master execution script that runs all phases:

```powershell
#requires -RunAsAdministrator

param([switch]$WhatIf)

Write-Host "VAPT-Secure AI Configuration Cleanup" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Phase 1: Backup
& $PSScriptRoot\cleanup-phase1-backup.ps1 -WhatIf:$WhatIf

# Phase 2: Archive Duplicates  
& $PSScriptRoot\cleanup-phase2-archive.ps1 -WhatIf:$WhatIf

# Phase 3: Create Symlinks
& $PSScriptRoot\cleanup-phase3-symlinks.ps1 -WhatIf:$WhatIf

# Phase 4: Skill Links
& $PSScriptRoot\cleanup-phase4-junctions.ps1 -WhatIf:$WhatIf

# Phase 5: Tier 3 Config
& $PSScriptRoot\cleanup-phase5-tier3.ps1 -WhatIf:$WhatIf

# Phase 6: Verify
Write-Host "`nRunning verification..." -ForegroundColor Green
if (-not $WhatIf) {
    php tools/verify-ai-config.php
}

Write-Host "`nCleanup Complete!" -ForegroundColor Green
if (-not $WhatIf) {
    Write-Host "Verify with: php tools/verify-ai-config.php" -ForegroundColor Cyan
}
```

---

## 🧪 Testing Matrix

After cleanup, verify in each IDE:

| IDE/Extension | How to Test | Expected Result |
|---------------|-------------|-----------------|
| Windsurf | Ask about .htaccess rules | References SOUL.md v2.5.9 |
| Roo Code | Switch to "vapt-security" mode | Loads SOUL context |
| Kilo Code | enable an agent | Routes via agent-manager.json |
| Cursor | Type @vapt | Shows SOUL context |
| Claude Code | Type /vapt-expert | Loads skill from .ai/skills/ |
| VS Code + Copilot | Open Copilot chat | Knows security rules |
| Antigravity | Query about features | Uses lifecycle rules |
| Continue | Type /vapt | Load SOUL context |

---

## 📦 Deliverables

### 1. Downloadable Package
```
Tools/Scripts/Archive/
├── cleanup-execute-all.ps1           ← MASTER SCRIPT
├── cleanup-phase1-backup.ps1
├── cleanup-phase2-archive.ps1
├── cleanup-phase3-symlinks.ps1
├── cleanup-phase4-junctions.ps1
├── cleanup-phase5-tier3.ps1
├── verify-ai-config.php
└── rollback-backup.ps1                 ← Restore from backup
```

### 2. Documentation
- This file: `VAPT-AI-CLEANUP-PROTOCOL-v1.0.md`
- Architecture plan: `VAPT-AI-DEV-PLAN-Universal-v1.0.md`

### 3. After-Cleanup State
- One canonical `.ai/SOUL.md`
- All editor rules are symlinks
- Skills shared via junctions
- Tier 3 extensions reference Tier 1

---

## ⏱️ Time Estimate

| Phase | Duration |
|-------|----------|
| Phase 1 (Backup) | 5 min |
| Phase 2 (Consolidate) | 10 min |
| Phase 3 (Symlinks) | 15 min |
| Phase 4 (Skill Links) | 10 min |
| Phase 5 (Tier 3 Config) | 15 min |
| Phase 6 (Verify) | 5 min |
| **Total** | **~60 minutes** |

---

## ⚠️ Prerequisites

- **Windows**: Run PowerShell as Administrator
- **macOS/Linux**: User with symlink creation permissions
- **Git**: Ensure clean working directory
- **Backup**: Verify backup script ran successfully

---

## 🔄 Rollback

If issues occur:
```powershell
.\tools\rollback-backup.ps1 -BackupDate "2026-03-27_143022"
```

Restores all files to pre-cleanup state.

---

*Cleanup Protocol v1.0 - Created 2026-03-27*
*Part of VAPT-Secure Universal AI Development Plan*
