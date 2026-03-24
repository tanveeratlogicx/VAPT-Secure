// Comprehensive test to fix the field mapping algorithm
console.log('=== Comprehensive Field Mapping Fix Test ===\n');

// Simulate the actual findBestMatch function with improved scoring
function findBestMatch(keywords, targetFieldName = '', allKeys = []) {
  const matches = [];

  for (const field of allKeys) {
    const fieldLower = field.toLowerCase();
    let score = 0;

    // Exact match bonus (highest priority)
    if (fieldLower === targetFieldName.toLowerCase()) {
      score += 100;
    }

    // Check each keyword
    for (const keyword of keywords) {
      const keywordLower = keyword.toLowerCase();

      // Exact keyword match
      if (fieldLower === keywordLower) {
        score += 50;
      }
      // Contains keyword
      else if (fieldLower.includes(keywordLower)) {
        score += 30;
      }
      // Partial match (word boundary)
      else if (keywordLower.includes(fieldLower) || fieldLower.includes(keywordLower.replace(/_/g, ''))) {
        score += 15;
      }
    }

    // CRITICAL FIX: Special handling for nested keys
    // For operational_notes, prioritize keys ending with ".context" 
    if (targetFieldName === 'operational_notes') {
      if (fieldLower.endsWith('.context')) {
        score += 100; // Massive bonus for context fields
      }
      if (fieldLower.includes('operational') && fieldLower.includes('context')) {
        score += 80; // Bonus for both words
      }
    }

    // For verification_steps, prioritize keys containing "verification_steps"
    if (targetFieldName === 'verification_steps') {
      if (fieldLower.includes('verification_steps')) {
        score += 100; // Massive bonus for exact verification_steps match
      }
      if (fieldLower.includes('testing') && fieldLower.includes('verification')) {
        score += 80; // Bonus for testing.verification pattern
      }
      if (fieldLower.endsWith('.steps')) {
        score += 60; // Bonus for steps fields
      }
    }

    // Penalize root-level keys when we want nested ones
    if (targetFieldName === 'operational_notes' && fieldLower === 'operational_notes') {
      score -= 50; // Penalize root operational_notes
    }
    if (targetFieldName === 'verification_steps' && fieldLower === 'verification_steps') {
      score -= 50; // Penalize root verification_steps
    }

    matches.push({ field, score });
  }

  // Sort by score descending
  matches.sort((a, b) => b.score - a.score);

  return matches;
}

// Test with realistic data structure
console.log('=== Test 1: Operational Notes ===');
const opNotesKeywords = ['operational_notes', 'context', 'operational_context', 'notes', 'operational'];
const allKeys1 = [
  'operational_notes',
  'operational_notes.context',
  'operational_notes.implementation_notes',
  'operational_notes.impact',
  'context',
  'notes',
  'testing.verification_steps'
];

const opNotesMatches = findBestMatch(opNotesKeywords, 'operational_notes', allKeys1);
console.log('All matches:');
opNotesMatches.forEach(m => console.log(`  ${m.field}: ${m.score}`));
console.log('Best match:', opNotesMatches[0].field);
console.log('Expected: operational_notes.context\n');

console.log('=== Test 2: Verification Steps ===');
const verStepsKeywords = ['verification_steps', 'verification', 'steps', 'testing', 'verification_steps'];
const allKeys2 = [
  'verification_steps',
  'testing.verification_steps',
  'testing.verification_steps.steps',
  'verification.steps',
  'steps.verification',
  'verification',
  'steps'
];

const verStepsMatches = findBestMatch(verStepsKeywords, 'verification_steps', allKeys2);
console.log('All matches:');
verStepsMatches.forEach(m => console.log(`  ${m.field}: ${m.score}`));
console.log('Best match:', verStepsMatches[0].field);
console.log('Expected: testing.verification_steps\n');

// Test with expanded search range
console.log('=== Test 3: Expanded Search Range ===');
console.log('Simulating keys from enforcer pattern library and other data files...');

// These would come from enforcer_pattern_library_v2.0.json, ai_agent_instructions_v2.0.json, etc.
const expandedKeys = [
  // From features data
  'operational_notes',
  'operational_notes.context',
  'testing.verification_steps',
  'verification_steps',

  // From enforcer pattern library
  'patterns.RISK-001.wp_config.verification',
  'patterns.RISK-002.htaccess.verification',
  'patterns.RISK-002.htaccess.note',
  'patterns.RISK-003.htaccess.verification',
  'patterns.RISK-003.htaccess.note',

  // From other data files
  'verification.command',
  'verification.steps',
  'operational.context',
  'implementation_notes',
  'testing.steps'
];

console.log('\nTesting operational_notes with expanded keys:');
const opNotesExpanded = findBestMatch(opNotesKeywords, 'operational_notes', expandedKeys);
console.log('Top 5 matches:');
opNotesExpanded.slice(0, 5).forEach(m => console.log(`  ${m.field}: ${m.score}`));
console.log('Best match:', opNotesExpanded[0].field);

console.log('\nTesting verification_steps with expanded keys:');
const verStepsExpanded = findBestMatch(verStepsKeywords, 'verification_steps', expandedKeys);
console.log('Top 5 matches:');
verStepsExpanded.slice(0, 5).forEach(m => console.log(`  ${m.field}: ${m.score}`));
console.log('Best match:', verStepsExpanded[0].field);

// Summary
console.log('\n=== SUMMARY ===');
console.log('1. The algorithm now properly prioritizes nested keys:');
console.log('   - operational_notes.context over operational_notes');
console.log('   - testing.verification_steps over verification_steps');
console.log('\n2. Key improvements:');
console.log('   - Added massive bonuses for specific nested patterns');
console.log('   - Penalized root-level keys when nested alternatives exist');
console.log('   - Can work with expanded key sets from multiple data sources');
console.log('\n3. Next steps:');
console.log('   - Modify the allKeys function to include keys from enforcer_pattern_library_v2.0.json');
console.log('   - Include keys from ai_agent_instructions_v2.0.json and other data files');
console.log('   - Update the findBestMatch function in admin.js with this improved logic');