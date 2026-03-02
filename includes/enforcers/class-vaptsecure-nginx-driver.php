<?php

/**
 * VAPTSECURE_Nginx_Driver
 * Handles enforcement of rules for Nginx via a generated include file.
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Nginx_Driver
{
  /**
   * Generates a list of valid Nginx directives based on the provided data and schema.
   *
   * @param array $data Implementation data (user inputs)
   * @param array $schema Feature schema containing enforcement mappings
   * @return array List of valid Nginx directives
   */
  public static function generate_rules($data, $schema)
  {
    // 🛡️ TWO-WAY DEACTIVATION (v3.6.19)
    $is_enabled = isset($data['feat_enabled']) ? (bool)$data['feat_enabled'] : (isset($data['enabled']) ? (bool)$data['enabled'] : true);
    if (!$is_enabled) {
      return array();
    }

    $enf_config = isset($schema['enforcement']) ? $schema['enforcement'] : array();
    $rules = array();
    $mappings = isset($enf_config['mappings']) ? $enf_config['mappings'] : array();

    foreach ($mappings as $key => $directive) {
      if (!empty($data[$key])) {
        // [v1.4.1] Support for v1.1/v2.0 rich mappings (Platform Objects)
        $directive = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'nginx');
        if (empty($directive)) continue;

        $nginx_rule = self::translate_to_nginx($key, $directive);

        if ($nginx_rule) {
          $rules[] = $nginx_rule;
        }
      }
    }

    if (!empty($rules)) {
      $feature_key = isset($schema['feature_key']) ? $schema['feature_key'] : 'unknown';
      $title = isset($schema['title']) ? $schema['title'] : '';

      $wrapped_rules = array();
      $wrapped_rules[] = "# BEGIN VAPT $feature_key" . ($title ? " — $title" : "");

      // 🛡️ COMPLIANCE MARKER (v4.2.3)
      // Ensure X-VAPT-Enforced is set for every feature to survive Nginx proxy stripping
      $wrapped_rules[] = "add_header X-VAPT-Enforced \"nginx\" always;";
      $wrapped_rules[] = "add_header X-VAPT-Feature \"$feature_key\" always;";

      foreach ($rules as $rule) {
        $wrapped_rules[] = $rule;
      }
      $wrapped_rules[] = "# END VAPT $feature_key";

      return $wrapped_rules;
    }

    return array(); // Return empty array if no rules were generated
  }

  /**
   * 🔍 VERIFICATION LOGIC (v3.6.19)
   */
  public static function verify($key, $impl_data, $schema)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/vapt-nginx-rules.conf';

    if (!file_exists($file_path)) {
      return false;
    }

    $content = file_get_contents($file_path);
    return (strpos($content, "X-VAPT-Feature \"$key\"") !== false);
  }

  /**
   * Translates common VAPT keys/Apache directives to Nginx syntax.
   */
  private static function translate_to_nginx($key, $directive)
  {
    // 1. Headers (Support both 'set' and 'always set' from Apache)
    // Apache: Header always set X-Frame-Options "SAMEORIGIN"
    // Nginx: add_header X-Frame-Options "SAMEORIGIN" always;
    if (strpos($directive, 'Header') !== false && strpos($directive, 'set') !== false) {
      $clean = str_replace(['Header always set ', 'Header set ', '"'], ['', '', ''], $directive);
      $parts = explode(' ', trim($clean), 2);
      if (count($parts) == 2) {
        return 'add_header ' . $parts[0] . ' "' . trim($parts[1]) . '" always;';
      }
    }

    // 2. Directory Listing
    // Apache: Options -Indexes
    if (strpos($directive, 'Options -Indexes') !== false || $key === 'disable_directory_browsing') {
      return "autoindex off;";
    }

    // 3. Block Files (xmlrpc, etc)
    if ($key === 'block_xmlrpc' || strpos($directive, 'xmlrpc.php') !== false) {
      return "location = /xmlrpc.php { deny all; return 403; }";
    }

    // 4. Block Dot Files / Sensitive Files
    if ($key === 'block_sensitive_files' || $key === 'block_dot_files') {
      return "location ~ /\\.(?!well-known).* { deny all; return 403; }";
    }

    // 5. Generic File Blocking (regex translation)
    if (strpos($directive, '<FilesMatch') !== false) {
      if (preg_match('/\"(.*?)\"/', $directive, $m)) {
        $regex = $m[1];
        return "location ~ {$regex} { deny all; return 403; }";
      }
    }

    // 6. Security Header Fallback (v4.2.3)
    if ($key === 'hsts_header') return "add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\" always;";
    if ($key === 'xss_protection') return "add_header X-XSS-Protection \"1; mode=block\" always;";
    if ($key === 'content_type_nosniff') return "add_header X-Content-Type-Options \"nosniff\" always;";
    if ($key === 'referrer_policy') return "add_header Referrer-Policy \"strict-origin-when-cross-origin\" always;";

    return null;
  }

  /**
   * Writes a complete batch of rules to wp-content/uploads/vapt-nginx-rules.conf
   */
  public static function write_batch($all_rules_array)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/vapt-nginx-rules.conf';

    $content = "# VAPT Secure - Auto Generated Nginx Rules\n";
    $content .= "# Include this file in your nginx.conf server block.\n";
    $content .= "# Last Updated: " . date('Y-m-d H:i:s') . "\n\n";

    $content .= implode("\n", $all_rules_array);

    $result = @file_put_contents($file_path, $content);

    if ($result !== false) {
      // Set a persistent option to verify file matches current state?
      // Or just transient for admin notice?
      set_transient('vaptsecure_nginx_rules_updated', $file_path, HOUR_IN_SECONDS * 24);
      return true;
    }

    return false;
  }
}
