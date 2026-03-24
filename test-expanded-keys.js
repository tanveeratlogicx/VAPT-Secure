// Test to verify expanded keys functionality
console.log('=== Testing Expanded Keys Implementation ===\n');

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

// Test with sample data structures
console.log('Test 1: Features data structure');
const sampleFeature = {
  id: 'test-feature',
  operational_notes: {
    context: 'Test context',
    implementation_notes: 'Some notes'
  },
  testing: {
    verification_steps: ['step1', 'step2'],
    other_field: 'value'
  },
  verification_steps: 'root level steps'
};

const featureKeys = flattenKeys(sampleFeature);
console.log('Keys from feature:', featureKeys);
console.log('Expected: operational_notes, operational_notes.context, operational_notes.implementation_notes, testing, testing.verification_steps, testing.other_field, verification_steps\n');

console.log('Test 2: AI Instructions data structure');
const sampleAiInstructions = {
  patterns: {
    'RISK-001': {
      wp_config: {
        verification: {
          command: 'test command',
          steps: ['step1', 'step2']
        },
        note: 'Test note'
      }
    },
    'RISK-002': {
      htaccess: {
        verification: {
          steps: ['check1', 'check2']
        }
      }
    }
  }
};

const aiKeys = flattenKeys(sampleAiInstructions);
console.log('Keys from AI instructions:', aiKeys);
console.log('Expected: patterns, patterns.RISK-001, patterns.RISK-001.wp_config, patterns.RISK-001.wp_config.verification, patterns.RISK-001.wp_config.note, patterns.RISK-002, patterns.RISK-002.htaccess, patterns.RISK-002.htaccess.verification\n');

console.log('Test 3: Combined keys from multiple sources');
const allKeysSet = new Set();
[featureKeys, aiKeys].forEach(keys => {
  keys.forEach(k => allKeysSet.add(k));
});
const allKeys = Array.from(allKeysSet).sort();
console.log('All combined keys:', allKeys);
console.log('Total keys:', allKeys.length);

// Check if we have the critical keys we need
const criticalKeys = [
  'operational_notes.context',
  'testing.verification_steps',
  'patterns.RISK-001.wp_config.verification',
  'patterns.RISK-002.htaccess.verification'
];

console.log('\nChecking for critical keys:');
criticalKeys.forEach(key => {
  const hasKey = allKeys.includes(key);
  console.log(`${hasKey ? '✅' : '❌'} ${key}: ${hasKey ? 'Found' : 'Missing'}`);
});

console.log('\n=== Summary ===');
console.log('The current flattenKeys function with depth limit of 1 will include:');
console.log('- First-level nested keys (e.g., operational_notes.context)');
console.log('- But NOT deeper nested keys (e.g., patterns.RISK-001.wp_config.verification.command)');
console.log('\nTo fix the field mapping issue, we need to:');
console.log('1. Increase the depth limit or adjust the flattenKeys function');
console.log('2. Include keys from rootAiInstructions and rootGlobalSettings in allKeys');
console.log('3. Update the findBestMatch function with improved scoring algorithm');