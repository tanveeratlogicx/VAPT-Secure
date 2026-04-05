<?php
/**
 * Unit tests for VAPTSECURE_Build class
 *
 * @package VAPT_Secure
 */

use PHPUnit\Framework\TestCase;

/**
 * Class Test_VAPTSECURE_Build
 *
 * Tests for build generation and config content creation.
 */
class Test_VAPTSECURE_Build extends TestCase
{
    /**
     * Test generate_config_content returns valid PHP config
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_returns_php()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            null,
            'single',
            1,
            false
        );

        // Should start with PHP open tag
        $this->assertStringStartsWith('<?php', $config);

        // Should contain ABSPATH check
        $this->assertStringContainsString("defined( 'ABSPATH' )", $config);
        $this->assertStringContainsString('exit', $config);
    }

    /**
     * Test generate_config_content includes domain
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_includes_domain()
    {
        $domain = 'mywebsite.com';
        $config = VAPTSECURE_Build::generate_config_content(
            $domain,
            '1.0.0',
            [],
            null,
            'single',
            1,
            false
        );

        // Should contain domain constant
        $this->assertStringContainsString('VAPTSECURE_DOMAIN_LOCKED', $config);
        $this->assertStringContainsString($domain, $config);
    }

    /**
     * Test generate_config_content includes version
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_includes_version()
    {
        $version = '2.5.1';
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            $version,
            [],
            null,
            'single',
            1,
            false
        );

        // Should contain version constant
        $this->assertStringContainsString('VAPTSECURE_BUILD_VERSION', $config);
        $this->assertStringContainsString($version, $config);
    }

    /**
     * Test generate_config_content includes license scope
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_includes_license_scope()
    {
        // Test single license
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            null,
            'single',
            1,
            false
        );
        $this->assertStringContainsString("VAPTSECURE_LICENSE_SCOPE', 'single'", $config);

        // Test multi license
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            null,
            'multi',
            5,
            false
        );
        $this->assertStringContainsString("VAPTSECURE_LICENSE_SCOPE', 'multi'", $config);
    }

    /**
     * Test generate_config_content includes domain limit
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_includes_domain_limit()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            null,
            'multi',
            10,
            false
        );

        // Should contain domain limit as integer
        $this->assertStringContainsString('VAPTSECURE_DOMAIN_LIMIT', $config);
        $this->assertStringContainsString('10', $config);
    }

    /**
     * Test generate_config_content with active data file
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_with_active_data_file()
    {
        $data_file = 'Feature-List-42.json';
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            $data_file,
            'single',
            1,
            false
        );

        // Should contain active data file constant
        $this->assertStringContainsString('VAPTSECURE_ACTIVE_DATA_FILE', $config);
        $this->assertStringContainsString($data_file, $config);
    }

    /**
     * Test generate_config_content without active data file
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_without_active_data_file()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            null,  // No active data file
            'single',
            1,
            false
        );

        // Should NOT contain active data file constant
        $this->assertStringNotContainsString('VAPTSECURE_ACTIVE_DATA_FILE', $config);
    }

    /**
     * Test generate_config_content with restricted features
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_with_restricted_features()
    {
        $features = ['security-headers', 'xss-protection', 'sql-injection-guard'];
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            $features,
            null,
            'single',
            1,
            true  // Restrict features
        );

        // Should contain restrict features constant
        $this->assertStringContainsString('VAPTSECURE_RESTRICT_FEATURES', $config);
        $this->assertStringContainsString('true', $config);

        // Should contain feature definitions
        foreach ($features as $feature) {
            $constant_name = 'VAPTSECURE_FEATURE_' . strtoupper(str_replace('-', '_', $feature));
            $this->assertStringContainsString($constant_name, $config);
            $this->assertStringContainsString("define( '$constant_name', true )", $config);
        }
    }

    /**
     * Test generate_config_content with open mode (no feature restrictions)
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_open_mode()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            ['feature-1', 'feature-2'],
            null,
            'single',
            1,
            false  // Open mode
        );

        // Should NOT contain restrict features constant
        $this->assertStringNotContainsString('VAPTSECURE_RESTRICT_FEATURES', $config);

        // Should indicate open mode
        $this->assertStringContainsString('Open Mode', $config);
    }

    /**
     * Test generate_config_content escapes special characters in domain
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_escapes_domain()
    {
        $domain = "example'com"; // Contains single quote
        $config = VAPTSECURE_Build::generate_config_content(
            $domain,
            '1.0.0',
            [],
            null,
            'single',
            1,
            false
        );

        // Domain should be escaped in the output
        $this->assertStringContainsString('VAPTSECURE_DOMAIN_LOCKED', $config);
    }

    /**
     * Test generate_config_content includes security alert email
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_includes_alert_email()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],
            null,
            'single',
            1,
            false
        );

        // Should contain security alert email constant
        $this->assertStringContainsString('VAPTSECURE_SECURITY_ALERT_EMAIL', $config);
        $this->assertStringContainsString('base64_decode', $config);
    }

    /**
     * Test generate_config_content format and comments
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_has_proper_comments()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.5.0',
            [],
            null,
            'single',
            1,
            false
        );

        // Should have file header comment
        $this->assertStringContainsString('VAPT Secure Configuration', $config);
        $this->assertStringContainsString('Build Version:', $config);

        // Should have section comment for domain & licensing
        $this->assertStringContainsString('Domain Locking & Licensing', $config);
    }

    /**
     * Test generate_config_content with empty features array
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_empty_features()
    {
        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            [],  // Empty features
            null,
            'single',
            1,
            true  // But restricted mode
        );

        // Should still work with empty features
        $this->assertStringContainsString('VAPTSECURE_RESTRICT_FEATURES', $config);
        // Should not have any feature constants since array is empty
        $this->assertStringNotContainsString('VAPTSECURE_FEATURE_', $config);
    }

    /**
     * Test generate_config_content feature key transformation
     *
     * @covers VAPTSECURE_Build::generate_config_content
     */
    public function test_generate_config_content_feature_key_transformation()
    {
        $features = [
            'simple-feature',
            'multi-word-feature-name',
            'UPPERCASE-FEATURE'
        ];

        $config = VAPTSECURE_Build::generate_config_content(
            'example.com',
            '1.0.0',
            $features,
            null,
            'single',
            1,
            true
        );

        // Feature keys should be transformed: hyphens to underscores, uppercase
        $this->assertStringContainsString('VAPTSECURE_FEATURE_SIMPLE_FEATURE', $config);
        $this->assertStringContainsString('VAPTSECURE_FEATURE_MULTI_WORD_FEATURE_NAME', $config);
        $this->assertStringContainsString('VAPTSECURE_FEATURE_UPPERCASE_FEATURE', $config);
    }
}
