<?php
/**
 * Test script to examine field structure for mapping
 */

require_once __DIR__ . '/vaptsecure.php';

// Simulate the flattenKeys function from admin.js
function flattenKeys($obj, $prefix = '', $depth = 0) {
    $keys = [];
    if ($depth > 1) return $keys;
    
    foreach ($obj as $key => $value) {
        $newKey = $prefix ? $prefix . '.' . $key : $key;
        
        if (is_array($value) && !empty($value) && !isset($value[0])) { // Associative array, not indexed
            $childKeys = flattenKeys($value, $newKey, $depth + 1);
            if (!empty($childKeys)) {
                $keys = array_merge($keys, $childKeys);
            }
            $keys[] = $newKey;
        } else {
            $keys[] = $newKey;
        }
    }
    
    return $keys;
}

// Sample data based on REST class processing
$sampleFeature = [
    'id' => 'RISK-001',
    'key' => 'RISK-001',
    'title' => 'Test Risk',
    'testing' => [
        'test_method' => 'Directory access test',
        'verification_steps' => [
            ['step_number' => 1, 'action' => 'Test Directory Listing Enabled'],
            ['step_number' => 2, 'action' => 'Verify protection is active']
        ]
    ],
    'operational_notes' => 'Some operational context here',
    'verification_steps' => ['Test Directory Listing Enabled', 'Verify protection is active'] // Added by REST class
];

echo "Sample feature structure:\n";
print_r($sampleFeature);

echo "\n\nFlattened keys:\n";
$keys = flattenKeys($sampleFeature);
foreach ($keys as $key) {
    echo "  - $key\n";
}

echo "\nKey checks:\n";
echo "Has 'verification_steps': " . (in_array('verification_steps', $keys) ? 'YES' : 'NO') . "\n";
echo "Has 'testing.verification_steps': " . (in_array('testing.verification_steps', $keys) ? 'YES' : 'NO') . "\n";
echo "Has 'operational_notes': " . (in_array('operational_notes', $keys) ? 'YES' : 'NO') . "\n";

// Test the actual Auto Map matching logic
echo "\n\nTesting Auto Map matching:\n";

$verificationKeywords = ['verification_steps', 'testing.verification_steps', 'verification.steps', 'verification_steps', 'manual_verification', 'steps', 'test_steps'];
$operationalKeywords = ['operational_notes', 'operational_notes.context', 'operational_context', 'context', 'operation_context', 'operation_notes'];

$verificationMatches = [];
$operationalMatches = [];

foreach ($keys as $key) {
    foreach ($verificationKeywords as $keyword) {
        if (stripos($key, $keyword) !== false) {
            $verificationMatches[] = $key;
            break;
        }
    }
    
    foreach ($operationalKeywords as $keyword) {
        if (stripos($key, $keyword) !== false) {
            $operationalMatches[] = $key;
            break;
        }
    }
}

echo "Verification steps matches: " . implode(', ', array_unique($verificationMatches)) . "\n";
echo "Operational notes matches: " . implode(', ', array_unique($operationalMatches)) . "\n";
?>