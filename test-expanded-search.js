// Test script to analyze expanded search range for field mapping
const fs = require('fs');
const path = require('path');

// Simulate the flattenKeys function from admin.js
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

// Read and analyze data files
console.log('=== Analyzing Data Files for Field Mapping ===\n');

// 1. Read enforcer pattern library
try {
  const enforcerPath = path.join(__dirname, 'data', 'enforcer_pattern_library_v2.0.json');
  const enforcerData = JSON.parse(fs.readFileSync(enforcerPath, 'utf8'));
  const enforcerKeys = flattenKeys(enforcerData);

  console.log('Enforcer Pattern Library Keys:');
  console.log(`Total keys: ${enforcerKeys.length}`);

  // Filter for verification and operational related keys
  const verificationKeys = enforcerKeys.filter(k =>
    k.toLowerCase().includes('verification') ||
    k.toLowerCase().includes('operational') ||
    k.toLowerCase().includes('context') ||
    k.toLowerCase().includes('steps')
  );

  console.log('\nRelevant keys for verification/operational mapping:');
  verificationKeys.forEach(k => console.log(`  - ${k}`));

  // Look for specific patterns
  console.log('\nLooking for verification_steps patterns:');
  const stepsKeys = enforcerKeys.filter(k => k.toLowerCase().includes('verification_steps'));
  stepsKeys.forEach(k => console.log(`  - ${k}`));

  console.log('\nLooking for operational_notes patterns:');
  const operationalKeys = enforcerKeys.filter(k => k.toLowerCase().includes('operational'));
  operationalKeys.forEach(k => console.log(`  - ${k}`));

} catch (err) {
  console.log(`Error reading enforcer pattern library: ${err.message}`);
}

console.log('\n---\n');

// 2. Read AI agent instructions
try {
  const aiPath = path.join(__dirname, 'data', 'ai_agent_instructions_v2.0.json');
  const aiData = JSON.parse(fs.readFileSync(aiPath, 'utf8'));
  const aiKeys = flattenKeys(aiData);

  console.log('AI Agent Instructions Keys:');
  console.log(`Total keys: ${aiKeys.length}`);

  // Filter for verification and operational related keys
  const relevantAiKeys = aiKeys.filter(k =>
    k.toLowerCase().includes('verification') ||
    k.toLowerCase().includes('operational') ||
    k.toLowerCase().includes('context') ||
    k.toLowerCase().includes('steps')
  );

  console.log('\nRelevant keys for verification/operational mapping:');
  relevantAiKeys.forEach(k => console.log(`  - ${k}`));

} catch (err) {
  console.log(`Error reading AI agent instructions: ${err.message}`);
}

console.log('\n=== Recommendations for Field Mapping ===');
console.log('1. The flattenKeys function should be modified to include keys from these additional data files.');
console.log('2. The Auto Map algorithm should search across all available data sources, not just the schema file.');
console.log('3. Consider adding a "data source" parameter to the findBestMatch function to prioritize matches from different sources.');