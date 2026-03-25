// Test to understand and fix the scoring issue
const testScoring = () => {
  console.log('=== Testing Scoring Algorithm ===\n');

  // Simulated flattened keys from actual data
  const allKeys = [
    'id',
    'title',
    'category',
    'severity',
    'owasp.category',
    'owasp.reference',
    'owasp',
    'ui_layout.type',
    'ui_layout.columns',
    'ui_layout.responsive',
    'ui_layout',
    'components',
    'actions',
    'platform_implementations.wp-config.php',
    'platform_implementations',
    'status_indicators.protected',
    'status_indicators.unprotected',
    'status_indicators',
    'testing.verification_steps',
    'testing',
    'operational_notes.context',
    'operational_notes.implementation_notes',
    'operational_notes.impact',
    'operational_notes'
  ];

  // Current keyword lists
  const operationalKeywords = [
    'operational_notes', 'operational_notes.context', 'operational_context', 'context',
    'operation_context', 'operation_notes', 'operation_details', 'notes', 'summary',
    'remarks', 'comments', 'guidance', 'instructions', 'documentation', 'background',
    'environment', 'contextual', 'operationalContext', 'operational-context',
    'op_context', 'op_notes', 'opnotes', 'opcontext', 'context.operational',
    'notes.operational', 'description.context', 'context.description', 'info',
    'additional_info', 'additional_info.context', 'context.additional',
    'notes.context', 'context.notes', 'operational.context', 'context.operational_notes'
  ];

  const verificationKeywords = [
    'verification_steps', 'testing.verification_steps', 'verification.steps',
    'verification_steps', 'manual_verification', 'steps', 'test_steps',
    'testing_steps', 'validation_steps', 'test_method', 'verification',
    'owasp', 'testing', 'validation', 'checks', 'procedures', 'manual_testing',
    'test_procedure', 'verificationSteps', 'verification-steps', 'test_steps',
    'testing_steps', 'validation_steps', 'test.method', 'test.methodology',
    'test_procedure', 'test.procedure', 'steps.verification', 'verification.steps',
    'manual_test', 'manual.test', 'test.manual', 'test_manual', 'checklist',
    'test_checklist', 'testing.checklist', 'validation.checklist',
    'testing.verification', 'verification.testing', 'test.verification', 'verification.test'
  ];

  // Current scoring algorithm
  const findBestMatchCurrent = (keywords, allKeys) => {
    const matches = [];

    allKeys.forEach(field => {
      const fieldLower = field.toLowerCase();
      let bestScore = 0;
      let bestKeyword = '';

      keywords.forEach(keyword => {
        const keywordLower = keyword.toLowerCase();
        let score = 0;

        // Exact match (highest priority)
        if (fieldLower === keywordLower) {
          score = 100;
        }
        // Field ends with .keyword (nested match)
        else if (fieldLower.endsWith('.' + keywordLower)) {
          score = 90;
        }
        // Field starts with keyword.
        else if (fieldLower.startsWith(keywordLower + '.')) {
          score = 85;
        }
        // Field contains keyword as whole word (with word boundaries)
        else if (fieldLower.includes('.' + keywordLower + '.') ||
          fieldLower.includes('_' + keywordLower + '_') ||
          fieldLower.includes(' ' + keywordLower + ' ')) {
          score = 80;
        }
        // Field contains keyword (partial match)
        else if (fieldLower.includes(keywordLower)) {
          // Penalize longer field names for partial matches
          const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
          score = 70 - lengthPenalty;
        }
        // Levenshtein distance for fuzzy matching (fallback)
        else {
          // Simple similarity check
          const similarity = keywordLower.split('').filter(c => fieldLower.includes(c)).length / keywordLower.length;
          if (similarity > 0.7) {
            score = Math.floor(similarity * 60);
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

    return matches.length > 0 ? matches[0] : null;
  };

  console.log('=== Current Algorithm Results ===');

  const operationalMatch = findBestMatchCurrent(operationalKeywords, allKeys);
  console.log('Operational Context match:', operationalMatch);

  const verificationMatch = findBestMatchCurrent(verificationKeywords, allKeys);
  console.log('Verification Steps match:', verificationMatch);

  // Show top 5 matches for each
  console.log('\n=== Top 5 matches for Operational Context ===');
  const operationalMatches = [];
  allKeys.forEach(field => {
    const fieldLower = field.toLowerCase();
    let bestScore = 0;
    let bestKeyword = '';

    operationalKeywords.forEach(keyword => {
      const keywordLower = keyword.toLowerCase();
      let score = 0;

      if (fieldLower === keywordLower) score = 100;
      else if (fieldLower.endsWith('.' + keywordLower)) score = 90;
      else if (fieldLower.startsWith(keywordLower + '.')) score = 85;
      else if (fieldLower.includes(keywordLower)) {
        const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
        score = 70 - lengthPenalty;
      }

      if (score > bestScore) {
        bestScore = score;
        bestKeyword = keyword;
      }
    });

    if (bestScore > 40) {
      operationalMatches.push({ field, score: bestScore, keyword: bestKeyword });
    }
  });

  operationalMatches.sort((a, b) => b.score - a.score);
  operationalMatches.slice(0, 5).forEach((match, i) => {
    console.log(`${i + 1}. ${match.field} (score: ${match.score}, keyword: ${match.keyword})`);
  });

  console.log('\n=== Top 5 matches for Verification Steps ===');
  const verificationMatches = [];
  allKeys.forEach(field => {
    const fieldLower = field.toLowerCase();
    let bestScore = 0;
    let bestKeyword = '';

    verificationKeywords.forEach(keyword => {
      const keywordLower = keyword.toLowerCase();
      let score = 0;

      if (fieldLower === keywordLower) score = 100;
      else if (fieldLower.endsWith('.' + keywordLower)) score = 90;
      else if (fieldLower.startsWith(keywordLower + '.')) score = 85;
      else if (fieldLower.includes(keywordLower)) {
        const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
        score = 70 - lengthPenalty;
      }

      if (score > bestScore) {
        bestScore = score;
        bestKeyword = keyword;
      }
    });

    if (bestScore > 40) {
      verificationMatches.push({ field, score: bestScore, keyword: bestKeyword });
    }
  });

  verificationMatches.sort((a, b) => b.score - a.score);
  verificationMatches.slice(0, 5).forEach((match, i) => {
    console.log(`${i + 1}. ${match.field} (score: ${match.score}, keyword: ${match.keyword})`);
  });

  // Analyze the problem
  console.log('\n=== Problem Analysis ===');
  console.log('1. "owasp" is in verificationKeywords list, so it matches field "owasp" with score 100');
  console.log('2. "operational_notes" is in operationalKeywords list, so it matches field "operational_notes" with score 100');
  console.log('3. But we want "testing.verification_steps" and "operational_notes.context"');

  // Improved algorithm
  console.log('\n=== Improved Algorithm ===');

  const findBestMatchImproved = (keywords, allKeys, targetFieldName) => {
    const matches = [];

    allKeys.forEach(field => {
      const fieldLower = field.toLowerCase();
      let bestScore = 0;
      let bestKeyword = '';

      keywords.forEach(keyword => {
        const keywordLower = keyword.toLowerCase();
        let score = 0;

        // Exact match (highest priority) - give bonus for exact match with target field name
        if (fieldLower === keywordLower) {
          score = 100;
          // Bonus if keyword matches the target field name we're looking for
          if (keywordLower.includes(targetFieldName.toLowerCase())) {
            score += 10;
          }
        }
        // Field ends with .keyword (nested match)
        else if (fieldLower.endsWith('.' + keywordLower)) {
          score = 90;
          // Bonus for nested matches that include the target field name
          if (keywordLower.includes(targetFieldName.toLowerCase())) {
            score += 15;
          }
        }
        // Field starts with keyword.
        else if (fieldLower.startsWith(keywordLower + '.')) {
          score = 85;
        }
        // Field contains keyword as whole word (with word boundaries)
        else if (fieldLower.includes('.' + keywordLower + '.') ||
          fieldLower.includes('_' + keywordLower + '_') ||
          fieldLower.includes(' ' + keywordLower + ' ')) {
          score = 80;
        }
        // Field contains keyword (partial match)
        else if (fieldLower.includes(keywordLower)) {
          // Penalize longer field names for partial matches
          const lengthPenalty = Math.min(20, (fieldLower.length - keywordLower.length) * 2);
          score = 70 - lengthPenalty;
          // Bonus if the field contains the full target field name
          if (fieldLower.includes(targetFieldName.toLowerCase())) {
            score += 20;
          }
        }
        // Levenshtein distance for fuzzy matching (fallback)
        else {
          // Simple similarity check
          const similarity = keywordLower.split('').filter(c => fieldLower.includes(c)).length / keywordLower.length;
          if (similarity > 0.7) {
            score = Math.floor(similarity * 60);
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

    return matches.length > 0 ? matches[0] : null;
  };

  console.log('\n=== Improved Algorithm Results ===');

  const operationalMatchImproved = findBestMatchImproved(operationalKeywords, allKeys, 'operational_notes');
  console.log('Operational Context match (improved):', operationalMatchImproved);

  const verificationMatchImproved = findBestMatchImproved(verificationKeywords, allKeys, 'verification_steps');
  console.log('Verification Steps match (improved):', verificationMatchImproved);

  // Another approach: prioritize keywords that contain the target field name
  console.log('\n=== Prioritizing Target Field Names ===');

  const prioritizeTargetKeywords = (keywords, targetFieldName) => {
    const targetLower = targetFieldName.toLowerCase();
    return keywords.map(keyword => ({
      keyword,
      priority: keyword.toLowerCase().includes(targetLower) ? 1 : 0
    })).sort((a, b) => b.priority - a.priority).map(item => item.keyword);
  };

  const prioritizedOperationalKeywords = prioritizeTargetKeywords(operationalKeywords, 'operational_notes');
  const prioritizedVerificationKeywords = prioritizeTargetKeywords(verificationKeywords, 'verification_steps');

  console.log('Prioritized operational keywords (first 5):', prioritizedOperationalKeywords.slice(0, 5));
  console.log('Prioritized verification keywords (first 5):', prioritizedVerificationKeywords.slice(0, 5));

  // Test with prioritized keywords
  const operationalMatchPrioritized = findBestMatchCurrent(prioritizedOperationalKeywords, allKeys);
  const verificationMatchPrioritized = findBestMatchCurrent(prioritizedVerificationKeywords, allKeys);

  console.log('\nOperational Context match (prioritized):', operationalMatchPrioritized);
  console.log('Verification Steps match (prioritized):', verificationMatchPrioritized);
};

testScoring();