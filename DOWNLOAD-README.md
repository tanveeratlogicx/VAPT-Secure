# VAPT-Secure AI Configuration Cleanup Package
> **Downloaded**: 2026-03-27  
> **Package Version**: 1.0

---

## 📦 What's Included

This package contains everything you need to unify your fragmented AI configuration:

### 📄 Documents
| File | Purpose |
|------|---------|
| `plans/VAPT-AI-DEV-PLAN-Universal-v1.0.md` | Comprehensive architecture plan (700+ lines) |
| `plans/VAPT-AI-CLEANUP-PROTOCOL-v1.0.md` | Detailed cleanup protocol with all files to archive/delete |
| `tools/cleanup-execute-all.ps1` | **MASTER SCRIPT** - Run this to execute all cleanup phases |
| `tools/verify-ai-config.php` | Verification script to check sync status |
| `tools/setup-ai-hierarchy.ps1` | Alternative setup script (lighter weight) |

### 📁 Current Status
```
🔴 CRITICAL ISSUE DETECTED
Multiple standalone SOUL.md files with DIFFERENT content:

.ai/SOUL.md              → Version 2.5.9 ✓ (Canonical - KEEP)
.kilo/kilo.rules         → Version 2.4.13 ✗ (Standalone - Convert to symlink)
.trae/trae.rules         → Version 2.4.11 ✗ (Standalone - Convert to symlink)
.windsurfrules           → Unknown ✗ (Standalone - Convert to symlink)
.clinerules              → Unknown ✗ (Standalone - Convert to symlink)
.roorules                → Unknown ✗ (Standalone - Convert to symlink)

Plus 5+ duplicate SOUL variants in .ai/ to archive
```

---

## 🚀 Quick Start (Windows)

### Step 1: Review the Documentation
Read these first:
- `plans/VAPT-AI-CLEANUP-PROTOCOL-v1.0.md` — What will change and why

### Step 2: Dry Run
```powershell
# Open PowerShell AS ADMINISTRATOR
# Navigate to project root

# Dry run first - shows what WOULD change:
powershell -ExecutionPolicy Bypass -File tools/cleanup-execute-all.ps1 -WhatIf
```

### Step 3: Execute Cleanup
```powershell
# If everything looks good, run without -WhatIf:
powershell -ExecutionPolicy Bypass -File tools/cleanup-execute-all.ps1
```

This will:
1. ✅ Backup existing config to `.archive/ai-cleanup-{timestamp}/`
2. ✅ Archive duplicate SOUL files
3. ✅ Convert standalone rules to symlinks pointing to `.ai/SOUL.md`
4. ✅ Create skill directory junctions
5. ✅ Setup Tier 3 extension configs (Roo, Kilo, Continue, etc.)
6. ✅ Run verification

---

## 🎯 What The Cleanup Fixes

### Before (Current - Fragmented)
```
AI receives DIFFERENT instructions depending on IDE:

Windsurf → .windsurfrules (v?)
Roo Code → .clinerules (v?)
Kilo Code → .kilo/kilo.rules (2.4.13)
Trae → .trae/trae.rules (2.4.11)
Cursor → .cursor/cursor.rules (?)
Claude → .ai/SOUL.md (2.5.9)

Result: Inconsistent behavior across IDEs
```

### After (Target - Unified)
```
ALL IDEs receive same instructions:
.windsurfrules → .ai/SOUL.md (2.5.9) ───┐
.clinerules → .ai/SOUL.md (2.5.9) ──────┤
.kilo/kilo.rules → .ai/SOUL.md (2.5.9) ─├─ ALL SAME
.trae/trae.rules → .ai/SOUL.md (2.5.9) ─┤
.cursor/cursor.rules → .ai/SOUL.md ─────┘

Result: Consistent behavior across all IDEs
```

---

## 📋 Manual Verification

After running the script, verify with PHP:
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

## 🔄 Rollback

If something goes wrong, restore from backup:
```powershell
# Locate your backup
dir .archive\ai-cleanup-*

# Restore (manual - copy files back)
copy .archive\ai-cleanup-20260327_143022\* .
```

---

## 📂 File Structure After Cleanup

```
vaptsecure/
│
├── .ai/                          ← Tier 1: Universal (KEEP)
│   ├── SOUL.md                   ← Single source of truth
│   ├── AGENTS.md                 ← Multi-agent orchestration
│   ├── VAPTSECURE.md             ← Project context
│   ├── skills/                   ← Shared skills
│   └── rules/
│       ├── cursor.rules → ../SOUL.md    ← symlink
│       └── gemini.md → ../SOUL.md       ← symlink
│
├── .windsurfrules → .ai/SOUL.md  ← Tier 2: Symlink
├── .clinerules → .ai/SOUL.md     ← Tier 2: Symlink
├── .roorules → .ai/SOUL.md       ← Tier 2: Symlink
│
├── .cursor/
│   ├── cursor.rules → ../.ai/SOUL.md    ← symlink
│   └── skills/ → ../../.ai/skills      ← junction
│
├── .kilo/
│   ├── kilo.rules → ../.ai/SOUL.md     ← symlink
│   ├── agent-manager.json              ← Tier 3: Extension config
│   └── skills/ → ../../.ai/skills     ← junction
│
├── .roo/
│   ├── roo.rules → ../.ai/SOUL.md     ← symlink
│   ├── rules/soul.md → ../.ai/SOUL.md ← symlink
│   └── skills/ → ../../.ai/skills     ← junction
│
├── .github/
│   └── copilot-instructions.md → ../.ai/SOUL.md  ← symlink
│
├── .archive/                     ← Backup & archived files
│   └── ai-cleanup-20260327_143022/   ← Timestamped backup
│       ├── .ai/ (pre-cleanup)
│       └── archived-duplicates/
│           ├── SOUL-Claude.md
│           └── ... (old variants)
│
└── tools/
    ├── cleanup-execute-all.ps1
    ├── verify-ai-config.php
    └── setup-ai-hierarchy.ps1
```

---

## 🎓 How It Works

### Three-Tier Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    TIER 1: UNIVERSAL                        │
│                      (.ai/SOUL.md)                        │
│  • Single source of truth                                 │
│  • Editor-agnostic rules                                    │
│  • Security guardrails (WordPress paths, .htaccess)         │
│  • Feature lifecycle rules                                  │
└─────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   TIER 2: EDITOR LINK                     │
│         (.windsurfrules, .clinerules, etc.)               │
│  • Symlinks to Tier 1                                     │
│  • Editor-specific additions only                         │
│  • No content, just references                            │
└─────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   TIER 3: EXTENSION MODES                 │
│              (.roomodes, .kilo/agent-manager.json)        │
│  • Custom mode definitions                                  │
│  • Agent routing configurations                             │
│  • Override/extend Tier 1                                 │
└─────────────────────────────────────────────────────────────┘
```

**Key Principle**: Edit `.ai/SOUL.md` once — changes propagate to ALL editors automatically.

---

## ☁️ Important Note: Claude & Claude Code

You mentioned Claude support specifically. Here are the Claude configurations:

### Claude Code (CLI Tool)
- **Location**: `.claude/` directory
- **Config**: `.claude/settings.json` (references `.ai/SOUL.md`)
- **Docs**: `.claude/CLAUDE.md` (tool commands reference)

### Claude Desktop App
- Currently uses its own conversation-based system
- **Recommendation**: Use Claude Code CLI + `.claude/CLAUDE.md` for structured workflows
- **Or**: Copy relevant sections from `.ai/SOUL.md` into Claude Desktop as "Projects" instructions

### Claude for VS Code
- Can read `.claude/CLAUDE.md` for context
- Can also use `.clinerules` symlink (if using the Roo/Kilo extensions)

---

## 🧪 Testing Checklist

After cleanup, test each IDE:

| IDE/Extension | How to Test | Expected |
|---------------|-------------|----------|
| Windsurf | Ask about .htaccess | References SOUL.md |
| Roo Code | Switch mode /mode-name | Loads SOUL context |
| Kilo Code | Enable agent | Routes via agent-manager |
| Cursor | Type @vapt | Shows SOUL context |
| Claude Code | /vapt-expert | Loads skill |
| GitHub Copilot | Open Copilot chat | Knows security rules |
| Antigravity | Query features | Uses lifecycle rules |

---

## 📞 Troubleshooting

### Issue: "Not a reparse point"
**Problem**: Symlink creation is failing  
**Solution**: Run PowerShell as Administrator

### Issue: Version still shows 2.4.11 in some places
**Problem**: Symlinks not created, old files remain  
**Solution**: Check backup directory, re-run cleanup script

### Issue: IDE not recognizing symlinks
**Problem**: IDE doesn't dereference symlinks  
**Solution**: Use hardlink instead (New-Item -ItemType HardLink)

### Issue: PHP verification script fails
**Problem**: Syntax error or missing PHP  
**Solution**: Ensure PHP is installed and in PATH

---

## 📚 Next Steps

1. **Review**: Read the cleanup protocol document
2. **Backup**: Ensure you have a git commit or manual backup
3. **Dry Run**: Run with `-WhatIf` first
4. **Execute**: Run without `-WhatIf` to apply changes
5. **Verify**: Run verification script
6. **Test**: Open your favorite IDE and test
7. **Commit**: `git add . && git commit -m "Unified AI config to .ai/SOUL.md"`

---

## 💡 Pro Tips

- **Always edit `.ai/SOUL.md`** directly — never the symlinks
- **Keep verification script handy**: Run it after any SOUL changes
- **Version in sync**: Update version in SOUL.md and plugin file together
- **Archive, don't delete**: Old files are moved to `.archive/` for rollback

---

**Questions?** Review the detailed documents in `plans/` for complete specifications.

*Generated by BrowserOS — VAPT-Secure AI Configuration Cleanup Package*
