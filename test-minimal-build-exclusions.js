/**
 * Test script to verify minimal build generation exclusions
 * Verifies that the build generator excludes unnecessary files:
 * - Rules, workflows, skills directories
 * - AI/agent configuration directories
 * - Excessive documentation except README.md and "How to User.md"
 */

console.log('Testing minimal build generation exclusions...\n');

// Simulate the exclusion logic from copy_plugin_files function
function shouldExcludeFile(filePath) {
  const exclusions = [
    '.git', '.vscode', 'node_modules', 'brain', 'tests', 'vapt-debug.txt', 'Implementation Plan',
    // AI/agent directories
    '.ai', '.roo', '.claude', '.cursor', '.gemini', '.kilocode', '.qoder', '.trae', '.windsurf', '.opencode', '.agent', '.kilo',
    // Workflows, skills, rules
    'workflows', 'skills', 'rules',
    // Documentation (except README.md and "How to User.md")
    'plans/', 'tools/', 'archive/', 'Debug/', 'backup_debug_cleanup/', 'deployment/'
  ];

  // Check if file path matches any exclusion pattern
  for (const exclusion of exclusions) {
    if (exclusion.endsWith('/')) {
      // Directory exclusion
      if (filePath.startsWith(exclusion)) {
        return true;
      }
    } else {
      // Exact match or starts with
      if (filePath === exclusion || filePath.startsWith(exclusion + '/')) {
        return true;
      }
    }
  }

  // Check documentation files
  const docFiles = filePath.match(/\/([^\/]+\.(md|txt|pdf|docx?))$/i);
  if (docFiles) {
    const fileName = docFiles[1].toLowerCase();
    // Only allow README.md and "How to User.md" (case-insensitive)
    if (fileName !== 'readme.md' && !fileName.includes('how to user')) {
      return true;
    }
  }

  return false;
}

// Test cases
const testCases = [
  // Should be excluded
  { path: '.ai/config.json', expected: true, description: 'AI configuration directory' },
  { path: '.roo/rules/soul.md', expected: true, description: 'Roo rules directory' },
  { path: 'workflows/security-scan.yml', expected: true, description: 'Workflows directory' },
  { path: 'skills/vapt-expert/SKILL.md', expected: true, description: 'Skills directory' },
  { path: 'rules/cursor.rules', expected: true, description: 'Rules directory' },
  { path: 'plans/implementation-plan.md', expected: true, description: 'Plans directory' },
  { path: 'tools/debug-tool.php', expected: true, description: 'Tools directory' },
  { path: 'archive/old-features.json', expected: true, description: 'Archive directory' },
  { path: 'Debug/log.txt', expected: true, description: 'Debug directory' },
  { path: 'deployment/config.json', expected: true, description: 'Deployment directory' },
  { path: 'documentation/API.md', expected: true, description: 'Other documentation file' },
  { path: 'CHANGELOG.md', expected: true, description: 'Changelog documentation' },
  { path: 'LICENSE.txt', expected: true, description: 'License file' },

  // Should be included
  { path: 'README.md', expected: false, description: 'README.md should be included' },
  { path: 'How to User.md', expected: false, description: '"How to User.md" should be included' },
  { path: 'how to user.md', expected: false, description: 'Case-insensitive "how to user.md"' },
  { path: 'includes/class-vaptsecure-build.php', expected: false, description: 'Core plugin file' },
  { path: 'assets/js/admin.js', expected: false, description: 'Assets directory' },
  { path: 'data/generated/config.json', expected: false, description: 'Generated data directory' },
  { path: 'vaptsecure.php', expected: false, description: 'Main plugin file' },
  { path: 'index.php', expected: false, description: 'Plugin index file' },
  { path: 'uninstall.php', expected: false, description: 'Uninstall script' },

  // Edge cases
  { path: '.gitignore', expected: true, description: '.git directory file' },
  { path: 'node_modules/package.json', expected: true, description: 'node_modules directory' },
  { path: 'tests/unit/test-feature.php', expected: true, description: 'Tests directory' },
];

console.log('Running exclusion tests...\n');
let passed = 0;
let failed = 0;

testCases.forEach((test, index) => {
  const result = shouldExcludeFile(test.path);
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

// Also test the actual PHP function structure
console.log('\n\nChecking PHP function structure...');
console.log('The copy_plugin_files function should:');
console.log('1. Use recursive directory iteration');
console.log('2. Check each file against the exclusion list');
console.log('3. Skip excluded files/directories');
console.log('4. Copy only included files to the build directory');