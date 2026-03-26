<?php
/**
 * Test fixed regex patterns for superadmin removal
 */

$content = file_get_contents(__DIR__ . '/vaptsecure.php');

echo "Testing fixed regex patterns...\n\n";

// Better pattern for vaptsecure_get_superadmin_identity
// Match from the specific docblock to the end of function
$pattern1_fixed = '/(\/\*\*[\s\S]*?Obfuscated Superadmin Identity[\s\S]*?\*\/)\s*function vaptsecure_get_superadmin_identity\s*\([^)]*\)\s*\{[^}]*\}/s';

// Better pattern for constants - match exactly from comment to the second define
$pattern2_fixed = '/\/\/ Set Superadmin Constants\s*\n\$vaptsecure_identity = vaptsecure_get_superadmin_identity\(\);\s*\nif \(! defined\(\'VAPTSECURE_SUPERADMIN_USER\'\)\) \{[^}]+\}\s*\nif \(! defined\(\'VAPTSECURE_SUPERADMIN_EMAIL\'\)\) \{[^}]+\}/s';

// Better pattern for is_vaptsecure_superadmin
$pattern3_fixed = '/(\/\*\*[\s\S]*?Strict Superadmin Check[\s\S]*?\*\/)\s*function is_vaptsecure_superadmin\s*\([^)]*\)\s*\{[^}]*\}/s';

// Test pattern 1
echo "1. Testing fixed pattern for vaptsecure_get_superadmin_identity:\n";
if (preg_match($pattern1_fixed, $content, $matches)) {
    echo "   ✓ Matches! Length: " . strlen($matches[0]) . "\n";
    echo "   First 150 chars: " . substr($matches[0], 0, 150) . "...\n";
    
    // Test removal
    $test_content = preg_replace($pattern1_fixed, '', $content);
    if (strpos($test_content, 'vaptsecure_get_superadmin_identity') === false) {
        echo "   ✓ Successfully removed\n";
    } else {
        echo "   ✗ Still present after removal\n";
    }
} else {
    echo "   ✗ No match with fixed pattern\n";
}
echo "\n";

// Test pattern 2
echo "2. Testing fixed pattern for constants:\n";
if (preg_match($pattern2_fixed, $content, $matches)) {
    echo "   ✓ Matches! Length: " . strlen($matches[0]) . "\n";
    echo "   Match: " . $matches[0] . "\n";
    
    // Test removal
    $test_content = preg_replace($pattern2_fixed, '', $content);
    if (strpos($test_content, 'VAPTSECURE_SUPERADMIN_USER') === false) {
        echo "   ✓ Successfully removed\n";
    } else {
        echo "   ✗ Still present after removal\n";
    }
} else {
    echo "   ✗ No match with fixed pattern\n";
}
echo "\n";

// Test pattern 3
echo "3. Testing fixed pattern for is_vaptsecure_superadmin:\n";
if (preg_match($pattern3_fixed, $content, $matches)) {
    echo "   ✓ Matches! Length: " . strlen($matches[0]) . "\n";
    echo "   First 150 chars: " . substr($matches[0], 0, 150) . "...\n";
    
    // Test removal
    $test_content = preg_replace($pattern3_fixed, '', $content);
    if (strpos($test_content, 'is_vaptsecure_superadmin') === false) {
        echo "   ✓ Successfully removed\n";
    } else {
        echo "   ✗ Still present after removal\n";
    }
} else {
    echo "   ✗ No match with fixed pattern\n";
}
echo "\n";

// Test the menu logic pattern
echo "4. Testing menu logic pattern (current):\n";
$pattern4 = '/\$is_superadmin_identity = is_vaptsecure_superadmin\(false\);\s*\/\/ 1\. Parent Menu[\s\S]*?remove_submenu_page\(\'vaptsecure\', \'vaptsecure\'\);/';
if (preg_match($pattern4, $content, $matches)) {
    echo "   ✓ Matches! Length: " . strlen($matches[0]) . "\n";
    echo "   First 200 chars: " . substr($matches[0], 0, 200) . "...\n";
} else {
    echo "   ✗ No match\n";
}
echo "\n";

// Test page rendering functions
echo "5. Testing page rendering functions:\n";
// Find vaptsecure_render_workbench_page
$pos1 = strpos($content, 'function vaptsecure_render_workbench_page');
if ($pos1 !== false) {
    echo "   Found vaptsecure_render_workbench_page at position $pos1\n";
    
    // Try to match the function
    $pattern5 = '/function vaptsecure_render_workbench_page\s*\([^)]*\)\s*\{[^}]*\}/s';
    if (preg_match($pattern5, substr($content, $pos1 - 100), $matches)) {
        echo "   ✓ Matched function, length: " . strlen($matches[0]) . "\n";
    }
} else {
    echo "   ✗ Function not found\n";
}