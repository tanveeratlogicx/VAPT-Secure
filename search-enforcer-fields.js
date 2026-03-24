const fs = require('fs');
const path = require('path');

console.log('=== Searching for verification_steps in enforcer pattern library ===\n');

try {
  const enforcerPath = path.join(__dirname, 'data', 'enforcer_pattern_library_v2.0.json');
  const data = JSON.parse(fs.readFileSync(enforcerPath, 'utf8'));

  const patterns = data.patterns || {};
  let foundCount = 0;

  Object.entries(patterns).forEach(([riskId, riskData]) => {
    Object.entries(riskData).forEach(([enforcerType, enforcerData]) => {
      if (enforcerData.verification) {
        console.log(`Found verification in ${riskId} -> ${enforcerType}`);
        if (enforcerData.verification.steps) {
          console.log(`  Steps: ${JSON.stringify(enforcerData.verification.steps)}`);
        }
        if (enforcerData.verification.manual_verification) {
          console.log(`  Manual verification: ${JSON.stringify(enforcerData.verification.manual_verification)}`);
        }
        foundCount++;
      }
    });
  });

  console.log(`\nTotal verification objects found: ${foundCount}`);

  console.log('\n=== Searching for operational context/notes ===\n');
  let contextCount = 0;
  Object.entries(patterns).forEach(([riskId, riskData]) => {
    Object.entries(riskData).forEach(([enforcerType, enforcerData]) => {
      if (enforcerData.operational_notes) {
        console.log(`Found operational_notes in ${riskId} -> ${enforcerType}`);
        console.log(`  Notes: ${JSON.stringify(enforcerData.operational_notes)}`);
        contextCount++;
      }
    });
  });

  console.log(`\nTotal operational_notes found: ${contextCount}`);

  // Also check for context fields
  console.log('\n=== Searching for context fields ===\n');
  let contextFields = new Set();
  Object.entries(patterns).forEach(([riskId, riskData]) => {
    Object.entries(riskData).forEach(([enforcerType, enforcerData]) => {
      // Look for any field containing "context"
      const jsonStr = JSON.stringify(enforcerData);
      if (jsonStr.includes('context')) {
        console.log(`Found "context" in ${riskId} -> ${enforcerType}`);
        // Extract context-related fields
        Object.keys(enforcerData).forEach(key => {
          if (key.toLowerCase().includes('context')) {
            contextFields.add(key);
          }
        });
      }
    });
  });

  console.log(`\nUnique context fields found: ${Array.from(contextFields).join(', ')}`);

} catch (error) {
  console.error('Error:', error.message);
  console.error('Stack:', error.stack);
}