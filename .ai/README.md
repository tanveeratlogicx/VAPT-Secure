# Universal AI Configuration Ruleset

> **VAPTSecure Plugin - AI Configuration Standard**
> Version: 1.0.0
> Last Updated: March 2025

---

## 🎯 Overview

This `.ai/` directory implements the **Universal AI Configuration Ruleset** - a cross-editor compatible configuration system that unifies AI agent behavior across all editors and IDEs.

## 📁 Directory Structure

```
.ai/
├── README.md                    # This documentation
├── SOUL.md                      # Universal rules (cross-editor compatible)
├── AGENTS.md                    # Multi-agent orchestration guide
├── skills/                      # Portable Agent Skills
│   ├── vapt-expert/            # VAPT Security Expert skill
│   └── security-auditor/       # Security Audit skill
├── workflows/                  # Reusable workflows
│   ├── security-scan.yml
│   └── validation.yml
└── rules/                      # Editor-specific rule symlinks
    ├── cursor.rules -> ../SOUL.md
    └── gemini.md -> ../SOUL.md
```

---

## 🔗 Editor-Specific Symlinks

This configuration uses symlinks to maintain a single source of truth:

### Cursor Editor (.cursor/)
```
.cursor/
└── skills/ -> ../../.ai/skills/     # Reusable skills
└── cursor.rules -> ../.ai/rules/cursor.rules
```

### Gemini/Antigravity (.gemini/)
```
.gemini/
└── antigravity/
    └── skills/ -> ../../../.ai/skills/
└── gemini.md -> ../.ai/rules/gemini.md
```

### Claude Code (.claude/)
```
.claude/
└── skills/ -> ../../.ai/skills/
└── settings.json -> ../.ai/rules/claude-settings.json
```

### Qoder (.qoder/)
```
.qoder/
└── skills/ -> ../../.ai/skills/
└── qoder.rules -> ../.ai/SOUL.md
```

### Trae (.trae/)
```
.trae/
└── skills/ -> ../../.ai/skills/
└── trae.rules -> ../.ai/SOUL.md
```

### Windsurf (.windsurf/)
```
.windsurf/
└── skills/ -> ../../.ai/skills/
.windsurfrules -> .ai/SOUL.md
```

### Kilo Code (.kilo/)
```
.kilo/
└── skills/ -> ../../.ai/skills/
└── kilo.rules -> ../.ai/SOUL.md
```

### Roo Code (.roo/)
```
.roo/
└── skills/ -> ../../.ai/skills/
└── roo.rules -> ../.ai/SOUL.md
```

### VS Code (.vscode/)
```
.vscode/
└── settings.json                  # Editor settings only
```

---

## 🚀 Quick Start

### For Cursor Users
1. The `.cursor/` directory contains symlinks to `.ai/skills/`
2. Edit rules in `.ai/SOUL.md` - changes apply to all editors
3. Use @vapt-expert to invoke the VAPT expert skill

### For Claude Users
1. The `.claude/` directory contains symlinks to `.ai/skills/`
2. Skills are automatically available in Claude Code

### For Gemini Users
1. The `.gemini/` directory contains symlinks to `.ai/skills/`
2. Use `--expert vapt` flag to load the VAPT expert

---

## 📋 Workflow Integration

### Trigger: Reset to Draft

When a Feature transitions from **Develop → Draft** and the user clicks **"Confirm Reset (Wipe Data)"** on the "Reset to Draft" modal, this workflow executes:

```mermaid
flowchart TD
    A[Feature Transition: Develop → Draft] --> B[Reset to Draft Modal]
    B --> C[User Clicks: Confirm Reset]
    C --> D[Invoke: `reset-to-draft.workflow`]
    D --> E[Remove .htaccess rules]
    D --> F[Wipe feature data from DB]
    D --> G[Remove config files]
    D --> H[Log reset operation]
    H --> I[Return to Draft state]
```

### Actions Performed:
1. **Remove .htaccess/config rules** added during deployment
2. **Wipe feature-specific data** from WordPress database
3. **Remove generated config files** for this feature
4. **Log the reset operation** in `vapt_feature_history@Draft`
5. **Update feature state** to Draft

### Hook Implementation:
See `.ai/workflows/reset-to-draft.yml` for the automated workflow definition.

---

## 🔧 Skill Development

### Adding a New Skill

1. Create skill directory: `.ai/skills/your-skill/`
2. Add `SKILL.md` with proper metadata header
3. Update `.ai/AGENTS.md` with orchestration rules
4. Test across all editors

### Skill Metadata Format

```yaml
---
name: Skill Name
description: What this skill does
version: "1.0.0"
triggers: ["@skill-name", "keyword"]
editors: [cursor, claude, gemini]
---
```

---

## 📝 Configuration Rules

### Universal Rules (SOUL.md)
- **Location**: `.ai/SOUL.md`
- **Purpose**: Cross-editor behavior definition
- **Applies To**: All AI agents regardless of editor

### Editor-Specific Rules
- **Location**: `.ai/rules/*.md`
- **Purpose**: Editor-specific optimizations
- **Type**: Symlinks to SOUL.md

### Agent Orchestration (AGENTS.md)
- **Location**: `.ai/AGENTS.md`
- **Purpose**: Multi-agent collaboration rules
- **Scope**: Complex workflows requiring multiple agents

---

## 🛡️ Security Considerations

1. **Never commit API keys** to this repository
2. **Use environment-specific configs** outside version control
3. **All .htaccess rules** must include WordPress whitelist paths
4. **Validate all file operations** before execution

---

## 🤝 Contributing

When modifying this configuration:
1. Edit `.ai/SOUL.md` for universal rules
2. Update `.ai/AGENTS.md` for orchestration changes
3. Test in multiple editors before committing
4. Document new workflows in this README

---

## 📚 References

- [VAPTSecure Plugin Documentation](../README.md)
- [Feature Lifecycle Workflow](../.agent/workflows/transition-to-develop.md)
- [VAPTSchema Builder Skill](skills/vapt-expert/SKILL.md)

---

*Generated for VAPTSecure WordPress Plugin v2.4.13*
