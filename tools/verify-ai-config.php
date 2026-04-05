<?php
/**
 * AI Configuration Verification Script
 * Verifies all AI configuration files are in sync with Tier 1 (.ai/SOUL.md)
 *
 * Run: php tools/verify-ai-config.php [--fix]
 * --fix: Attempt to auto-fix minor issues (non-destructive)
 */

// ---------------------------------------------------------------------------
// CONFIGURATION
// ---------------------------------------------------------------------------

$TIER1_FILE = '.ai/SOUL.md';
$PLUGIN_FILE = 'vaptsecure.php';

$TIER2_FILES = [
    '.windsurfrules',
    '.clinerules',
    '.cursor/cursor.rules',
    '.gemini/gemini.md',
    '.qoder/qoder.rules',
    '.trae/trae.rules',
];

$TIER3_FILES = [
    '.kilo/kilo.rules',
    '.kilocode/kilocode.rules',
    '.roo/roo.rules',
    '.roo/rules/soul.md',
];

$SYNC_CHECK_FILES = array_merge([$TIER1_FILE], $TIER2_FILES, $TIER3_FILES);

// ---------------------------------------------------------------------------
// COLOR OUTPUT HELPERS
// ---------------------------------------------------------------------------

$useColor = !defined('NO_COLOR') && (PHP_SAPI === 'cli') && posix_isatty(STDOUT);

function color(string $text, string $color): string {
    global $useColor;
    if (!$useColor) return $text;
    
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'reset' => "\033[0m",
    ];
    
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function success(string $msg): void { echo color("✓ $msg", 'green') . "\n"; }
function error(string $msg): void { echo color("✗ $msg", 'red') . "\n"; }
function warning(string $msg): void { echo color("⚠ $msg", 'yellow') . "\n"; }
function info(string $msg): void { echo color("ℹ $msg", 'cyan') . "\n"; }
function header(string $msg): void { echo "\n" . color("═══ $msg ═══", 'blue') . "\n"; }

// ---------------------------------------------------------------------------
// VERSION EXTRACTION FUNCTIONS
// ---------------------------------------------------------------------------

/**
 * Extract plugin version from vaptsecure.php
 */
function extractPluginVersion(string $file): ?string {
    if (!file_exists($file)) return null;
    
    $content = file_get_contents($file);
    
    // Match define('VAPTSECURE_VERSION', 'x.x.x');
    if (preg_match("/define\s*\(\s*['\"]VAPTSECURE_VERSION['\"]\s*,\s*['\"]([\d.]+)['\"]\s*\)/", $content, $matches)) {
        return $matches[1];
    }
    
    // Match Plugin Header: Version: x.x.x
    if (preg_match('/^\s*Version:\s*([\d.]+)/m', $content, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Extract version from SOUL.md or rules file
 */
function extractConfigVersion(string $file): ?string {
    if (!file_exists($file)) return null;
    
    $content = file_get_contents($file);
    
    // Match **Version**: 2.4.11 format
    if (preg_match('/\*\*Version\*\*[:\s]+([\d.]+)/', $content, $matches)) {
        return $matches[1];
    }
    
    // Match Version: 2.4.11 format
    if (preg_match('/Version[:\s]+([\d.]+)/', $matches)) {
        return $matches[1];
    }
    
    return null;
}

// ---------------------------------------------------------------------------
// FILE SYSTEM CHECKS
// ---------------------------------------------------------------------------

/**
 * Check if file is a symlink and points to expected target
 */
function checkSymlink(string $file, string $expectedTarget): array {
    $issues = [];
    $status = 'ok';
    
    if (!file_exists($file) && !is_link($file)) {
        return ['missing', "File does not exist: $file", []];
    }
    
    if (!is_link($file)) {
        $status = 'not_symlink';
        $issues[] = "Not a symlink: $file (should link to $expectedTarget)";
    } else {
        $target = readlink($file);
        // Normalize paths for comparison
        $target = str_replace('\\', '/', $target);
        $expected = str_replace('\\', '/', $expectedTarget);
        
        if ($target !== $expected) {
            $status = 'wrong_target';
            $issues[] = "Wrong symlink target: $file -> $target (expected: $expected)";
        }
    }
    
    return [$status, $file, $issues];
}

// ---------------------------------------------------------------------------
// CONTENT VALIDATION
// ---------------------------------------------------------------------------

/**
 * Verify mandatory sections exist in Tier 1
 */
function validateMandatorySections(string $file): array {
    $content = file_get_contents($file);
    $issues = [];
    
    $requiredSections = [
        '## 🎯 Core Identity',
        '## 🏗️ Project Context',
        '## 🚫 Mandatory Rules',
        '## 📋 Feature Lifecycle Rules',
        '## 🔧 Technical Constraints',
        '## 💬 Communication Style',
        '## 🎓 Domain Expertise Areas',
    ];
    
    foreach ($requiredSections as $section) {
        if (strpos($content, $section) === false) {
            $issues[] = "Missing required section: $section";
        }
    }
    
    return $issues;
}

/**
 * Check for WordPress security keywords
 */
function validateSecurityContent(string $file): array {
    $content = file_get_contents($file);
    $issues = [];
    $suggestions = [];
    
    // Critical patterns that should exist
    $criticalPatterns = [
        ['pattern' => '/wp-admin/', 'desc' => 'WordPress admin path reference'],
        ['pattern' => 'RewriteEngine', 'desc' => 'Apache mod_rewrite'],
        ['pattern' => '{domain}', 'desc' => 'Domain placeholder'],
        ['pattern' => 'TraceEnable', 'desc' => 'Forbidden directive warning'],
    ];
    
    foreach ($criticalPatterns as $pattern) {
        if (strpos($content, $pattern['pattern']) === false) {
            $issues[] = "May be missing: {$pattern['desc']}";
        }
    }
    
    return [$issues, $suggestions];
}

// ---------------------------------------------------------------------------
// MAIN VERIFICATION
// ---------------------------------------------------------------------------

function main(): int {
    global $TIER1_FILE, $PLUGIN_FILE, $SYNC_CHECK_FILES;
    
    $exitCode = 0;
    $errors = [];
    $warnings = [];
    $fixed = [];
    
    header("AI Configuration Verification");
    
    // -----------------------------------------------------------------------
    // Check Plugin Version
    // -----------------------------------------------------------------------
    info("Checking plugin version...");
    $pluginVersion = extractPluginVersion($PLUGIN_FILE);
    
    if (!$pluginVersion) {
        error("Could not extract version from $PLUGIN_FILE");
        $errors[] = "No version found in plugin file";
        return 1;
    }
    success("Plugin version: $pluginVersion");
    
    // -----------------------------------------------------------------------
    // Check Tier 1 Exists
    // -----------------------------------------------------------------------
    header("Tier 1: Universal Core ($TIER1_FILE)");
    
    if (!file_exists($TIER1_FILE)) {
        error("CRITICAL: Tier 1 file not found: $TIER1_FILE");
        error("All AI configuration depends on this file!");
        return 1;
    }
    success("Tier 1 file exists");
    
    // Validate content
    $contentIssues = validateMandatorySections($TIER1_FILE);
    if (empty($contentIssues)) {
        success("All mandatory sections present");
    } else {
        error("Missing mandatory sections:");
        foreach ($contentIssues as $issue) {
            echo "    - $issue\n";
        }
        $errors = array_merge($errors, $contentIssues);
    }
    
    [$securityIssues, $suggestions] = validateSecurityContent($TIER1_FILE);
    if (empty($securityIssues)) {
        success("Security content validated");
    } else {
        warning("Security content concerns:");
        foreach ($securityIssues as $issue) {
            echo "    - $issue\n";
        }
        $warnings = array_merge($warnings, $securityIssues);
    }
    
    // Check Tier 1 version matches plugin
    $tier1Version = extractConfigVersion($TIER1_FILE);
    if ($tier1Version && $tier1Version !== $pluginVersion) {
        warning("Version mismatch: Plugin=$pluginVersion, Tier1=$tier1Version");
        $warnings[] = "Version sync recommended";
    } elseif ($tier1Version) {
        success("Version synced: $tier1Version");
    }
    
    // -----------------------------------------------------------------------
    // Check Tier 2 Symlinks
    // -----------------------------------------------------------------------
    header("Tier 2: Editor Symlinks");
    
    $tier2Expectations = [
        '.windsurfrules' => ['target' => '.ai/SOUL.md', 'docs' => 'Windsurf'],
        '.clinerules' => ['target' => '.ai/SOUL.md', 'docs' => 'Claude Code CLI'],
        '.cursor/cursor.rules' => ['target' => '../.ai/SOUL.md', 'docs' => 'Cursor'],
        '.gemini/gemini.md' => ['target' => '../.ai/SOUL.md', 'docs' => 'Gemini/Antigravity'],
        '.qoder/qoder.rules' => ['target' => '../.ai/SOUL.md', 'docs' => 'Qoder'],
        '.trae/trae.rules' => ['target' => '../.ai/SOUL.md', 'docs' => 'Trae'],
    ];
    
    foreach ($tier2Expectations as $file => $config) {
        $fullPath = $file;
        
        if (!file_exists($fullPath) && !is_link($fullPath)) {
            error("Missing: $fullPath (for {$config['docs']})");
            $errors[] = "Create symlink: $fullPath -> {$config['target']}";
            continue;
        }
        
        if (is_link($fullPath)) {
            $target = readlink($fullPath);
            // Normalize for comparison
            $target = str_replace(['../', '..\\'], ['', ''], $target);
            $expected = str_replace(['../', '..\\'], ['', ''], $config['target']);
            
            if ($target === $expected || $target === $config['target']) {
                success("$fullPath -> {$config['target']}", 'green');
            } else {
                warning("$fullPath links to '$target', expected '{$config['target']}'");
                $warnings[] = "Update symlink: $fullPath";
            }
        } else {
            warning("$fullPath exists but is not a symlink");
            $warnings[] = "Consider converting to symlink: $fullPath -> {$config['target']}";
        }
        
        // Check content sync (symlinks should auto-sync)
        $fileVersion = extractConfigVersion($fullPath);
        if ($fileVersion !== $tier1Version) {
            warning("Content drift: $fullPath version ($fileVersion) vs Tier1 ($tier1Version)");
            $warnings[] = "Regenerate: $fullPath";
        }
    }
    
    // -----------------------------------------------------------------------
    // Check Tier 3 Configurations
    // -----------------------------------------------------------------------
    header("Tier 3: Extension Configurations (Roo/Kilo)");
    
    $tier3Files = [
        '.kilo/kilo.rules',
        '.kilocode/kilocode.rules',
        '.roo/roo.rules',
        '.roo/rules/soul.md',
        '.roomodes',
    ];
    
    foreach ($tier3Files as $file) {
        if (!file_exists($file)) {
            info("Optional: $file (not present)");
        } else {
            $fileVersion = extractConfigVersion($file);
            if ($fileVersion) {
                if ($fileVersion === $tier1Version) {
                    success("$file (v$fileVersion)");
                } else {
                    warning("$file v$fileVersion ≠ Tier1 v$tier1Version");
                    $warnings[] = "Resync: $file";
                }
            } else {
                info("$file (no version detected)");
            }
        }
    }
    
    // -----------------------------------------------------------------------
    // Skills Directory Check
    // -----------------------------------------------------------------------
    header("Skills Repository");
    
    $skillsDir = '.ai/skills';
    if (is_dir($skillsDir)) {
        $skills = glob("$skillsDir/*/*SKILL.md");
        success("Skills directory exists with " . count($skills) . " skill files");
        
        foreach ($skills as $skill) {
            $name = dirname($skill);
            info("  Found: " . basename($name));
        }
    } else {
        error("Skills directory not found: $skillsDir");
        $errors[] = "Create skills directory structure";
    }
    
    // Check skill symlinks in editor directories
    $skillLinks = [
        '.cursor/skills' => '../../.ai/skills',
        '.windsurf/skills' => '../../.ai/skills',
        '.gemini/antigravity/skills' => '../../../.ai/skills',
        '.roo/skills' => '../../.ai/skills',
        '.kilo/skills' => '../../.ai/skills',
        '.trae/skills' => '../../.ai/skills',
        '.qoder/skills' => '../../.ai/skills',
    ];
    
    foreach ($skillLinks as $link => $expectedTarget) {
        if (is_link($link)) {
            $target = readlink($link);
            // Normalize paths
            $target = str_replace(['../', '..\\'], '', $target);
            $expected = str_replace(['../', '..\\'], '', $expectedTarget);
            
            if (strpos($target, 'ai/skills') !== false) {
                success("Skill link: $link");
            } else {
                warning("Skill link may be incorrect: $link -> $target");
            }
        } elseif (is_dir($link)) {
            info("Skill dir exists (not symlink): $link");
        } else {
            info("Skill link missing (optional): $link");
        }
    }
    
    // -----------------------------------------------------------------------
    // Summary
    // -----------------------------------------------------------------------
    header("Verification Summary");
    
    if (empty($errors) && empty($warnings)) {
        success("═══════════════════════════════════════════");
        success("  All AI configuration files are in sync!");
        success("  Plugin Version: $pluginVersion");
        success("  Tier 1 (SOUL): ✓ Valid");
        success("  Tier 2 (Symlinks): ✓ All linked");
        success("═══════════════════════════════════════════");
        return 0;
    }
    
    if (!empty($warnings)) {
        warning("\nFound " . count($warnings) . " warning(s) - recommended but not blocking");
        foreach ($warnings as $i => $w) {
            echo "  " . ($i + 1) . ". $w\n";
        }
    }
    
    if (!empty($errors)) {
        error("\nFound " . count($errors) . " error(s) - must fix");
        foreach ($errors as $i => $e) {
            echo "  " . ($i + 1) . ". $e\n";
        }
        $exitCode = 1;
    }
    
    // -----------------------------------------------------------------------
    // Recommendations
    // -----------------------------------------------------------------------
    
    if (!empty($warnings) || !empty($errors)) {
        echo "\n" . color("═ Recommendations ═", 'cyan') . "\n";
        echo "1. Review plans/VAPT-AI-DEV-PLAN-Universal-v1.0.md\n";
        echo "2. Ensure only .ai/SOUL.md is edited (not symlinked files)\n";
        echo "3. Run this script after configuration changes\n";
        echo "4. Consider setting up git pre-commit hook\n\n";
        
        echo color("Quick fix commands (PowerShell):\n", 'cyan');
        echo "  # Create symlinks\n";
        echo "  New-Item -ItemType SymbolicLink -Path \".windsurfrules\" -Target \".ai\\SOUL.md\" -Force\n";
        echo "  New-Item -ItemType SymbolicLink -Path \".clinerules\" -Target \".ai\\SOUL.md\" -Force\n";
        echo "  New-Item -ItemType SymbolicLink -Path \".roorules\" -Target \".ai\\SOUL.md\" -Force\n\n";
    }
    
    return $exitCode;
}

// Run main
$exitCode = main();
exit($exitCode);
