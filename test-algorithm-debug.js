/**
 * Debug script to analyze why operational_notes.context isn't being selected
 */

const testAlgorithmDebug = () => {
  console.log('=== Algorithm Debug Analysis ===\n');

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

  const targetFieldName = 'operational_notes';

  // Exact algorithm from admin.js
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

  // Run the algorithm
  const result = findBestMatch(operationalKeywords, targetFieldName);

  console.log(`Target field name: ${targetFieldName}`);
  console.log(`Result: ${result}`);

  // Let's trace through the algorithm manually for key fields
  console.log('\n=== Detailed Analysis for Key Fields ===');

  const keyFieldsToAnalyze = ['operational_notes', 'operational_notes.context', 'operational_notes.implementation_notes'];

  keyFieldsToAnalyze.forEach(field => {
    console.log(`\nAnalyzing field: ${field}`);
    const fieldLower = field.toLowerCase();
    let bestScore = 0;
    let bestKeyword = '';

    operationalKeywords.forEach(keyword => {
      const keywordLower = keyword.toLowerCase();
      let score = 0;

      const targetInKeyword = targetFieldName && keywordLower.includes(targetFieldName.toLowerCase());

      // Check each scoring rule
      if (fieldLower === keywordLower) {
        score = 100;
        if (targetInKeyword) score += 20;
        console.log(`  - Exact match with "${keyword}": score = ${score}`);
      }
      else if (fieldLower.endsWith('.' + keywordLower)) {
        score = 95;
        if (targetInKeyword) score += 15;
        console.log(`  - Field ends with ".${keyword}": score = ${score}`);
      }
      else if (fieldLower.startsWith(keywordLower + '.')) {
        score = 90;
        if (targetInKeyword) score += 15;
        console.log(`  - Field starts with "${keyword}.": score = ${score}`);
      }
      else if (fieldLower.includes('.' + keywordLower + '.')) {
        score = 85;
        if (targetInKeyword) score += 10;
        console.log(`  - Field contains ".${keyword}.": score = ${score}`);
      }
      else if (fieldLower.includes('_' + keywordLower + '_')) {
        score = 80;
        if (targetInKeyword) score += 10;
        console.log(`  - Field contains "_${keyword}_": score = ${score}`);
      }
      else if (fieldLower.includes(keywordLower)) {
        const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
        score = 70 - lengthPenalty;
        if (targetInKeyword) score += 10;
        console.log(`  - Field contains "${keyword}" (partial): score = ${score} (penalty: ${lengthPenalty})`);
      }

      // Extra bonus for exact target field name match
      if (targetFieldName && fieldLower === targetFieldName.toLowerCase()) {
        score += 25;
        console.log(`  - Extra bonus for exact target field name: +25`);
      }

      // Special bonus for nested keys that match the target field structure
      if (targetFieldName) {
        if (fieldLower.includes('.' + targetFieldName.toLowerCase() + '.')) {
          score += 15;
          console.log(`  - Bonus for containing ".${targetFieldName}.": +15`);
        }
        if (fieldLower.endsWith('.' + targetFieldName.toLowerCase())) {
          score += 20;
          console.log(`  - Bonus for ending with ".${targetFieldName}": +20`);
        }
        if (fieldLower.startsWith(targetFieldName.toLowerCase() + '.')) {
          score += 20;
          console.log(`  - Bonus for starting with "${targetFieldName}.": +20`);
        }
      }

      if (score > bestScore) {
        bestScore = score;
        bestKeyword = keyword;
      }
    });

    console.log(`  Best score for "${field}": ${bestScore} (keyword: "${bestKeyword}")`);
  });

  // Show all matches sorted
  console.log('\n=== All Matches (sorted) ===');
  const matches = [];

  allKeys.forEach(field => {
    const fieldLower = field.toLowerCase();
    let bestScore = 0;
    let bestKeyword = '';

    operationalKeywords.forEach(keyword => {
      const keywordLower = keyword.toLowerCase();
      let score = 0;

      const targetInKeyword = targetFieldName && keywordLower.includes(targetFieldName.toLowerCase());

      if (fieldLower === keywordLower) {
        score = 100;
        if (targetInKeyword) score += 20;
      }
      else if (fieldLower.endsWith('.' + keywordLower)) {
        score = 95;
        if (targetInKeyword) score += 15;
      }
      else if (fieldLower.startsWith(keywordLower + '.')) {
        score = 90;
        if (targetInKeyword) score += 15;
      }
      else if (fieldLower.includes('.' + keywordLower + '.')) {
        score = 85;
        if (targetInKeyword) score += 10;
      }
      else if (fieldLower.includes('_' + keywordLower + '_')) {
        score = 80;
        if (targetInKeyword) score += 10;
      }
      else if (fieldLower.includes(keywordLower)) {
        const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
        score = 70 - lengthPenalty;
        if (targetInKeyword) score += 10;
      }

      if (targetFieldName && fieldLower === targetFieldName.toLowerCase()) {
        score += 25;
      }

      if (targetFieldName) {
        if (fieldLower.includes('.' + targetFieldName.toLowerCase() + '.')) {
          score += 15;
        }
        if (fieldLower.endsWith('.' + targetFieldName.toLowerCase())) {
          score += 20;
        }
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

  matches.sort((a, b) => {
    if (b.score !== a.score) return b.score - a.score;
    return a.field.length - b.field.length;
  });

  matches.forEach((match, i) => {
    console.log(`${i + 1}. ${match.field} (score: ${match.score}, keyword: "${match.keyword}")`);
  });
};

testAlgorithmDebug();