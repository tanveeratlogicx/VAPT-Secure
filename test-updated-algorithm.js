// Test the updated algorithm for operational_notes and verification_steps
console.log('=== Testing Updated Algorithm ===\n');

// Simulate the findBestMatch function with updated scoring
function findBestMatch(keywords, targetFieldName = '') {
  const allKeys = [
    'operational_notes',
    'operational_notes.context',
    'operational_notes.implementation_notes',
    'operational_notes.impact',
    'verification_steps',
    'testing.verification_steps',
    'testing.verification_steps.steps',
    'verification.steps',
    'steps.verification'
  ];

  const matches = [];

  for (const field of allKeys) {
    const fieldLower = field.toLowerCase();
    let score = 0;

    // Exact match bonus
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

    // SPECIAL HANDLING FOR SPECIFIC FIELDS
    // For operational_notes, prioritize keys ending with ".context" 
    if (targetFieldName === 'operational_notes' && fieldLower.endsWith('.context')) {
      score += 30; // Extra bonus for context fields
    }
    // For verification_steps, prioritize keys containing "verification_steps"
    if (targetFieldName === 'verification_steps' && fieldLower.includes('verification_steps')) {
      score += 25; // Extra bonus for exact verification_steps match
    }
    // For verification_steps, also prioritize keys ending with ".steps"
    if (targetFieldName === 'verification_steps' && fieldLower.endsWith('.steps')) {
      score += 20; // Bonus for steps fields
    }

    matches.push({ field, score });
  }

  // Sort by score descending
  matches.sort((a, b) => b.score - a.score);

  return matches;
}

// Test operational_notes
console.log('=== Testing operational_notes ===');
const opNotesKeywords = ['operational_notes', 'context', 'operational_context', 'notes', 'operational'];
const opNotesMatches = findBestMatch(opNotesKeywords, 'operational_notes');
console.log('All matches:');
opNotesMatches.forEach(m => console.log(`  ${m.field}: ${m.score}`));
console.log('Best match:', opNotesMatches[0]);
console.log('Expected: operational_notes.context\n');

// Test verification_steps
console.log('=== Testing verification_steps ===');
const verStepsKeywords = ['verification_steps', 'verification', 'steps', 'testing', 'verification_steps'];
const verStepsMatches = findBestMatch(verStepsKeywords, 'verification_steps');
console.log('All matches:');
verStepsMatches.forEach(m => console.log(`  ${m.field}: ${m.score}`));
console.log('Best match:', verStepsMatches[0]);
console.log('Expected: testing.verification_steps\n');

// Check if testing.verification_steps exists in the matches
const testingVerSteps = verStepsMatches.find(m => m.field === 'testing.verification_steps');
if (testingVerSteps) {
  console.log(`testing.verification_steps score: ${testingVerSteps.score}`);
  console.log(`verification_steps score: ${verStepsMatches.find(m => m.field === 'verification_steps').score}`);

  // Calculate what bonus we need
  const currentDiff = verStepsMatches.find(m => m.field === 'verification_steps').score - testingVerSteps.score;
  if (currentDiff > 0) {
    console.log(`\nNeed additional bonus of ${currentDiff + 1} for testing.verification_steps`);
  }
}