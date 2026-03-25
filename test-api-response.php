<?php
/**
 * Test script to check actual API response for field mapping
 */

require_once __DIR__ . '/vaptsecure.php';

// Check if we can access the REST API
add_action('rest_api_init', function() {
    // Try to get features data
    $request = new WP_REST_Request('GET', '/vaptsecure/v1/features');
    $request->set_param('file', 'interface_schema_v2.0.json');
    
    $response = rest_do_request($request);
    
    if ($response->is_error()) {
        echo "Error getting features: " . $response->as_error()->get_error_message() . "\n";
        return;
    }
    
    $data = $response->get_data();
    
    if (!empty($data) && is_array($data)) {
        echo "Total features: " . count($data) . "\n";
        
        // Check first feature for operational_notes and verification_steps
        $first_feature = $data[0] ?? [];
        
        echo "\nFirst feature keys:\n";
        print_r(array_keys($first_feature));
        
        echo "\nChecking for specific fields:\n";
        echo "Has 'operational_notes': " . (isset($first_feature['operational_notes']) ? 'YES' : 'NO') . "\n";
        echo "Has 'verification_steps': " . (isset($first_feature['verification_steps']) ? 'YES' : 'NO') . "\n";
        echo "Has 'testing': " . (isset($first_feature['testing']) ? 'YES' : 'NO') . "\n";
        
        if (isset($first_feature['testing'])) {
            echo "Testing keys: " . implode(', ', array_keys($first_feature['testing'])) . "\n";
            if (isset($first_feature['testing']['verification_steps'])) {
                echo "Has 'testing.verification_steps': YES\n";
            }
        }
        
        // Check flattened keys
        function flattenKeys($obj, $prefix = '', $depth = 0) {
            $keys = [];
            if ($depth > 1) return $keys;
            
            foreach ($obj as $key => $value) {
                $newKey = $prefix ? $prefix . '.' . $key : $key;
                
                if (is_array($value) && !empty($value) && !isset($value[0])) {
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
        
        $flatKeys = flattenKeys($first_feature);
        echo "\nFlattened keys (first 20):\n";
        $count = 0;
        foreach ($flatKeys as $key) {
            echo "  - $key\n";
            if (++$count >= 20) break;
        }
        
        // Check for verification steps in flattened keys
        $hasVerification = false;
        foreach ($flatKeys as $key) {
            if (stripos($key, 'verification') !== false) {
                echo "\nFound verification-related key: $key\n";
                $hasVerification = true;
            }
        }
        
        if (!$hasVerification) {
            echo "\nNo verification-related keys found in flattened keys.\n";
        }
    }
});

// Initialize WordPress REST API
do_action('rest_api_init');