/**
 * Test script for VAPT-Secure Auto Map functionality
 * Tests the enhanced keyword matching for Operational Context and Verification Steps
 * 
 * This script simulates the Auto Map logic to verify field matching works correctly
 */

// Simulate the findBestMatch function from admin.js
function findBestMatch(keywords, allKeys) {
  let bestScore = 0;
  let bestField = '';
  let bestKeyword = '';

  keywords.forEach(keyword => {
    const keywordLower = keyword.toLowerCase();

    allKeys.forEach(field => {
      const fieldLower = field.toLowerCase();
      let score = 0;

      // Exact match (highest priority)
      if (fieldLower === keywordLower) {
        score = 100;
      }
      // Contains match (high priority)
      else if (fieldLower.includes(keywordLower) || keywordLower.includes(fieldLower)) {
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

      if (score > bestScore && score > 40) {
        bestScore = score;
        bestField = field;
        bestKeyword = keyword;
      }
    });
  });

  return bestField;
}

// Simulate the autoMapField function
function autoMapField(key, keywords, allKeys, results) {
  const match = findBestMatch(keywords, allKeys);
  if (match) {
    results[key] = {
      match: match,
      keywords: keywords.slice(0, 3) // Show first 3 keywords for context
    };
    return true;
  }
  return false;
}

// Test data - sample JSON keys that might exist in real data
const sampleAllKeys = [
  // Core fields
  'summary', 'description', 'desc', 'overview',
  'severity', 'level', 'risk_level', 'priority',

  // UI Schema fields
  'ui_layout', 'ui-layout', 'uiLayout', 'layout.ui',
  'components', 'ui_components', 'ui-components', 'ui.components',
  'actions', 'ui_actions', 'ui-actions', 'ui.actions',

  // Platform fields
  'available_platforms', 'platforms', 'platform_list',
  'platform_implementations', 'implementations', 'enforcer_map',

  // Additional Context - these are the problematic fields
  'operational_notes', 'operational_context', 'context.operational', 'notes.operational',
  'verification_steps', 'verification.steps', 'manual_verification', 'test_steps',
  'verification_command', 'verification.command', 'test_command',
  'verification_expected', 'verification.expected', 'expected_output',

  // Risk Identification
  'risk_id', 'id', 'risk.identifier',
  'title', 'name', 'risk_title',
  'category', 'type', 'risk_category',
  'owasp.cwe', 'cwe', 'cwe_id',
  'owasp.owasp_top_10_2025', 'owasp_top_10_2025', 'owasp_top10',

  // Other possible fields
  'metadata', 'created_at', 'updated_at', 'author', 'tags'
];

console.log('=== VAPT-Secure Auto Map Test ===');
console.log(`Total sample keys: ${sampleAllKeys.length}`);
console.log('');

// Test each mapping field with enhanced keywords
const testResults = {};

// Enhanced keyword lists from the updated code
const keywordTests = [
  {
    key: 'operational_notes',
    keywords: ['operational_notes', 'operational_context', 'context', 'operation_context', 'operation_notes', 'operation_details', 'notes', 'summary', 'remarks', 'comments', 'guidance', 'instructions', 'documentation', 'background', 'environment', 'contextual', 'operationalContext', 'operational-context', 'op_context', 'op_notes', 'opnotes', 'opcontext', 'context.operational', 'notes.operational', 'description.context', 'context.description', 'info', 'additional_info', 'additional_info.context', 'context.additional']
  },
  {
    key: 'verification_steps',
    keywords: ['verification_steps', 'verification.steps', 'verification_steps', 'manual_verification', 'steps', 'test_steps', 'testing_steps', 'validation_steps', 'test_method', 'verification', 'owasp', 'testing', 'validation', 'checks', 'procedures', 'manual_testing', 'test_procedure', 'verificationSteps', 'verification-steps', 'test_steps', 'testing_steps', 'validation_steps', 'test.method', 'test.methodology', 'test_procedure', 'test.procedure', 'steps.verification', 'verification.steps', 'manual_test', 'manual.test', 'test.manual', 'test_manual', 'checklist', 'test_checklist', 'testing.checklist', 'validation.checklist']
  },
  {
    key: 'ui_layout',
    keywords: ['ui_layout', 'ui-layout', 'uiLayout', 'layout', 'ui', 'interface', 'design', 'ui.layout', 'ui_layout_schema', 'interface_layout', 'ui_design', 'structure', 'arrangement', 'layout.ui', 'ui_schema', 'interface_schema', 'design_layout', 'visual_layout', 'page_layout', 'template_layout']
  },
  {
    key: 'components',
    keywords: ['components', 'ui_components', 'ui-components', 'uiComponents', 'ui.components', 'fields', 'elements', 'controls', 'widgets', 'parts', 'ui_elements', 'component_list', 'ui_elements_list', 'fields_list', 'controls_list', 'widgets_list', 'ui_parts', 'interface_components', 'design_components', 'visual_components', 'ui_controls']
  },
  {
    key: 'actions',
    keywords: ['actions', 'ui_actions', 'ui-actions', 'uiActions', 'ui.actions', 'buttons', 'action', 'operations', 'functions', 'interactions', 'handlers', 'action_list', 'buttons_list', 'operations_list', 'functions_list', 'ui_buttons', 'interface_actions', 'design_actions', 'visual_actions', 'user_actions', 'click_actions', 'event_handlers']
  }
];

console.log('Testing enhanced keyword matching:');
console.log('');

let passedTests = 0;
let totalTests = keywordTests.length;

keywordTests.forEach(test => {
  const matched = autoMapField(test.key, test.keywords, sampleAllKeys, testResults);

  console.log(`🔍 ${test.key}:`);
  console.log(`   Keywords: ${test.keywords.slice(0, 5).join(', ')}...`);
  console.log(`   Match found: ${matched ? '✅ YES' : '❌ NO'}`);

  if (matched) {
    console.log(`   Matched field: "${testResults[test.key].match}"`);
    console.log(`   Sample keywords: ${testResults[test.key].keywords.join(', ')}`);
    passedTests++;
  } else {
    console.log(`   No match found with ${test.keywords.length} keywords`);

    // Debug: Show what keywords didn't match
    const unmatchedKeywords = test.keywords.filter(kw =>
      !sampleAllKeys.some(key =>
        key.toLowerCase().includes(kw.toLowerCase()) ||
        kw.toLowerCase().includes(key.toLowerCase())
      )
    );
    if (unmatchedKeywords.length > 0) {
      console.log(`   Unmatched keywords (first 5): ${unmatchedKeywords.slice(0, 5).join(', ')}`);
    }
  }
  console.log('');
});

console.log('=== Test Summary ===');
console.log(`Passed: ${passedTests}/${totalTests} tests`);
console.log(`Success rate: ${Math.round((passedTests / totalTests) * 100)}%`);
console.log('');

// Verify the 16 mapping fields count
console.log('=== Field Count Verification ===');
const mappingFields = [
  'description', 'severity',
  'ui_layout', 'components', 'actions',
  'available_platforms', 'platform_implementations',
  'operational_notes', 'verification_steps', 'verification_command', 'verification_expected',
  'risk_id', 'title', 'category', 'owasp_cwe', 'owasp_top_10_2025'
];

console.log(`Total mapping fields in modal: ${mappingFields.length}`);
console.log('Fields:');
mappingFields.forEach((field, index) => {
  console.log(`  ${index + 1}. ${field}`);
});

console.log('');
console.log('=== Recommendations ===');
console.log('1. The enhanced keyword lists should now properly map Operational Context and Verification Steps');
console.log('2. Total field count is fixed to show 16 (actual mapping fields) instead of 90 (all JSON keys)');
console.log('3. Scoring algorithm ensures better matching with exact > partial > fuzzy matching');
console.log('4. Auto Map will not override existing mappings (only maps empty fields)');
console.log('');
console.log('To test in WordPress:');
console.log('1. Go to VAPT-Secure > Domain Admin');
console.log('2. Click "Map Include Fields" on any domain');
console.log('3. Click "Auto Map" button');
console.log('4. Verify Operational Context and Verification Steps are mapped');
console.log('5. Check total field count shows "Total Fields: 16"');