// Debug script to analyze field mapping issues
console.log('=== Field Mapping Debug Analysis ===');

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

// Test data structure from the REST API
const testFeatureData = {
  "id": "RISK-001",
  "title": "wp-cron.php Enabled Leads to DoS Attack",
  "category": "Configuration",
  "severity": "high",
  "owasp": {
    "category": "A05:2021-Security Misconfiguration",
    "reference": "https://owasp.org/Top10/A05_2021-Security_Misconfiguration/"
  },
  "ui_layout": {
    "type": "card",
    "columns": 1,
    "responsive": true
  },
  "components": [
    {
      "type": "severity_badge",
      "props": {
        "severity": "high",
        "label": "High Risk"
      }
    }
  ],
  "actions": [
    {
      "type": "button",
      "label": "Protect",
      "action": "enable_protection",
      "variant": "primary"
    }
  ],
  "platform_implementations": {
    "wp-config.php": {
      "lib_key": "wp_config",
      "operation": "add_constant",
      "target_file": "wp-config.php"
    }
  },
  "status_indicators": {
    "protected": {
      "color": "#10b981",
      "icon": "shield-check"
    },
    "unprotected": {
      "color": "#ef4444",
      "icon": "shield-exclamation"
    }
  },
  "testing": {
    "verification_steps": [
      {
        "step_number": 1,
        "action": "Test wp-cron.php access",
        "expected_result": "Should be blocked or redirected",
        "command": "curl -I https://{domain}/wp-cron.php",
        "automated": true
      }
    ]
  },
  "operational_notes": {
    "context": "This risk addresses the default WordPress cron system which runs on every page load, potentially leading to denial of service attacks.",
    "implementation_notes": "Disable WP-CRON and set up a proper server cron job",
    "impact": "High performance impact if not mitigated"
  }
};

console.log('Test feature data structure:');
console.log(JSON.stringify(testFeatureData, null, 2));

const flattenedKeys = flattenKeys(testFeatureData);
console.log('\nFlattened keys:');
console.log(flattenedKeys);

// Check for specific keys
console.log('\nLooking for verification_steps related keys:');
const verificationKeys = flattenedKeys.filter(key =>
  key.toLowerCase().includes('verification') ||
  key.toLowerCase().includes('step') ||
  key.toLowerCase().includes('test')
);
console.log(verificationKeys);

console.log('\nLooking for operational_notes related keys:');
const operationalKeys = flattenedKeys.filter(key =>
  key.toLowerCase().includes('operational') ||
  key.toLowerCase().includes('note') ||
  key.toLowerCase().includes('context')
);
console.log(operationalKeys);

// Test the scoring algorithm
const findBestMatch = (keywords, allKeys) => {
  let bestMatch = null;
  let bestScore = -1;

  for (const key of allKeys) {
    let score = 0;
    const keyLower = key.toLowerCase();

    // Exact match
    if (keywords.some(kw => keyLower === kw.toLowerCase())) {
      score = 100;
    }
    // Contains match
    else if (keywords.some(kw => keyLower.includes(kw.toLowerCase()))) {
      score = 50;
    }
    // Partial match (word boundary)
    else if (keywords.some(kw => {
      const kwLower = kw.toLowerCase();
      return keyLower.includes(kwLower) ||
        kwLower.includes(keyLower) ||
        keyLower.split(/[._-]/).some(part => part === kwLower) ||
        kwLower.split(/[._-]/).some(part => part === keyLower);
    })) {
      score = 25;
    }

    if (score > bestScore) {
      bestScore = score;
      bestMatch = key;
    }
  }

  return { match: bestMatch, score: bestScore };
};

console.log('\n=== Testing matching algorithm ===');

// Test verification_steps
const verificationKeywords = [
  'verification_steps', 'verification.steps', 'verification_steps', 'manual_verification',
  'steps', 'test_steps', 'testing_steps', 'validation_steps', 'test_method', 'verification',
  'owasp', 'testing', 'validation', 'checks', 'procedures', 'manual_testing', 'test_procedure',
  'verificationSteps', 'verification-steps', 'test_steps', 'testing_steps', 'validation_steps',
  'test.method', 'test.methodology', 'test_procedure', 'test.procedure', 'steps.verification',
  'verification.steps', 'manual_test', 'manual.test', 'test.manual', 'test_manual', 'checklist',
  'test_checklist', 'testing.checklist', 'validation.checklist'
];

const verificationMatch = findBestMatch(verificationKeywords, flattenedKeys);
console.log('Verification steps match:', verificationMatch);

// Test operational_notes
const operationalKeywords = [
  'operational_notes', 'operational_context', 'context', 'operation_context', 'operation_notes',
  'operation_details', 'notes', 'summary', 'remarks', 'comments', 'guidance', 'instructions',
  'documentation', 'background', 'environment', 'contextual', 'operationalContext',
  'operational-context', 'op_context', 'op_notes', 'opnotes', 'opcontext', 'context.operational',
  'notes.operational', 'description.context', 'context.description', 'info', 'additional_info',
  'additional_info.context', 'context.additional'
];

const operationalMatch = findBestMatch(operationalKeywords, flattenedKeys);
console.log('Operational notes match:', operationalMatch);

console.log('\n=== Analysis ===');
console.log('The issue appears to be that the flattened keys from the data structure are:');
console.log('- testing.verification_steps (nested under testing)');
console.log('- operational_notes (top level, but contains nested context field)');
console.log('\nThe flattenKeys function creates keys like:');
console.log('- "testing.verification_steps" for nested objects');
console.log('- "operational_notes" for the parent object (but not "operational_notes.context")');

// Check what the actual REST API returns
console.log('\n=== Checking REST API structure ===');
console.log('From class-vaptsecure-rest.php:');
console.log('1. operational_notes comes from database meta: operational_notes_content');
console.log('2. verification_steps comes from JSON data: testing.verification_steps');
console.log('3. The REST API processes these and adds them to the feature object');

console.log('\n=== Recommendation ===');
console.log('We need to enhance the keyword matching to include:');
console.log('1. "testing.verification_steps" as a keyword');
console.log('2. "operational_notes.context" as a keyword');
console.log('3. Also search for parent keys when child keys are not found');