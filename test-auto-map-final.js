/**
 * Test script to verify the updated Auto Map algorithm
 * This tests the improved findBestMatch function with target field name parameter
 */

const testAutoMapFinal = () => {
  console.log('=== Final Auto Map Algorithm Test ===\n');

  // Simulate allKeys from actual data (based on debug-field-mapping.js results)
  const allKeys = [
    'id',
    'title',
    'description',
    'severity',
    'category',
    'owasp',
    'owasp.category',
    'owasp.reference',
    'owasp.cwe',
    'owasp.owasp_top_10_2025',
    'testing',
    'testing.verification_steps',
    'testing.verification_command',
    'testing.verification_expected',
    'operational_notes',
    'operational_notes.context',
    'operational_notes.implementation_notes',
    'operational_notes.impact',
    'ui_layout',
    'components',
    'actions',
    'available_platforms',
    'platform_implementations',
    'risk_id',
    'verification_command',
    'verification_expected'
  ];

  console.log(`Total keys: ${allKeys.length}`);
  console.log('Key sample:', allKeys.slice(0, 10));

  // Updated findBestMatch function from admin.js
  const findBestMatch = (keywords, targetFieldName = '') => {
    const matches = [];

    allKeys.forEach(field => {
      const fieldLower = field.toLowerCase();
      let bestScore = 0;
      let bestKeyword = '';

      keywords.forEach(keyword => {
        const keywordLower = keyword.toLowerCase();
        let score = 0;

        // Bonus: keyword contains target field name (e.g., "verification_steps" contains "verification")
        const targetInKeyword = targetFieldName && keywordLower.includes(targetFieldName.toLowerCase());
        const keywordInTarget = targetFieldName && targetFieldName.toLowerCase().includes(keywordLower);

        // Exact match (highest priority)
        if (fieldLower === keywordLower) {
          score = 100;
          // Extra bonus if keyword contains target field name
          if (targetInKeyword) score += 20;
        }
        // Field ends with .keyword (nested match) - e.g., "testing.verification_steps" ends with ".verification_steps"
        else if (fieldLower.endsWith('.' + keywordLower)) {
          score = 95;
          if (targetInKeyword) score += 15;
        }
        // Field starts with keyword. - e.g., "verification_steps.testing" starts with "verification_steps."
        else if (fieldLower.startsWith(keywordLower + '.')) {
          score = 90;
          if (targetInKeyword) score += 15;
        }
        // Field contains keyword as whole word with dots (nested structure)
        else if (fieldLower.includes('.' + keywordLower + '.')) {
          score = 85;
          if (targetInKeyword) score += 10;
        }
        // Field contains keyword as whole word with underscores
        else if (fieldLower.includes('_' + keywordLower + '_')) {
          score = 80;
          if (targetInKeyword) score += 10;
        }
        // Field contains keyword (partial match)
        else if (fieldLower.includes(keywordLower)) {
          // Penalize longer field names for partial matches
          const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
          score = 70 - lengthPenalty;
          if (targetInKeyword) score += 10;
        }
        // Levenshtein distance for fuzzy matching (fallback)
        else {
          // Simple similarity check
          const similarity = keywordLower.split('').filter(c => fieldLower.includes(c)).length / keywordLower.length;
          if (similarity > 0.7) {
            score = Math.floor(similarity * 60);
            if (targetInKeyword) score += 5;
          }
        }

        // Extra bonus for exact target field name match
        if (targetFieldName && fieldLower === targetFieldName.toLowerCase()) {
          score += 25;
        }

        // Special bonus for nested keys that match the target field structure
        if (targetFieldName) {
          // Bonus for field containing target field name as part of nested structure
          if (fieldLower.includes('.' + targetFieldName.toLowerCase() + '.')) {
            score += 15;
          }
          // Bonus for field ending with target field name
          if (fieldLower.endsWith('.' + targetFieldName.toLowerCase())) {
            score += 20;
          }
          // Bonus for field starting with target field name
          if (fieldLower.startsWith(targetFieldName.toLowerCase() + '.')) {
            score += 20;
          }
        }

        if (score > bestScore) {
          bestScore = score;
          bestKeyword = keyword;
        }
      });

      if (bestScore > 40) {
        matches.push({
          field,
          score: bestScore,
          keyword: bestKeyword
        });
      }
    });

    // Sort by score descending, then by field length (shorter is better)
    matches.sort((a, b) => {
      if (b.score !== a.score) return b.score - a.score;
      return a.field.length - b.field.length;
    });

    return matches.length > 0 ? matches[0].field : '';
  };

  // Test Operational Context mapping
  const operationalKeywords = [
    'operational_notes.context',  // Highest priority - exact nested key
    'context.operational_notes',  // Alternative nested structure
    'operational_notes',          // Exact field name
    'operational_context',
    'operation_context',
    'context',
    'operation_notes',
    'operation_details',
    'notes',
    'summary',
    'remarks',
    'comments',
    'guidance',
    'instructions',
    'documentation',
    'background',
    'environment',
    'contextual'
  ];

  // Test Verification Steps mapping
  const verificationKeywords = [
    'testing.verification_steps',  // Highest priority - exact nested key
    'verification.steps',          // Alternative nested structure
    'verification_steps',          // Exact field name
    'manual_verification',
    'steps',
    'test_steps',
    'testing_steps',
    'validation_steps',
    'test_method',
    'verification',
    'testing',
    'validation',
    'checks',
    'procedures',
    'manual_testing',
    'test_procedure'
  ];

  console.log('\n=== Testing Operational Context ===');
  const operationalMatch = findBestMatch(operationalKeywords, 'operational_notes');
  console.log(`Best match for Operational Context: ${operationalMatch}`);

  // Show top 3 matches
  const operationalMatches = allKeys
    .map(field => {
      const fieldLower = field.toLowerCase();
      let score = 0;

      // Check if field contains operational context indicators
      if (fieldLower.includes('operational') && fieldLower.includes('context')) score += 50;
      if (fieldLower.includes('operational') && fieldLower.includes('notes')) score += 40;
      if (fieldLower.includes('context')) score += 20;
      if (fieldLower.includes('notes')) score += 10;

      return { field, score };
    })
    .filter(m => m.score > 0)
    .sort((a, b) => b.score - a.score)
    .slice(0, 5);

  console.log('Top 5 potential matches for Operational Context:');
  operationalMatches.forEach((m, i) => {
    console.log(`  ${i + 1}. ${m.field} (score: ${m.score})`);
  });

  console.log('\n=== Testing Verification Steps ===');
  const verificationMatch = findBestMatch(verificationKeywords, 'verification_steps');
  console.log(`Best match for Verification Steps: ${verificationMatch}`);

  // Show top 3 matches
  const verificationMatches = allKeys
    .map(field => {
      const fieldLower = field.toLowerCase();
      let score = 0;

      // Check if field contains verification steps indicators
      if (fieldLower.includes('verification') && fieldLower.includes('steps')) score += 60;
      if (fieldLower.includes('testing') && fieldLower.includes('verification')) score += 50;
      if (fieldLower.includes('verification')) score += 30;
      if (fieldLower.includes('steps')) score += 20;
      if (fieldLower.includes('testing')) score += 10;

      return { field, score };
    })
    .filter(m => m.score > 0)
    .sort((a, b) => b.score - a.score)
    .slice(0, 5);

  console.log('Top 5 potential matches for Verification Steps:');
  verificationMatches.forEach((m, i) => {
    console.log(`  ${i + 1}. ${m.field} (score: ${m.score})`);
  });

  console.log('\n=== Summary ===');
  const operationalSuccess = operationalMatch === 'operational_notes.context' || operationalMatch === 'operational_notes';
  const verificationSuccess = verificationMatch === 'testing.verification_steps' || verificationMatch === 'verification_steps';

  console.log(`Operational Context mapping: ${operationalSuccess ? '✅ SUCCESS' : '❌ FAILED'}`);
  console.log(`  Expected: operational_notes.context or operational_notes`);
  console.log(`  Got: ${operationalMatch}`);

  console.log(`Verification Steps mapping: ${verificationSuccess ? '✅ SUCCESS' : '❌ FAILED'}`);
  console.log(`  Expected: testing.verification_steps or verification_steps`);
  console.log(`  Got: ${verificationMatch}`);

  console.log('\n=== Key Insights ===');
  console.log('1. The algorithm now includes target field name parameter');
  console.log('2. Special bonuses for nested keys that match target field structure');
  console.log('3. "owasp" removed from verification keywords to prevent incorrect matches');
  console.log('4. Nested keys like "testing.verification_steps" and "operational_notes.context" are prioritized');
};

// Run the test
testAutoMapFinal();