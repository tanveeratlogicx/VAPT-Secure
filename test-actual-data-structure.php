<?php
/**
 * Test script to analyze actual data structure from JSON files
 */

// Load the interface schema
$schema_path = __DIR__ . '/data/interface_schema_v2.0.json';
if (!file_exists($schema_path)) {
    die("Schema file not found: $schema_path\n");
}

$schema = json_decode(file_get_contents($schema_path), true);
if (!$schema) {
    die("Failed to parse schema JSON\n");
}

echo "=== Analyzing Interface Schema ===\n";
echo "Total risks: " . count($schema['risk_interfaces'] ?? []) . "\n";

// Get first risk
$first_risk_key = array_key_first($schema['risk_interfaces']);
$first_risk = $schema['risk_interfaces'][$first_risk_key];

echo "\nFirst Risk ID: $first_risk_key\n";

// Search for verification_steps in the schema
function search_in_array($array, $search_key, $path = '') {
    $results = [];
    foreach ($array as $key => $value) {
        $current_path = $path ? $path . '.' . $key : $key;
        
        if (is_string($key) && stripos($key, $search_key) !== false) {
            $results[] = [
                'path' => $current_path,
                'key' => $key,
                'type' => gettype($value),
                'sample' => is_string($value) ? substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') : null
            ];
        }
        
        if (is_array($value)) {
            $nested = search_in_array($value, $search_key, $current_path);
            $results = array_merge($results, $nested);
        }
    }
    return $results;
}

echo "\n=== Searching for 'verification' related keys ===\n";
$verification_results = search_in_array($first_risk, 'verification');
echo "Found " . count($verification_results) . " verification-related keys:\n";
foreach ($verification_results as $result) {
    echo "  - {$result['path']} ({$result['type']})\n";
    if ($result['sample']) {
        echo "    Sample: {$result['sample']}\n";
    }
}

echo "\n=== Searching for 'operational' related keys ===\n";
$operational_results = search_in_array($first_risk, 'operational');
echo "Found " . count($operational_results) . " operational-related keys:\n";
foreach ($operational_results as $result) {
    echo "  - {$result['path']} ({$result['type']})\n";
    if ($result['sample']) {
        echo "    Sample: {$result['sample']}\n";
    }
}

echo "\n=== Searching for 'context' related keys ===\n";
$context_results = search_in_array($first_risk, 'context');
echo "Found " . count($context_results) . " context-related keys:\n";
foreach ($context_results as $result) {
    echo "  - {$result['path']} ({$result['type']})\n";
    if ($result['sample']) {
        echo "    Sample: {$result['sample']}\n";
    }
}

echo "\n=== Searching for 'testing' related keys ===\n";
$testing_results = search_in_array($first_risk, 'testing');
echo "Found " . count($testing_results) . " testing-related keys:\n";
foreach ($testing_results as $result) {
    echo "  - {$result['path']} ({$result['type']})\n";
    if ($result['sample']) {
        echo "    Sample: {$result['sample']}\n";
    }
}

// Check enforcer pattern library
echo "\n=== Checking Enforcer Pattern Library ===\n";
$pattern_path = __DIR__ . '/data/enforcer_pattern_library_v2.0.json';
if (file_exists($pattern_path)) {
    $pattern_data = json_decode(file_get_contents($pattern_path), true);
    if ($pattern_data && isset($pattern_data['patterns'][$first_risk_key])) {
        $pattern = $pattern_data['patterns'][$first_risk_key];
        
        // Look for verification in pattern
        foreach ($pattern as $enforcer_type => $enforcer_data) {
            if (isset($enforcer_data['verification'])) {
                echo "Found verification in $enforcer_type enforcer:\n";
                var_export($enforcer_data['verification']);
                echo "\n";
            }
        }
    }
}

// Flatten the first risk to see structure
echo "\n=== Flattened Structure of First Risk ===\n";
function flatten_structure($array, $prefix = '', $depth = 0, $max_depth = 3) {
    $result = [];
    if ($depth > $max_depth) {
        return $result;
    }
    
    foreach ($array as $key => $value) {
        $new_key = $prefix ? $prefix . '.' . $key : $key;
        
        if (is_array($value) && !empty($value)) {
            if (is_numeric(key($value))) {
                // Numeric array - just include the key
                $result[] = $new_key . '[]';
            } else {
                // Associative array - recurse
                $result = array_merge($result, flatten_structure($value, $new_key, $depth + 1, $max_depth));
                // Also include parent key
                $result[] = $new_key;
            }
        } else {
            $result[] = $new_key;
        }
    }
    return $result;
}

$flattened = flatten_structure($first_risk);
echo "Total flattened keys (depth limited to 3): " . count($flattened) . "\n";

// Show some sample keys
echo "\nSample flattened keys:\n";
for ($i = 0; $i < min(20, count($flattened)); $i++) {
    echo "  - {$flattened[$i]}\n";
}

// Check for specific patterns
echo "\n=== Looking for specific field patterns ===\n";
$field_patterns = [
    'verification_steps',
    'testing.verification_steps',
    'operational_notes',
    'operational_notes.context',
    'context',
    'steps',
    'verification',
    'testing',
    'notes'
];

foreach ($field_patterns as $pattern) {
    $found = false;
    foreach ($flattened as $key) {
        if (strpos($key, $pattern) !== false) {
            echo "  Found pattern '$pattern' in key: $key\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "  Pattern '$pattern' NOT found in flattened keys\n";
    }
}