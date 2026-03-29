# VAPT-Secure Universal AI Development Plan
> **Version**: 1.0.0 | **Status**: 🔴 [Need Review]
> **Date**: 2026-03-27 | **Applies Across**: Windsurf, VS Code, Antigravity, Roo Code, Kilo Code

---

## 📋 Executive Summary

This document establishes a **unified, hierarchical configuration system** for AI agents across all IDEs and extensions used in the VAPT-Secure project. It resolves the current fragmentation where multiple SOUL files exist with differing content across editor-specific directories.

### Current State Problems
| Issue | Impact |
|-------|--------|
| 5+ versions of SOUL.md with different content | Inconsistent AI behavior |
| Version mismatch (2.4.11 vs 2.4.13) | Confusion about current state |
| No clear symlink hierarchy | Manual sync required |
| Legacy `.agent/` vs new `.ai/` | Unclear which to use |
| Editor-specific overrides scattered | Hard to maintain |

### Target State
| Feature | Benefit |
|---------|---------|
| Single canonical SOUL.md | One edit propagates everywhere |
| Clear hierarchy | Know which config takes precedence |
| Editor-agnostic core | Works across all IDEs |
| Version enforcement | Automated sync verification |
| Migration path | Clean transition from legacy |

---

## 🏗️ Part 1: Configuration Hierarchy Architecture

### 1.1 The Three-Tier System

```
┌─────────────────────────────────────────────────────────────┐
│                    TIER 1: UNIVERSAL CORE                     │
│                      (.ai/SOUL.md)                          │
│  • Editor-agnostic rules                                    │
│  • Project identity & domain expertise                      │
│  • Mandatory security guardrails                            │
│  • Feature lifecycle rules                                  │
│  • Single source of truth                                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   TIER 2: EDITOR ADAPTATION                   │
│         (.cursor/cursor.rules, .windsurfrules, etc)         │
│  • Symlink to Tier 1 + Editor-specific additions            │
│  • Tool calling patterns                                    │
│  • IDE-specific workflows                                   │
│  • UI/UX considerations                                     │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   TIER 3: EXTENSION MODES                   │
│              (.roo/rules/soul.md, .roomodes)                │
│  • Roo-specific mode definitions                            │
│  • Kilo Code agent configurations                           │
│  • Capability scoping                                       │
│  • Role-specific instructions                               │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 File Responsibility Matrix

| File | Tier | Purpose | Can Override Tier 1? |
|------|------|---------|---------------------|
| `.ai/SOUL.md` | 1 | Universal behavior, security, lifecycle | N/A (canonical) |
| `.windsurfrules` | 2 | Windsurf-specific tool patterns | No, extends only |
| `.clinerules` | 2 | Claude Code CLI patterns | No, extends only |
| `.cursor/cursor.rules` | 2 | Cursor IDE integration | No, extends only |
| `.gemini/gemini.md` | 2 | Gemini/Antigravity specifics | No, extends only |
| `.kilo/kilo.rules` | 3 | Kilo Code agent config | Yes (for agent roles) |
| `.roo/rules/soul.md` | 3 | Roo Code base behavior | No, extends only |
| `.roomodes` | 3 | Roo custom modes | Yes (mode-specific) |

---

## 🔧 Part 2: The Canonical SOUL.md Structure

### 2.1 Required Sections (Tier 1)

Every Tier 1 SOUL.md MUST include these sections:

```markdown
# SOUL.md — Universal AI Configuration for VAPTSecure Plugin

## 🎯 Core Identity
## 🏗️ Project Context
## 🚫 Mandatory Rules
## 📋 Feature Lifecycle Rules
## 🔧 Technical Constraints
## 💬 Communication Style
## 🎓 Domain Expertise Areas
## 🔍 Troubleshooting
## 📚 Resources
```

### 2.2 Section Content Standards

#### 🎯 Core Identity
```markdown
**You are an AI agent specialized in WordPress security hardening and VAPT 
(Vulnerability Assessment & Penetration Testing) implementation.**

Your primary role is to:
1. Generate secure, production-ready security configurations
2. Ensure WordPress core, REST API, and admin endpoints remain fully accessible
3. Follow strict security best practices for `.htaccess` and server configurations
4. Maintain backward compatibility with existing plugin features
5. **Execute self-check automations** on all critical system lifecycle events
6. **Never hardcode domain names** — always use `{domain}` placeholder
```

#### 🚫 Mandatory Rules (CRITICAL)
```markdown
### Security Guardrails
1. **NEVER block WordPress admin paths**:
   - `/wp-admin/`, `/wp-login.php`, `/wp-json/wp/v2/`
   - `/wp-json/vaptsecure/v1/` (custom API)

2. **ALWAYS use .htaccess-safe directives only**:
   - ✅ Allowed: `RewriteEngine`, `RewriteCond`, `RewriteRule`
   - ✅ Allowed: `Header set`, `RequestHeader set`
   - ❌ Forbidden: `TraceEnable`, `<Directory>`, `ServerSignature`

3. **MUST insert rules at correct position**:
   - All custom rewrite rules MUST go `before_wordpress_rewrite`
   - WRONG: After `# END WordPress` comment

4. **MUST wrap in proper modules**:
   ```apache
   <IfModule mod_rewrite.c>
   # Your rewrite rules here
   </IfModule>
   ```
```

#### 📋 Feature Lifecycle Rules
```markdown
### State Transitions
| Transition | Required Actions |
|------------|------------------|
| Draft → Develop | Verify deps, apply .htaccess, set up tables, enable logging |
| Develop → Deploy | Run validation, disable logging, verify no conflicts, test REST API |
| Deploy → Draft | Remove ALL .htaccess rules, wipe data, remove configs, log operation |
```

### 2.3 Tier 2 Extension Pattern

Tier 2 files follow this template:

```markdown
# {Editor} Rules for VAPT-Secure

> **Symlink Source**: `.ai/SOUL.md` (Tier 1)
> **This File**: Tier 2 — Editor-specific extensions

---

## 🎯 Tier 1 Inheritance

This editor extends the universal SOUL.md from `.ai/SOUL.md`.
All Tier 1 rules are in effect unless explicitly extended below.

---

## 🛠️ {Editor} Specific Instructions

### Tool Calling Patterns
```

### Workflow Preferences

### UI Considerations
```
```

### 2.4 Tier 3 Extension Pattern (Roo/Kilo Modes)

```markdown
# {Mode} Mode Configuration

> **Base**: `.ai/SOUL.md` (Tier 1)
> **Extension**: `.roo/rules/soul.md` (Tier 2)
> **This Mode**: Tier 3 — Role-specific capabilities

## Mode Definition

### Role
{Detailed role description}

### Capabilities
- Can read: {file patterns}
- Can edit: {file patterns}
- Can execute: {commands}

### When to Activate
{Trigger conditions}

### Mode-Specific Instructions
{Instructions that extend/override lower tiers}
```

---

## 📁 Part 3: Directory Structure Standard

### 3.1 Unified Structure Across All Editors

```
{project-root}/
│
├── .ai/                              # Tier 1: Universal (SINGLE SOURCE OF TRUTH)
│   ├── SOUL.md                       # Canonical behavior definition
│   ├── AGENTS.md                     # Multi-agent orchestration spec
│   ├── VAPTSECURE.md                 # Project-specific context
│   ├── skills/                       # Shared skills repository
│   │   ├── vapt-expert/
│   │   │   ├── SKILL.md
│   │   │   └── README.md
│   │   └── security-auditor/
│   │       ├── SKILL.md
│   │       └── README.md
│   ├── workflows/                    # Reusable automation
│   │   ├── security-scan.yml
│   │   └── validation.yml
│   └── rules/                        # Editor-specific symlinks (T2)
│       ├── cursor.rules → ../SOUL.md
│       ├── gemini.md → ../SOUL.md
│       ├── claude-settings.json
│       └── opencode.md → ../SOUL.md
│
├── .cursor/                          # Tier 2: Cursor IDE
│   ├── cursor.rules → .ai/rules/cursor.rules
│   └── skills/ → ../.ai/skills/
│
├── .windsurf/                        # Tier 2: Windsurf
│   ├── windsurf.rules → ../.ai/SOUL.md
│   └── skills/ → ../.ai/skills/
│
├── .claude/                          # Tier 2: Claude Code
│   ├── CLAUDE.md                     # Claude-specific commands
│   ├── settings.json → ../.ai/rules/claude-settings.json
│   └── skills/ → ../.ai/skills/
│
├── .gemini/                          # Tier 2: Gemini/Antigravity
│   ├── gemini.md → ../.ai/rules/gemini.md
│   └── antigravity/
│       └── skills/ → ../../.ai/skills/
│
├── .roo/                             # Tier 3: Roo Code
│   ├── rules/
│   │   └── soul.md → ../../.ai/SOUL.md (T2 extension)
│   ├── skills/ → ../.ai/skills/
│   ├── roo.rules → ../.ai/SOUL.md
│   └── Plan/                         # Roo-specific plans
│
├── .roomodes                         # Tier 3: Roo custom modes
│
├── .kilo/                            # Tier 3: Kilo Code
│   ├── kilo.rules → ../.ai/SOUL.md
│   └── skills/ → ../.ai/skills/
│
├── .kilocode/                        # Tier 3: Kilo Code extended
│   ├── rules/
│   │   └── version-bump-policy.md
│   └── skills/ → ../.ai/skills/
│
├── .trae/                            # Tier 2: Trae
│   ├── trae.rules → ../.ai/SOUL.md
│   └── skills/ → ../.ai/skills/
│
├── .qoder/                           # Tier 2: Qoder
│   ├── qoder.rules → ../.ai/SOUL.md
│   └── skills/ → ../.ai/skills/
│
├── .opencode/                        # Tier 2: OpenCode
│   ├── instructions/
│   │   ├── INSTRUCTIONS.md
│   │   ├── SOUL.md → ../../.ai/SOUL.md
│   │   └── VERSION_BUMP_RULES.md
│   ├── commands/                     # Command definitions
│   └── skills/ → ../.ai/skills/
│
└── .agent/                           # LEGACY — DEPRECATED
    └── MIGRATION.md                  # Migration guide to .ai/
```

### 3.2 Symlink Convention

All Tier 2 and Tier 3 files that extend Tier 1 MUST use symlinks:

```bash
# Windows (Command Prompt - Administrator)
mklink .windsurfrules .ai\SOUL.md
mklink .clinerules .ai\SOUL.md
mklink .roorules .ai\SOUL.md

# Windows (PowerShell - Administrator)
New-Item -ItemType SymbolicLink -Path ".windsurfrules" -Target ".ai\SOUL.md"
New-Item -ItemType SymbolicLink -Path ".clinerules" -Target ".ai\SOUL.md"

# macOS/Linux
ln -s .ai/SOUL.md .windsurfrules
ln -s .ai/SOUL.md .clinerules
ln -s .ai/SOUL.md .roorules

# Directory symlinks for skills
ln -s ../.ai/skills .cursor/skills
ln -s ../../.ai/skills .gemini/antigravity/skills
```

### 3.3 File Priority Order

When an AI agent loads configuration, it MUST follow this priority:

```
1. System-level settings (lowest priority)
2. .ai/SOUL.md — Universal rules
3. Editor-specific .rules/.md — Tool preferences
4. Extension mode settings — Role scoping
5. In-session context (highest priority)
```

**Conflict Resolution**: Higher tiers CANNOT override Tier 1 security rules. They can only ADD capabilities or REFINE behavior.

---

## 🔄 Part 4: Version Synchronization Strategy

### 4.1 Version Management

Current problematic state:
- `.ai/SOUL.md`: Version 2.4.11
- `.kilo/kilo.rules`: Version 2.4.13

**Required**: Single version source

#### Solution: Version in CODE

Embed version in plugin code, reference from SOUL:

```php
// vaptsecure.php
if (! defined('VAPTSECURE_AI_CONFIG_VERSION')) {
    define('VAPTSECURE_AI_CONFIG_VERSION', '2.4.13');
}
```

```markdown
<!-- .ai/SOUL.md -->
## 🏗️ Project Context

**Project**: VAPTSecure WordPress Plugin
**Version**: 2.4.13 <!-- READ FROM: vaptsecure.php line 56 -->
**Domain**: WordPress Security & Vulnerability Management
```

### 4.2 Sync Verification Script

Create automated check:

```php
<?php
// tools/verify-ai-config.php
/**
 * Verifies all AI configuration files are in sync
 * Run: php tools/verify-ai-config.php
 */

$plugin_file = 'vaptsecure.php';
$core_version = extract_version_from_plugin($plugin_file);

$config_files = [
    '.ai/SOUL.md', '.windsurfrules', '.clinerules',
    '.roo/rules/soul.md', '.kilo/kilo.rules'
];

$errors = [];
foreach ($config_files as $file) {
    if (!file_exists($file)) {
        $errors[] = "Missing: $file";
        continue;
    }
    
    $content = file_get_contents($file);
    if (!preg_match('/Version:\s*([\d.]+)/', $content, $matches)) {
        $errors[] = "No version found in: $file";
    } elseif ($matches[1] !== $core_version) {
        $errors[] = "Version mismatch in $file: {$matches[1]} (expected $core_version)";
    }
    
    // Check for symlink (Tier 2+ should be symlinks)
    if ($file !== '.ai/SOUL.md' && !is_link($file)) {
        $errors[] = "Not a symlink: $file";
    }
}

if (empty($errors)) {
    echo "✅ All AI configuration files are in sync (Version: $core_version)\n";
    exit(0);
} else {
    echo "❌ Configuration sync issues found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
```

### 4.3 Pre-Commit Hook

Add to `.git/hooks/pre-commit` (or `.git/hooks/pre-commit.sample` renamed):

```bash
#!/bin/bash
# Verify AI config sync before allowing commit
php tools/verify-ai-config.php
if [ $? -ne 0 ]; then
    echo "Commit blocked: AI configuration out of sync"
    exit 1
fi
```

---

## 🧬 Part 5: Editor-Specific Configurations

### 5.1 Windsurf (.windsurfrules)

**File**: `.windsurfrules` (symlink to `.ai/SOUL.md`)
**Extension**: `.windsurf/windsurf.ext.md` (if needed)

```markdown
# .windsurfrules → .ai/SOUL.md

## Windsurf-Specific Additions

### Cascade Panel Behavior
When responding in Cascade:
- Use the chat panel for explanations
- Use code blocks for any code being suggested
- Highlight security-critical sections

### Inline Code Settings
When working with code:
- Prefer explanations in natural language
- Provide complete, copy-pasteable code blocks
- Never suggest partial edits that break syntax

### Preview Mode
When preview changes:
- Trigger browser refresh after file changes
- Verify REST API endpoints still respond
- Check for 500 errors after .htaccess modifications
```

### 5.2 VS Code + Roo Code (.roo/rules/soul.md, .roomodes)

**Base**: `.roo/rules/soul.md` (symlink to `.ai/SOUL.md`)
**Modes**: `.roomodes` (custom mode definitions)

Example `.roomodes` entry:

```yaml
customModes:
  - slug: vapt-security
    name: 🔒 VAPT Security
    roleDefinition: |-
      You are a VAPT (Vulnerability Assessment & Penetration Testing) security specialist
      working with Apache .htaccess rules and WordPress security configurations.
      
      Base behavior extends from .ai/SOUL.md with these additional constraints:
      - Prioritize security over convenience
      - Always whitelist WordPress core paths before applying blocking rules
      - Verify rule syntax with self-check before delivering
    whenToUse: "Creating or modifying security enforcement rules, .htaccess configurations"
    description: "Security-focused VAPT rule generation"
    groups:
      - read
      - edit
      - fileRegex: (\.htaccess|\.conf)$
        description: Server configuration files
```

### 5.3 VS Code + Kilo Code (.kilo/kilo.rules)

**File**: `.kilo/kilo.rules` (symlink to `.ai/SOUL.md`)
**Extension**: `.kilo/agent-manager.json` (agent configuration)

```json
// .kilo/agent-manager.json
{
  "agents": [
    {
      "id": "vapt-expert",
      "name": "VAPT Security Expert",
      "baseInstructions": ".ai/SOUL.md",
      "capabilities": ["htaccess", "nginx", "security-rules"],
      "triggers": ["security", "htaccess", "risk"]
    },
    {
      "id": "schema-builder",
      "name": "Schema Builder",
      "baseInstructions": ".ai/skills/vaptschema-builder/SKILL.md",
      "capabilities": ["json", "schema-validation"],
      "triggers": ["schema", "interface", "json"]
    }
  ],
  "routing": {
    "defaultAgent": "vapt-expert",
    "contextKeywords": {
      "security": "vapt-expert",
      "schema": "schema-builder",
      "htaccess": "vapt-expert"
    }
  }
}
```

### 5.4 Antigravity / Gemini (.gemini/gemini.md)

**File**: `.gemini/gemini.md` (symlink) + `.gemini/antigravity/` folder

```markdown
# gemini.md → .ai/SOUL.md

## Antigravity-Specific Context

### Agent Selection
When using Antigravity:
- Select the "Code" agent for implementation tasks
- Select the "Architecture" agent for structural decisions
- Select the "Security" agent for audit tasks

### Prompt Patterns
Structure prompts as:
1. Context (what exists)
2. Goal (what to achieve)
3. Constraints (from .ai/SOUL.md)
4. Output format (code, explanation, plan)
```

### 5.5 Cursor (.cursor/cursor.rules)

**File**: `.cursor/cursor.rules` (symlink via `.ai/rules/cursor.rules`)

```markdown
# cursor.rules → .ai/rules/cursor.rules → .ai/SOUL.md

## Cursor-Specific Instructions

### Composer Integration
When modifying code:
- Use Composer for dependency management
- Run `composer validate` before committing composer.json
- Never commit vendor/ directory

### .cursorignore
Respect project .cursorignore patterns for:
- vendor/
- node_modules/
- backups/
- *.log
- data/uploaded/

### Tab Completion Behavior
For security-sensitive files (.htaccess, wp-config.php):
- Show full context before suggesting completions
- Warn before modifying production configs
- Suggest backup creation
```

### 5.6 Claude Code (.claude/CLAUDE.md)

**File**: `.claude/CLAUDE.md` (commands reference)
**Settings**: `.claude/settings.json` (symlink)

Claude Code uses its own mechanism. The CLAUDE.md provides command reference while respecting Tier 1 SOUL.

```markdown
# CLAUDE.md

## Commands Reference

See `.ai/SOUL.md` for core behavior.

### PHP Quality Tools
- `composer run lint` - Check coding standards
- `composer run analyze` - PHPStan static analysis
- `composer run test` - PHPUnit tests

### Claude-Specific
When using Claude Code:
- All Tier 1 SOUL rules apply automatically
- Use /help for Claude-specific commands
- Use /compact to manage context window
```

---

## 🗺️ Part 6: Migration from Legacy (.agent/)

### 6.1 Current Legacy Structure

```
.agent/
├── ARCHITECTURE.md           # → keep as reference, mark deprecated
├── agents/                   # → evaluate vs .ai/skills/
│   ├── backend-specialist.md
│   ├── frontend-specialist.md
│   └── ... (20 agents)
├── skills/                   # → migrate to .ai/skills/
│   ├── api-patterns/
│   ├── app-builder/
│   └── ... (36 skills)
├── workflows/                # → migrate to .ai/workflows/
│   ├── create.md
│   ├── debug.md
│   └── ... (11 workflows)
└── rules/
    └── GEMINI.md             # → move to .ai/rules/
```

### 6.2 Migration Decision Matrix

| Legacy Content | Action | Destination |
|---------------|--------|-------------|
| ARCHITECTURE.md | Archive with migration notice | `/docs/ARCHITECTURE-LEGACY.md` |
| agents/* | Evaluate | Merge into .ai/skills/ or deprecate |
| skills/* | Migrate | `.ai/skills/` (deduplicate with existing) |
| workflows/* | Migrate | `.ai/workflows/` |
| rules/GEMINI.md | Move | `.ai/rules/gemini.md` |

### 6.3 Migration Script

```bash
#!/bin/bash
# migrate-agent-to-ai.sh

echo "Migrating .agent/ to .ai/ structure..."

# Create .ai structure
mkdir -p .ai/{skills,workflows,rules}

# Migrate skills (skip if exists in destination)
for skill in .agent/skills/*/; do
    name=$(basename "$skill")
    if [ ! -d ".ai/skills/$name" ]; then
        cp -r "$skill" ".ai/skills/$name"
        echo "✓ Migrated skill: $name"
    else
        echo "⚠ Skipped (exists): $name"
    fi
done

# Migrate workflows
cp -r .agent/workflows/* .ai/workflows/
echo "✓ Migrated workflows"

# Move rules
mv .agent/rules/* .ai/rules/
echo "✓ Migrated rules"

# Mark legacy as migrated
cat > .agent/MIGRATION.md << 'EOF'
# .agent/ Directory — DEPRECATED

This directory contains legacy AI agent configuration.
All active configuration has moved to `.ai/`.

## Migration Date
$(date +%Y-%m-%d)

## New Locations
- Skills: `.ai/skills/`
- Workflows: `.ai/workflows/`
- Rules: `.ai/rules/`
- Universal SOUL: `.ai/SOUL.md`

## When to Delete This Directory
After 30 days of successful operation with new structure.
EOF

echo "Migration complete. Review .agent/MIGRATION.md"
```

---

## ✅ Part 7: Implementation Checklist

### Phase 1: Cleanup & Analysis (Day 1)

- [ ] Run `tools/verify-ai-config.php` to document current drift
- [ ] Create backup of all .{editor} directories
- [ ] Identify unique content in each SOUL variant
- [ ] Document which content should be universal vs editor-specific

### Phase 2: Consolidate Tier 1 (Days 2-3)

- [ ] Merge all SOUL variants into `.ai/SOUL.md`
- [ ] Resolve version conflicts (pick highest: 2.4.13)
- [ ] Add versioning extraction from `vaptsecure.php`
- [ ] Update `.ai/AGENTS.md` with multi-agent orchestration
- [ ] Create `.ai/VAPTSECURE.md` with project context

### Phase 3: Create Tier 2 Symlinks (Days 4-5)

- [ ] Replace `.windsurfrules` with symlink to `.ai/SOUL.md`
- [ ] Replace `.clinerules` with symlink to `.ai/SOUL.md`
- [ ] Replace `.roorules` with symlink to `.ai/SOUL.md`
- [ ] Create `.cursor/cursor.rules` symlink
- [ ] Create `.kilo/kilo.rules` symlink
- [ ] Create `.trae/trae.rules` symlink
- [ ] Create `.qoder/qoder.rules` symlink
- [ ] Verify all symlinks work: `cat .windsurfrules | head -5`

### Phase 4: Configure Tier 3 Extensions (Days 6-7)

- [ ] Update `.roomodes` to reference `.ai/SOUL.md`
- [ ] Create `.kilo/agent-manager.json` for agent routing
- [ ] Update `.roo/rules/soul.md` as Tier 2 extension
- [ ] Configure extension-specific mode definitions

### Phase 5: Legacy Migration (Days 8-9)

- [ ] Run `migrate-agent-to-ai.sh`
- [ ] Deduplicate skills (keep `.ai/skills/` versions)
- [ ] Archive `.agent/ARCHITECTURE.md` to `/docs/`
- [ ] Create `.agent/MIGRATION.md`

### Phase 6: Validation & Documentation (Day 10)

- [ ] Run `tools/verify-ai-config.php` — should pass
- [ ] Test in Windsurf: verify rules load
- [ ] Test in VS Code + Roo: verify modes work
- [ ] Test in VS Code + Kilo: verify agents work
- [ ] Test in Antigravity: verify context loads
- [ ] Update main README with configuration diagram

---

## 🧪 Part 8: Validation Testing

### 8.1 Automated Tests

Create test suite:

```php
<?php
// tests/ai-config/ConfigHierarchyTest.php

class ConfigHierarchyTest extends TestCase {
    
    public function testTier1Exists() {
        $this->assertFileExists('.ai/SOUL.md');
    }
    
    public function testTier2AreSymlinks() {
        $this->assertTrue(is_link('.windsurfrules'));
        $this->assertTrue(is_link('.clinerules'));
        $this->assertTrue(is_link('.roorules'));
    }
    
    public function testVersionConsistency() {
        $pluginVersion = $this->extractVersion('vaptsecure.php');
        $soulVersion = $this->extractVersion('.ai/SOUL.md');
        $this->assertEquals($pluginVersion, $soulVersion);
    }
    
    public function testMandatorySectionsPresent() {
        $content = file_get_contents('.ai/SOUL.md');
        $this->assertStringContains('## 🎯 Core Identity');
        $this->assertStringContains('## 🚫 Mandatory Rules');
        $this->assertStringContains('## 📋 Feature Lifecycle Rules');
    }
}
```

### 8.2 Manual Verification Matrix

| Editor/Extension | Test | Expected Result | Status |
|------------------|------|-----------------|--------|
| Windsurf | Open project, ask about security rules | References SOUL.md content | ⬜ |
| Roo Code | Switch to "vapt-security" mode | Mode loads, shows SOUL context | ⬜ |
| Kilo Code | Ask about htaccess | Routes to vapt-expert agent | ⬜ |
| Antigravity | Query about feature lifecycle | Uses lifecycle rules from SOUL | ⬜ |
| Cursor | Modify .htaccess | Warns about WordPress paths | ⬜ |
| Claude | Run validation | Suggests composer commands from CLAUDE.md | ⬜ |

---

## 📚 Part 9: Quick Reference

### File Locations

| What | Where |
|------|-------|
| Canonical SOUL | `.ai/SOUL.md` |
| Multi-agent spec | `.ai/AGENTS.md` |
| Project context | `.ai/VAPTSECURE.md` |
| Skills repository | `.ai/skills/` |
| Automation workflows | `.ai/workflows/` |
| Editor symlinks | `.ai/rules/` |
| Verification script | `tools/verify-ai-config.php` |
| Migration script | `tools/migrate-agent-to-ai.sh` |

### Symlink Commands Summary

```bash
# Essential symlinks to create
ln -s .ai/SOUL.md .windsurfrules
ln -s .ai/SOUL.md .clinerules
ln -s .ai/SOUL.md .roorules

# Directory links
ln -s ../.ai/skills .cursor/skills
ln -s ../../.ai/skills .gemini/antigravity/skills
```

### Version Check

```bash
php tools/verify-ai-config.php
```

---

## 🔄 Version History

| Date | Version | Changes |
|------|---------|---------|
| 2026-03-27 | 1.0.0 | Initial comprehensive development plan |

---

*This plan provides a complete specification for unifying AI agent configuration across all IDEs and extensions used in the VAPT-Secure project.*
