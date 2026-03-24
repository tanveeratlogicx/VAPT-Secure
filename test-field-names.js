// Test to understand the actual field mapping structure
console.log('=== Analyzing Field Mapping Structure ===\n');

// Based on the admin.js code, these are the target fields in the modal:
const targetFields = [
  'description',
  'severity',
  'ui_layout',
  'components',
  'actions',
  'available_platforms',
  'platform_implementations',
  'operational_notes',  // This maps to "Operational Context" in the UI
  'verification_steps', // This maps to "Verification Steps" in the UI
  'risk_id',
  'title',
  'category',
  'owasp_cwe',
  'owasp_top_10_2025',
  'verification_command',
  'verification_expected'
];

console.log('Total target fields:', targetFields.length);
console.log('\nTarget fields that need special attention:');
console.log('1. operational_notes -> Maps to "Operational Context" in UI');
console.log('2. verification_steps -> Maps to "Verification Steps" in UI');

console.log('\n=== Current Issue Analysis ===');
console.log('Problem: operational_notes is matching to "operational_notes" instead of "operational_notes.context"');
console.log('Reason: operational_notes gets +25 bonus for exact target field name match');
console.log('Solution: Need to adjust scoring to prioritize nested keys for these specific fields');

console.log('\n=== Proposed Solution ===');
console.log('1. For operational_notes field, we should prioritize nested keys like "operational_notes.context"');
console.log('2. Give extra bonus to nested keys that contain the target field name');
console.log('3. Specifically for operational_notes, prioritize keys ending with ".context"');

console.log('\n=== Testing with Sample Data ===');
const sampleKeys = [
  'operational_notes',
  'operational_notes.context',
  'operational_notes.implementation_notes',
  'operational_notes.impact',
  'testing.verification_steps',
  'verification.steps',
  'verification_steps',
  'verification',
  'testing'
];

console.log('\nSample keys that should be matched:');
console.log('- operational_notes.context -> Should match operational_notes (for Operational Context)');
console.log('- testing.verification_steps -> Should match verification_steps (for Verification Steps)');

console.log('\n=== Current Scoring Issue ===');
console.log('operational_notes:');
console.log('  - operational_notes: 120 (exact match) + 25 (target bonus) = 145');
console.log('  - operational_notes.context: 120 (exact match) + 20 (starts with bonus) = 140');
console.log('Result: operational_notes wins (145 > 140)');

console.log('\n=== Fix Needed ===');
console.log('Add special bonus for nested keys that are more specific:');
console.log('- operational_notes.context should get extra bonus for containing ".context"');
console.log('- testing.verification_steps should get extra bonus for containing "verification_steps"');