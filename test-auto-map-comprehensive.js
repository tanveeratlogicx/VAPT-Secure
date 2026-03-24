const fs = require('fs');
const path = require('path');

// Simulate the Auto Map function from admin.js
const findBestMatch = (keywords, allKeys) => {
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

  return matches.length > 0 ? matches[0].field : '';
};

// Read actual data from the REST API
const getActualData = () => {
  try {
    // Read interface schema to get actual field structure
    const schemaPath = path.join(__dirname, 'data', 'interface_schema_v2.0.json');
    const schemaData = JSON.parse(fs.readFileSync(schemaPath, 'utf8'));

    // Read enforcer pattern library
    const enforcerPath = path.join(__dirname, 'data', 'enforcer_pattern_library_v2.0.json');
    const enforcerData = JSON.parse(fs.readFileSync(enforcerPath, 'utf8'));

    // Read AI instructions
    const aiPath = path.join(__dirname, 'data', 'ai_agent_instructions_v2.0.json');
    const aiData = JSON.parse(fs.readFileSync(aiPath, 'utf8'));

    return { schemaData, enforcerData, aiData };
  } catch (error) {
    console.error('Error reading data files:', error.message);
    return null;
  }
};

// Flatten keys function (same as in admin.js)
const flattenKeys = (obj, prefix = '', depth = 0) => {
  let keys = [];
  if (depth > 1) return keys; // Restrict nesting depth to keep dropdown clean
  for (const key in obj) {
    if (!obj.hasOwnProperty(key)) continue;
    const newKey = prefix ? `${prefix}.${key}` : key;

    if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
      const childKeys = flattenKeys(obj[key], newKey, depth + 1);
      // Include parent nested keys? Let's include child keys primarily
      if (childKeys.length > 0) keys = keys.concat(childKeys);
      // Also include the object key itself if it might be used directly
      keys.push(newKey);
    } else {
      keys.push(newKey);
    }
  }
  return keys;
};

// Extract all possible keys from all data sources
const extractAllKeys = (data) => {
  const { schemaData, enforcerData, aiData } = data;
  let allKeys = [];

  // Extract from schema data
  if (schemaData && schemaData.risk_catalog) {
    // Take first few risks to get sample keys
    const sampleRisks = schemaData.risk_catalog.slice(0, 5);
    sampleRisks.forEach(risk => {
      const flattened = flattenKeys(risk);
      allKeys = allKeys.concat(flattened);
    });
  }

  // Extract from enforcer patterns
  if (enforcerData && enforcerData.patterns) {
    Object.values(enforcerData.patterns).forEach(pattern => {
      Object.values(pattern).forEach(config => {
        if (typeof config === 'object') {
          const flattened = flattenKeys(config);
          allKeys = allKeys.concat(flattened);
        }
      });
    });
  }

  // Extract from AI instructions (as JSON keys)
  if (aiData) {
    const flattened = flattenKeys(aiData);
    allKeys = allKeys.concat(flattened);
  }

  // Remove duplicates
  return [...new Set(allKeys)];
};

// Test the mapping
const testMapping = () => {
  console.log('=== Comprehensive Auto Map Test ===\n');

  const data = getActualData();
  if (!data) {
    console.error('Failed to load data');
    return;
  }

  const allKeys = extractAllKeys(data);
  console.log(`Total unique keys found: ${allKeys.length}`);
  console.log('Sample keys (first 20):', allKeys.slice(0, 20));

  // Current keyword lists from admin.js
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

  console.log('\n=== Testing Operational Context Mapping ===');
  const operationalMatch = findBestMatch(operationalKeywords, allKeys);
  console.log(`Best match for operational_notes: ${operationalMatch}`);

  // Show all matches with scores
  console.log('\nTop 10 matches for operational_notes:');
  const operationalMatches = [];
  allKeys.forEach(field => {
    const fieldLower = field.toLowerCase();
    let bestScore = 0;

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

      if (score > bestScore) bestScore = score;
    });

    if (bestScore > 40) {
      operationalMatches.push({ field, score: bestScore });
    }
  });

  operationalMatches.sort((a, b) => b.score - a.score);
  operationalMatches.slice(0, 10).forEach((match, i) => {
    console.log(`  ${i + 1}. ${match.field} (score: ${match.score})`);
  });

  console.log('\n=== Testing Verification Steps Mapping ===');
  const verificationMatch = findBestMatch(verificationKeywords, allKeys);
  console.log(`Best match for verification_steps: ${verificationMatch}`);

  // Show all matches with scores
  console.log('\nTop 10 matches for verification_steps:');
  const verificationMatches = [];
  allKeys.forEach(field => {
    const fieldLower = field.toLowerCase();
    let bestScore = 0;

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

      if (score > bestScore) bestScore = score;
    });

    if (bestScore > 40) {
      verificationMatches.push({ field, score: bestScore });
    }
  });

  verificationMatches.sort((a, b) => b.score - a.score);
  verificationMatches.slice(0, 10).forEach((match, i) => {
    console.log(`  ${i + 1}. ${match.field} (score: ${match.score})`);
  });

  // Check for specific patterns in the data
  console.log('\n=== Searching for specific patterns in data ===');

  // Search for verification_steps in schema
  if (data.schemaData && data.schemaData.risk_catalog) {
    const firstRisk = data.schemaData.risk_catalog[0];
    console.log('\nFirst risk keys:', Object.keys(firstRisk));

    // Check if testing.verification_steps exists
    if (firstRisk.testing && firstRisk.testing.verification_steps) {
      console.log('Found testing.verification_steps in first risk');
    }

    // Check for operational_notes
    if (firstRisk.operational_notes) {
      console.log('Found operational_notes in first risk:', typeof firstRisk.operational_notes);
    }
  }

  // Search in enforcer patterns
  if (data.enforcerData && data.enforcerData.patterns) {
    const firstPattern = data.enforcerData.patterns['RISK-001'];
    console.log('\nRISK-001 pattern keys:', Object.keys(firstPattern));

    // Check for verification objects
    Object.entries(firstPattern).forEach(([key, value]) => {
      if (value && typeof value === 'object' && value.verification) {
        console.log(`Found verification in ${key}:`, value.verification);
      }
    });
  }
};

// Run the test
testMapping();