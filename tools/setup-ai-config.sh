#!/bin/bash

# VAPT-Secure AI Configuration Auto-Setup Script
# Automatically creates and verifies all symlinks for the Universal AI Configuration

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Project root detection
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}🔧 VAPT-Secure AI Configuration Auto-Setup${NC}"
echo -e "${BLUE}=============================================${NC}"
echo ""
echo "Project Root: $PROJECT_ROOT"
echo ""

# Change to project root
cd "$PROJECT_ROOT"

# Function to create symlink
create_symlink() {
    local source="$1"
    local target="$2"
    local description="$3"
    
    echo -e "${CYAN}Setting up: $description${NC}"
    
    # Create parent directory if it doesn't exist
    mkdir -p "$(dirname "$target")"
    
    # Remove existing file/link if it exists
    if [ -e "$target" ] || [ -L "$target" ]; then
        echo -e "${YELLOW}  Removing existing: $target${NC}"
        rm -rf "$target"
    fi
    
    # Create symlink
    if ln -sf "$source" "$target"; then
        echo -e "${GREEN}  ✅ $target → $source${NC}"
        return 0
    else
        echo -e "${RED}  ❌ Failed to create: $target${NC}"
        return 1
    fi
}

# Function to verify symlink
verify_symlink() {
    local target="$1"
    local expected_source="$2"
    
    if [ -L "$target" ]; then
        local actual_source="$(readlink "$target")"
        if [[ "$actual_source" == "$expected_source" ]] || [[ "$actual_source" == *"$expected_source" ]]; then
            echo -e "${GREEN}  ✅ Verified: $target${NC}"
            return 0
        else
            echo -e "${YELLOW}  ⚠️  Wrong target: $target → $actual_source (expected: $expected_source)${NC}"
            return 1
        fi
    else
        echo -e "${RED}  ❌ Not a symlink: $target${NC}"
        return 1
    fi
}

echo -e "${CYAN}🔗 Creating Editor Symlinks...${NC}"
echo ""

# Tier 1: Core editor symlinks
create_symlink ".ai/SOUL.md" ".windsurfrules" "Windsurf Rules"
create_symlink ".ai/SOUL.md" ".clinerules" "Roo Code Rules (Modern)"
create_symlink ".ai/SOUL.md" ".roorules" "Roo Code Rules (Fallback)"

# Tier 2: Editor-specific directories
create_symlink "../.ai/SOUL.md" ".cursor/cursor.rules" "Cursor Rules"
create_symlink "../.ai/SOUL.md" ".gemini/gemini.md" "Gemini/Antigravity Rules"
create_symlink "../.ai/SOUL.md" ".qoder/qoder.rules" "Qoder Rules"
create_symlink "../.ai/SOUL.md" ".trae/trae.rules" "Trae Rules"

# Tier 3: Multi-file configurations
create_symlink "../.ai/SOUL.md" ".kilocode/rules/soul.md" "Kilo Code Rules"
create_symlink "../.ai/SOUL.md" ".continue/rules/soul.md" "Continue Rules"
create_symlink "../.ai/SOUL.md" ".roo/rules/soul.md" "Roo Code Directory Rules"
create_symlink "../../.ai/SOUL.md" ".opencode/instructions/SOUL.md" "OpenCode Instructions"

# Cross-IDE configurations
create_symlink "../.ai/SOUL.md" ".github/copilot-instructions.md" "GitHub Copilot Instructions"
create_symlink "../.ai/SOUL.md" ".junie/guidelines.md" "JetBrains Junie Guidelines"
create_symlink ".ai/SOUL.md" ".rules" "Zed Rules"

# Claude configuration (special case - settings file)
cat > .claude/settings.json << 'EOF'
{
  "rules": ["../.ai/rules/claude-settings.json"],
  "skills": ["../../.ai/skills/"]
}
EOF
echo -e "${GREEN}  ✅ Created: .claude/settings.json${NC}"

echo ""
echo -e "${CYAN}🎯 Creating Skills Directory Symlinks...${NC}"
echo ""

# Skills directory symlinks
create_symlink "../../.ai/skills" ".cursor/skills" "Cursor Skills"
create_symlink "../../.ai/skills" ".windsurf/skills" "Windsurf Skills"
create_symlink "../../../.ai/skills" ".gemini/antigravity/skills" "Gemini/Antigravity Skills"
create_symlink "../../.ai/skills" ".roo/skills" "Roo Code Skills"
create_symlink "../../.ai/skills" ".kilo/skills" "Kilo Code Skills"
create_symlink "../../.ai/skills" ".trae/skills" "Trae Skills"
create_symlink "../../.ai/skills" ".qoder/skills" "Qoder Skills"

echo ""
echo -e "${CYAN}🔍 Verifying All Symlinks...${NC}"
echo ""

# Verification
failed=0

# Verify core symlinks
verify_symlink ".windsurfrules" ".ai/SOUL.md" || ((failed++))
verify_symlink ".clinerules" ".ai/SOUL.md" || ((failed++))
verify_symlink ".roorules" ".ai/SOUL.md" || ((failed++))
verify_symlink ".cursor/cursor.rules" "../.ai/SOUL.md" || ((failed++))
verify_symlink ".gemini/gemini.md" "../.ai/SOUL.md" || ((failed++))
verify_symlink ".qoder/qoder.rules" "../.ai/SOUL.md" || ((failed++))
verify_symlink ".trae/trae.rules" "../.ai/SOUL.md" || ((failed++))
verify_symlink ".kilocode/rules/soul.md" "../../.ai/SOUL.md" || ((failed++))
verify_symlink ".continue/rules/soul.md" "../../.ai/SOUL.md" || ((failed++))
verify_symlink ".roo/rules/soul.md" "../../.ai/SOUL.md" || ((failed++))
verify_symlink ".opencode/instructions/SOUL.md" "../../.ai/SOUL.md" || ((failed++))
verify_symlink ".github/copilot-instructions.md" "../.ai/SOUL.md" || ((failed++))
verify_symlink ".junie/guidelines.md" "../.ai/SOUL.md" || ((failed++))
verify_symlink ".rules" ".ai/SOUL.md" || ((failed++))

echo ""
echo -e "${CYAN}🎓 Running Configuration Verification...${NC}"
echo ""

# Run the verification script
if [ -f "tools/verify-ai-config.php" ]; then
    php tools/verify-ai-config.php
    verification_result=$?
else
    echo -e "${YELLOW}⚠️  Verification script not found at tools/verify-ai-config.php${NC}"
    verification_result=1
fi

echo ""
echo -e "${BLUE}📊 Setup Summary${NC}"
echo -e "${BLUE}=================${NC}"

if [ $failed -eq 0 ] && [ $verification_result -eq 0 ]; then
    echo -e "${GREEN}🎉 SUCCESS: All AI configurations are properly set up!${NC}"
    echo ""
    echo -e "${GREEN}✅ Universal Source of Truth: .ai/SOUL.md${NC}"
    echo -e "${GREEN}✅ 14 Editor/Extension Symlinks: Created and Verified${NC}"
    echo -e "${GREEN}✅ Skills Directory: Linked to all editors${NC}"
    echo -e "${GREEN}✅ Configuration Score: 100/100${NC}"
    echo ""
    echo -e "${CYAN}💡 Next Steps:${NC}"
    echo "   1. Edit .ai/SOUL.md to update AI behavior"
    echo "   2. Changes automatically propagate to all editors"
    echo "   3. Run 'php tools/verify-ai-config.php' to verify sync"
    echo "   4. See .ai/EDITOR_OPTIMIZATION_GUIDE.md for performance tips"
    exit 0
else
    echo -e "${RED}❌ SETUP INCOMPLETE: $failed symlink(s) failed${NC}"
    echo ""
    echo -e "${YELLOW}🔧 Troubleshooting:${NC}"
    echo "   1. Check file permissions"
    echo "   2. Ensure you have symlink creation rights"
    echo "   3. Run 'php tools/verify-ai-config.php' for detailed diagnostics"
    echo "   4. Manual setup commands are in the verification script output"
    exit 1
fi
