// Debug script to understand field structure
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

// Load sample data from interface schema
try {
  const schemaPath = path.join(__dirname, 'data', 'interface_schema_v2.0.json');
  const schemaData = JSON.parse(fs.readFileSync(schemaPath, 'utf8'));

  // Get first risk to examine structure
  const firstRiskKey = Object.keys(schemaData.risk_interfaces)[0];
  const firstRisk = schemaData.risk_interfaces[firstRiskKey];

  console.log('=== Examining first risk structure ===');
  console.log('Risk ID:', firstRisk.risk_id);
  console.log('Title:', firstRisk.title);

  // Flatten the keys
  const flatKeys = flattenKeys(firstRisk);
  console.log('\n=== Flattened keys ===');
  console.log(flatKeys.slice(0, 50).join('\n'));
  console.log(`\nTotal keys: ${flatKeys.length}`);

  // Look for verification_steps or operational_notes
  console.log('\n=== Searching for verification_steps ===');
  const verificationKeys = flatKeys.filter(k => k.includes('verification') || k.includes('test'));
  console.log(verificationKeys.join('\n'));

  console.log('\n=== Searching for operational_notes ===');
  const operationalKeys = flatKeys.filter(k => k.includes('operational') || k.includes('notes') || k.includes('context'));
  console.log(operationalKeys.join('\n'));

  // Check if testing.verification_steps exists
  console.log('\n=== Checking for testing.verification_steps ===');
  if (firstRisk.testing && firstRisk.testing.verification_steps) {
    console.log('Found testing.verification_steps:', firstRisk.testing.verification_steps.length, 'steps');
  } else {
    console.log('No testing.verification_steps found in first risk');
  }

} catch (error) {
  console.error('Error:', error.message);
}

// Also check enforcer pattern library
try {
  const enforcerPath = path.join(__dirname, 'data', 'enforcer_pattern_library_v2.0.json');
  const enforcerData = JSON.parse(fs.readFileSync(enforcerPath, 'utf8'));

  // Get first pattern
  const firstPatternKey = Object.keys(enforcerData.patterns)[0];
  const firstPattern = enforcerData.patterns[firstPatternKey];

  console.log('\n\n=== Examining enforcer pattern structure ===');
  console.log('Pattern ID:', firstPattern.risk_id);

  // Check for verification_steps in enforcer templates
  console.log('\n=== Checking enforcer templates for verification_steps ===');
  const enforcerTypes = ['htaccess', 'wp_config', 'php_functions', 'wordpress'];

  enforcerTypes.forEach(type => {
    if (firstPattern[type] && firstPattern[type].verification) {
      console.log(`${type}: Has verification object`);
      if (firstPattern[type].verification.steps || firstPattern[type].verification.command) {
        console.log(`  - Has verification steps or command`);
      }
    }
  });

} catch (error) {
  console.error('Error reading enforcer pattern:', error.message);
}