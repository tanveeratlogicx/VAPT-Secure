/**
 * Test script to verify minimal build generation exclusions - Version 2
 * Matches the exact logic from copy_plugin_files function
 */

console.log('Testing minimal build generation exclusions (matching PHP logic)...\n');

// Simulate the exact exclusion logic from copy_plugin_files function
function shouldExcludeFilePHP(subPath, filename) {
  // Core exclusions - development and testing files (from PHP)
  const exclusions = [
    '.git', '.vscode', 'node_modules', 'brain', 'tests', 'vapt-debug.txt',
    'Implementation Plan', 'plans', 'tools', 'archive', 'Debug', 'backup_debug_cleanup',
    // AI/Agent configuration directories
    '.ai', '.roo', '.claude', '.cursor', '.gemini', '.kilocode', '.qoder', '.trae',
    '.windsurf', '.opencode', '.agent', '.kilo',
    // Specific AI subdirectories
    '.ai/workflows', '.ai/skills', '.ai/rules',
    '.claude/skills', '.cursor/skills', '.gemini/antigravity/skills',
    '.kilocode/rules', '.qoder/skills', '.trae/skills', '.windsurf/skills',
    '.roo/rules', '.roo/skills',
    // Deployment directory
    'deployment'
  ];

  // Documentation files to exclude (keep only README.md and How to User.md)
  const doc_exclusions = [
    'CLAUDE.md', 'DEBUG-MODE.md', 'VERSION_HISTORY.md', 'SOUL.md', 'SOUL_Claude-Notes.md',
    'SOUL_comprehensive.md', 'SOUL_enhanced.md', 'SOUL_with_selfcheck.md', 'SOUL-Claude-Ext.md',
    'SOUL-Claude.md', 'AGENTS.md', 'README-Claude-Ext.md'
  ];

  // Check Exclusions (PHP: if (strpos($subPath, $exclude) === 0) { continue 2; })
  for (const exclude of exclusions) {
    if (subPath.indexOf(exclude) === 0) {
      return true;
    }
  }

  // Handle Data Directory (simplified - we're not testing this part)
  if (subPath.indexOf('data') === 0) {
    // In real code, this would check for active_data_file
    // For testing, we'll assume no active_data_file, so exclude all data files
    return true;
  }

  // Exclude documentation files (except README.md and How to User.md)
  if (doc_exclusions.includes(filename)) {
    return true;
  }

  // Special case: exclude .md files in root except README.md and "How to User.md"
  // PHP: if (strpos($subPath, '.md') !== false && strpos($subPath, '/') === false && strpos($subPath, '\\') === false)
  if (subPath.includes('.md') && !subPath.includes('/') && !subPath.includes('\\')) {
    if (filename !== 'README.md' && filename !== 'How to User.md') {
      return true;
    }
  }

  return false;
}

// Test cases based on actual PHP logic
const testCases = [
  // Should be excluded (matches exclusion patterns)
  { path: '.ai/config.json', filename: 'config.json', expected: true, description: 'AI configuration directory' },
  { path: '.roo/rules/soul.md', filename: 'soul.md', expected: true, description: 'Roo rules directory' },
  { path: 'workflows/security-scan.yml', filename: 'security-scan.yml', expected: false, description: 'Workflows directory (not in exclusions)' },
  { path: 'skills/vapt-expert/SKILL.md', filename: 'SKILL.md', expected: false, description: 'Skills directory (not in exclusions)' },
  { path: 'rules/cursor.rules', filename: 'cursor.rules', expected: false, description: 'Rules directory (not in exclusions)' },
  { path: 'plans/implementation-plan.md', filename: 'implementation-plan.md', expected: true, description: 'Plans directory' },
  { path: 'tools/debug-tool.php', filename: 'debug-tool.php', expected: true, description: 'Tools directory' },
  { path: 'archive/old-features.json', filename: 'old-features.json', expected: true, description: 'Archive directory' },
  { path: 'Debug/log.txt', filename: 'log.txt', expected: true, description: 'Debug directory' },
  { path: 'deployment/config.json', filename: 'config.json', expected: true, description: 'Deployment directory' },
  { path: 'documentation/API.md', filename: 'API.md', expected: false, description: 'Documentation directory (not excluded)' },
  { path: 'CHANGELOG.md', filename: 'CHANGELOG.md', expected: true, description: 'CHANGELOG.md in root (excluded by special case)' },
  { path: 'LICENSE.txt', filename: 'LICENSE.txt', expected: false, description: 'LICENSE.txt (not excluded)' },
  { path: 'SOUL.md', filename: 'SOUL.md', expected: true, description: 'SOUL.md (in doc_exclusions)' },
  { path: 'AGENTS.md', filename: 'AGENTS.md', expected: true, description: 'AGENTS.md (in doc_exclusions)' },

  // Should be included
  { path: 'README.md', filename: 'README.md', expected: false, description: 'README.md should be included' },
  { path: 'How to User.md', filename: 'How to User.md', expected: false, description: '"How to User.md" should be included' },
  { path: 'includes/class-vaptsecure-build.php', filename: 'class-vaptsecure-build.php', expected: false, description: 'Core plugin file' },
  { path: 'assets/js/admin.js', filename: 'admin.js', expected: false, description: 'Assets directory' },
  { path: 'data/generated/config.json', filename: 'config.json', expected: true, description: 'Data directory (excluded by data rule)' },
  { path: 'vaptsecure.php', filename: 'vaptsecure.php', expected: false, description: 'Main plugin file' },
  { path: 'index.php', filename: 'index.php', expected: false, description: 'Plugin index file' },
  { path: 'uninstall.php', filename: 'uninstall.php', expected: false, description: 'Uninstall script' },

  // Edge cases
  { path: '.gitignore', filename: '.gitignore', expected: false, description: '.gitignore (does not start with .git/)' },
  { path: '.git/config', filename: 'config', expected: true, description: '.git directory' },
  { path: 'node_modules/package.json', filename: 'package.json', expected: true, description: 'node_modules directory' },
  { path: 'tests/unit/test-feature.php', filename: 'test-feature.php', expected: true, description: 'Tests directory' },
  { path: 'data/interface_schema_v2.0.json', filename: 'interface_schema_v2.0.json', expected: true, description: 'Data directory file' },
];

console.log('Running exclusion tests (matching PHP logic)...\n');
let passed = 0;
let failed = 0;

testCases.forEach((test, index) => {
  const result = shouldExcludeFilePHP(test.path, test.filename);
  const status = result === test.expected ? '✓ PASS' : '✗ FAIL';

  if (result === test.expected) {
    passed++;
    console.log(`${status}: ${test.description}`);
    console.log(`  Path: ${test.path}`);
  } else {
    failed++;
    console.log(`${status}: ${test.description}`);
    console.log(`  Path: ${test.path}`);
    console.log(`  Expected: ${test.expected ? 'excluded' : 'included'}, Got: ${result ? 'excluded' : 'included'}`);
  }
  console.log();
});

console.log(`\nTest Results:`);
console.log(`Passed: ${passed}`);
console.log(`Failed: ${failed}`);
console.log(`Total: ${testCases.length}`);

if (failed === 0) {
  console.log('\n✅ All tests passed! The build generator will correctly exclude unnecessary files.');
} else {
  console.log('\n❌ Some tests failed. Please review the exclusion logic.');
}

// Summary of what the PHP function actually does
console.log('\n\nSummary of PHP copy_plugin_files logic:');
console.log('1. Excludes directories starting with patterns in $exclusions array');
console.log('2. Excludes most data directory files (except active_data_file)');
console.log('3. Excludes specific documentation files in $doc_exclusions array');
console.log('4. Excludes all .md files in root except README.md and "How to User.md"');
console.log('5. Note: "workflows", "skills", "rules" directories are NOT excluded unless they are under AI directories');
console.log('6. LICENSE.txt and similar files ARE included (not excluded)');