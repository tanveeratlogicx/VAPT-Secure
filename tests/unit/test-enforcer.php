<?php
/**
 * Unit tests for VAPTSECURE_Enforcer class
 *
 * @package VAPT_Secure
 */

use PHPUnit\Framework\TestCase;

/**
 * Class Test_VAPTSECURE_Enforcer
 *
 * Tests for code extraction and enforcer logic.
 */
class Test_VAPTSECURE_Enforcer extends TestCase
{
    /**
     * Test extract_code_from_mapping with empty input
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_empty()
    {
        // Empty string should return empty string
        $this->assertEquals('', VAPTSECURE_Enforcer::extract_code_from_mapping(''));
        
        // Null should return empty string
        $this->assertEquals('', VAPTSECURE_Enforcer::extract_code_from_mapping(null));
        
        // Empty array should return empty string
        $this->assertEquals('', VAPTSECURE_Enforcer::extract_code_from_mapping([]));
    }

    /**
     * Test extract_code_from_mapping with plain string
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_plain_string()
    {
        $code = 'RewriteEngine On';
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($code);
        $this->assertEquals($code, $result);
    }

    /**
     * Test extract_code_from_mapping with platform-specific array
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_platform_specific()
    {
        // Test htaccess platform
        $directive = [
            'htaccess' => 'RewriteEngine On'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        $this->assertEquals('RewriteEngine On', $result);

        // Test nginx platform
        $directive = [
            'nginx' => 'rewrite ^/old /new permanent;'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'nginx');
        $this->assertEquals('rewrite ^/old /new permanent;', $result);

        // Test php platform
        $directive = [
            'php' => '<?php echo "Hello World"; ?>'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'php');
        $this->assertEquals('<?php echo "Hello World"; ?>', $result);
    }

    /**
     * Test extract_code_from_mapping with platform-specific code array
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_platform_code_array()
    {
        // Platform key with code subkey
        $directive = [
            'htaccess' => ['code' => 'RewriteEngine On']
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        $this->assertEquals('RewriteEngine On', $result);

        // Nginx with code subkey
        $directive = [
            'nginx' => ['code' => 'location / { deny all; }']
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'nginx');
        $this->assertEquals('location / { deny all; }', $result);
    }

    /**
     * Test extract_code_from_mapping with fallback to generic code field
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_generic_code_fallback()
    {
        // No platform-specific key, but has generic 'code' field
        $directive = [
            'code' => 'Generic security code'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        $this->assertEquals('Generic security code', $result);
    }

    /**
     * Test extract_code_from_mapping with JSON string input
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_json_string()
    {
        // JSON string with platform-specific code
        $json = json_encode(['htaccess' => 'RewriteEngine On']);
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($json, 'htaccess');
        $this->assertEquals('RewriteEngine On', $result);

        // JSON string with code subkey
        $json = json_encode(['nginx' => ['code' => 'deny all;']]);
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($json, 'nginx');
        $this->assertEquals('deny all;', $result);

        // JSON string without matching platform should fallback
        $json = json_encode(['other' => 'value', 'code' => 'fallback']);
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($json, 'htaccess');
        $this->assertEquals('fallback', $result);
    }

    /**
     * Test extract_code_from_mapping with platform variations
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_platform_variations()
    {
        // Test .htaccess with leading dot
        $directive = [
            '.htaccess' => 'RewriteEngine On'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        $this->assertEquals('RewriteEngine On', $result);

        // Test wp_config variation (underscore vs hyphen)
        $directive = [
            'wp_config' => 'define("DISABLE_WP_CRON", true);'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'wp-config');
        $this->assertEquals('define("DISABLE_WP_CRON", true);', $result);

        $directive = [
            'wp-config' => 'define("FORCE_SSL_ADMIN", true);'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'wp_config');
        $this->assertEquals('define("FORCE_SSL_ADMIN", true);', $result);
    }

    /**
     * Test extract_code_from_mapping with legacy array format
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_legacy_format()
    {
        // Legacy format: first string value without platform key
        $directive = [
            'some_key' => 'Legacy code value'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        $this->assertEquals('Legacy code value', $result);
    }

    /**
     * Test extract_code_from_mapping with whitespace in keys
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_whitespace_keys()
    {
        // Keys with leading/trailing whitespace
        $directive = [
            '  htaccess  ' => 'RewriteEngine On'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        $this->assertEquals('RewriteEngine On', $result);
    }

    /**
     * Test extract_code_from_mapping returns empty for non-matching platform
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_non_matching_platform()
    {
        // Array with no matching platform and no fallback
        $directive = [
            'nginx' => 'nginx specific code'
        ];
        // Requesting htaccess code from nginx-only directive
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        // Should return empty since no fallback 'code' key exists
        $this->assertEquals('', $result);
    }

    /**
     * Test extract_code_from_mapping ignores nested arrays as values
     *
     * @covers VAPTSECURE_Enforcer::extract_code_from_mapping
     */
    public function test_extract_code_from_mapping_ignores_nested_arrays()
    {
        // First non-array value should be returned
        $directive = [
            'nested' => ['array' => 'value'],
            'string' => 'Valid code'
        ];
        $result = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'htaccess');
        // Should skip the nested array and return the first string
        $this->assertEquals('Valid code', $result);
    }
}
