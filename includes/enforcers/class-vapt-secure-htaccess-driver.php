<?php

/**
 * VAPT_SECURE_Htaccess_Driver
 * Handles enforcement of rules into .htaccess
 */

if (!defined('ABSPATH')) exit;

class VAPT_SECURE_Htaccess_Driver
{
  /**
   * Whitelist of allowed .htaccess directives for security
   * Prevents injection of dangerous PHP/Server directives
   */
  private static $allowed_directives = [
    'Options',
    'Header',
    'Files',
    'FilesMatch',
    'IfModule',
    'Order',
    'Deny',
    'Allow',
    'Directory',
    'DirectoryMatch',
    'Require'
  ];

  /**
   * Dangerous patterns that should never be allowed
   */
  private static $dangerous_patterns = [
    '/php_value/i',
    '/php_admin_value/i',
    '/SetEnvIf.*passthrough/i',
    '/RewriteRule.*passthrough/i',
    '/RewriteRule.*exec/i',
    '/<FilesMatch.*\.php/i',
    '/php_flag\s/i',
    '/AddHandler.*php/i',
    '/Action\s/i',
    '/SetHandler\s/i'
  ];

  /**
   * Generates a list of valid .htaccess rules based on the provided data and schema.
   * Does NOT write to file.
   *
   * @param array $data Implementation data (user inputs)
   * @param array $schema Feature schema containing enforcement mappings
   * @return array List of valid .htaccess directives
   */
  public static function generate_rules($data, $schema)
  {
    // 🛡️ TWO-WAY DEACTIVATION (v3.12.3 - Intelligent Detection)
    $is_enabled = true;
    if (isset($data['enabled'])) {
      $is_enabled = (bool)$data['enabled'];
    } else {
      // If 'enabled' is missing, check if any mapped toggle is set to false
      $mappings = $enf_config['mappings'] ?? array();
      foreach ($mappings as $key => $directive) {
        if (isset($data[$key]) && ($data[$key] === false || $data[$key] === 0 || $data[$key] === '0')) {
          // If the primary enforcement mapping is a toggle and it's OFF, consider feature disabled
          $is_enabled = false;
          break;
        }
      }
    }

    if (!$is_enabled) {
      return array(); // Return empty set if disabled
    }

    $enf_config = isset($schema['enforcement']) ? $schema['enforcement'] : array();
    $rules = array();
    $mappings = isset($enf_config['mappings']) ? $enf_config['mappings'] : array();



    // 1. Iterate mappings and bind data
    // [v3.12.14] Case-Insensitive Key Match to handle sanitized (lowercase) control keys
    $data_keys = array_keys($data);
    $data_keys_lower = array_map('strtolower', $data_keys);
    $data_map = array_combine($data_keys_lower, $data);

    foreach ($mappings as $key => $directive) {
      $key_lower = strtolower($key);
      if (!empty($data_map[$key_lower])) {
        // [ENHANCEMENT] Variable Substitution (v3.12.0)
        $directive = self::substitute_variables($directive);

        // [v3.12.4] Fix literal \n escaping
        $directive = str_replace('\n', "\n", $directive);

        // [v3.12.7] Strip VAPTBuilder RISK-XXX comments
        $directive = preg_replace('/^#\s*VAPTBuilder\s+RISK-\d+:.*$/m', '', $directive);
        $directive = trim($directive);

        $processed_directive = self::prepare_directive($directive);
        $validation = self::validate_htaccess_directive($processed_directive);

        if ($validation['valid']) {
          $rules[] = $processed_directive;
        } else {
          error_log(sprintf(
            'VAPT: Invalid .htaccess directive rejected for feature %s (key: %s). Reason: %s',
            $schema['feature_key'] ?? 'unknown',
            $key,
            $validation['reason']
          ));
        }
      }
    }

    // 2. Wrap collected rules in a marker comment for verification (v3.12.10 - Anonymous)
    if (!empty($rules)) {
      $feature_key = isset($schema['feature_key']) ? $schema['feature_key'] : 'unknown';
      $hash = substr(md5($feature_key), 0, 8); // Short hash for anonymity

      $header_block = "<IfModule mod_headers.c>\n  Header set X-VAPT-Enforced \"htaccess\"\n</IfModule>";
      $id_marker = "# VAPTID-$hash";

      // [FIX v3.12.14] Join marker and header with single \n to avoid blank line after marker
      $combined_header = $id_marker . "\n" . $header_block;

      // Prepend combined header
      $rules = array_merge(
        [$combined_header],
        $rules
      );
    }

    return $rules;
  }

  /**
   * 🔍 VERIFICATION LOGIC (v3.12.6 - Enhanced Debug)
   * Physically checks the .htaccess file for the feature marker.
   */
  public static function verify($key, $impl_data, $schema)
  {
    $target_key = $schema['enforcement']['target'] ?? 'root';
    $htaccess_path = ABSPATH . '.htaccess';
    if ($target_key === 'uploads') {
      $upload_dir = wp_upload_dir();
      $htaccess_path = $upload_dir['basedir'] . '/.htaccess';
    }

    error_log("VAPT VERIFY: Checking for feature '$key' in $htaccess_path");

    if (!file_exists($htaccess_path)) {
      error_log("VAPT VERIFY: File does not exist: $htaccess_path");
      return false;
    }

    $content = file_get_contents($htaccess_path);
    $hash = substr(md5($key), 0, 8);
    $search_string = "VAPTID-$hash";
    $found = (strpos($content, $search_string) !== false);

    error_log("VAPT VERIFY: Looking for anonymous ID '$search_string' - " . ($found ? 'FOUND' : 'NOT FOUND'));

    // Look for the specific feature hash within our VAPT block
    return $found;
  }

  /**
   * Writes a complete batch of rules to the .htaccess file, replacing the previous VAPT block.
   *
   * @param array $all_rules_array Flat array of all .htaccess rules to write
   * @param string $target_key 'root' or 'uploads'
   * @return bool Success status
   */
  public static function write_batch($all_rules_array, $target_key = 'root')
  {
    $log = "[Htaccess Batch Write " . date('Y-m-d H:i:s') . "] Writing " . count($all_rules_array) . " rules.\n";

    $htaccess_path = ABSPATH . '.htaccess';
    if ($target_key === 'uploads') {
      $upload_dir = wp_upload_dir();
      $htaccess_path = $upload_dir['basedir'] . '/.htaccess';
    }

    // Ensure directory exists
    $dir = dirname($htaccess_path);
    if (!is_dir($dir)) {
      wp_mkdir_p($dir);
    }

    // Read existing content
    $content = "";
    if (file_exists($htaccess_path)) {
      $content = file_get_contents($htaccess_path);
    }

    // Prepare new VAPT block
    $start_marker = "# BEGIN VAPT SECURITY RULES";
    $end_marker = "# END VAPT SECURITY RULES";
    $rules_string = "";

    if (!empty($all_rules_array)) {
      $has_rewrite = false;
      foreach ($all_rules_array as $rule) {
        if (stripos($rule, 'RewriteCond') !== false || stripos($rule, 'RewriteRule') !== false || stripos($rule, 'RewriteEngine') !== false) {
          $has_rewrite = true;
          break;
        }
      }

      $header = $start_marker . "\n";
      // [FIX v3.12.14] Wrap global RewriteEngine in IfModule for safety
      if ($has_rewrite) {
        $header .= "<IfModule mod_rewrite.c>\n    RewriteEngine On\n</IfModule>\n";
      }
      // [FIX v3.12.14] Ensure blank line after block header
      $header .= "\n";

      // [v3.12.16] Consolidate rules before writing
      $consolidated_rules = self::consolidate_rules($all_rules_array);

      $rules_string = "\n" . $header . implode("\n\n", $consolidated_rules) . "\n" . $end_marker . "\n";
    }

    // Replace or Append
    // 1. Remove old block if exists (supporting both old/new markers)
    $pattern = "/# BEGIN VAPT SECURITY RULES.*?# END VAPT SECURITY RULES/s";
    $old_pattern = "/# BEGIN VAPTC SECURITY RULES.*?# END VAPTC SECURITY RULES/s";

    $new_content = $content;

    if (preg_match($pattern, $content)) {
      $new_content = preg_replace($pattern, trim($rules_string), $content);
    } else if (preg_match($old_pattern, $content)) {
      $new_content = preg_replace($old_pattern, trim($rules_string), $content);
    } else {
      // Append if not found
      // [FIX v3.12.23] Place VAPT Rules BEFORE WordPress Core Block
      // This is CRITICAL because WP's RewriteRule [L] stops processing for virtual paths.
      // If we are after WP, our rules are never reached for REST API endpoints.
      if ($target_key === 'root') {
        if (strpos($content, "# BEGIN WordPress") !== false) {
          // Insert BEFORE the WordPress block
          $parts = explode("# BEGIN WordPress", $content);
          // Ensure we don't double up newlines unnecessarily but keep readable
          $new_content = $parts[0] . $rules_string . "\n# BEGIN WordPress" . (isset($parts[1]) ? $parts[1] : "");
        } else {
          // If no WP block found (rare), prepend to start
          $new_content = $rules_string . "\n" . $content;
        }
      } else {
        // Uploads directory or other target: Prepend is safest for blocking
        $new_content = $rules_string . "\n" . $content;
      }
    }

    // Clean up empty lines? 
    // Just ensure we don't end up with huge gaps.

    // Write
    if ($new_content !== $content || !file_exists($htaccess_path)) {
      // Safety Backup
      if (file_exists($htaccess_path)) {
        @copy($htaccess_path, $htaccess_path . '.bak');
      }

      $result = @file_put_contents($htaccess_path, trim($new_content) . "\n");
      if ($result !== false) {
        $log .= "Write SUCCESS: " . strlen($new_content) . " bytes written to $htaccess_path. Backup created.\n";
        delete_transient('vapt_secure_active_enforcements');
      } else {
        $log .= "Write FAILURE: Could not write to $htaccess_path. Check file permissions.\n";
        error_log("VAPT: Failed to write .htaccess to $htaccess_path.");
        set_transient('vapt_secure_htaccess_write_error_' . time(), "Failed to update .htaccess file. check perms.", 300);
        return false;
      }
    } else {
      $log .= "No changes detected. Write skipped.\n";
    }

    // Persistent Log
    $debug_file = WP_CONTENT_DIR . '/vapt-htaccess-debug.txt';
    @file_put_contents($debug_file, $log, FILE_APPEND);

    return true;
  }

  /**
   * Legacy method for single-feature enforcement.
   * Now proxies to generate + write, BUT logic warns this is partial.
   * Kept for signature compatibility.
   */
  public static function enforce($data, $schema)
  {
    // Note: Direct calling of this will overwrite the file with ONLY this feature's rules.
    // This should only be used if we are sure we want that, or during testing.
    //Ideally, we should trigger a full rebuild from Enforcer instead.
    $rules = self::generate_rules($data, $schema);
    self::write_batch($rules, isset($schema['enforcement']['target']) ? $schema['enforcement']['target'] : 'root');
  }

  /**
   * Automatically wraps directives in <IfModule> if they are not already wrapped.
   * This is a safety measure to prevent server crashes if an Apache module is missing.
   * [v3.12.6] Enhanced formatting with proper indentation and spacing
   */
  /**
   * Automatically wraps directives in <IfModule> if they are not already wrapped.
   * [v3.12.16] Enhanced formatting with 4-space indentation and expanded wrappers.
   */
  private static function prepare_directive($directive)
  {
    $directive = trim($directive);
    if (empty($directive)) return $directive;

    // [FIX v3.12.13] Only strip IfModule if it's a single, simple block.
    if (stripos($directive, '<IfModule') === 0 && substr_count(strtolower($directive), '<ifmodule') === 1) {
      if (preg_match('/^<IfModule.*?>\s*(.*?)\s*<\/IfModule>$/is', $directive, $matches)) {
        $directive = trim($matches[1]);
      }
    }

    // Wrap mod_headers directives
    if (stripos($directive, 'Header ') === 0) {
      return "<IfModule mod_headers.c>\n    $directive\n</IfModule>";
    }

    // Wrap mod_rewrite directives
    if (stripos($directive, 'RewriteEngine') === 0 || stripos($directive, 'RewriteCond') === 0 || stripos($directive, 'RewriteRule') === 0) {
      $lines = explode("\n", $directive);
      $formatted_lines = [];
      foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        $formatted_lines[] = "    " . $trimmed;
      }
      return "<IfModule mod_rewrite.c>\n" . implode("\n", $formatted_lines) . "\n</IfModule>";
    }

    // Wrap access control (mod_access_compat or mod_authz_core)
    if (stripos($directive, 'Order ') === 0 || stripos($directive, 'Deny ') === 0 || stripos($directive, 'Allow ') === 0 || stripos($directive, 'Require ') === 0) {
      // We'll wrap in a generic way or let consolidation handle it.
      // For now, let's wrap in mod_authz_core for modern Apache, or just mod_version if we wanted to be fancy.
      // Pragmattically, just wrapping in mod_authz_core for Require, or mod_access_compat for Order/Deny.
      if (stripos($directive, 'Require ') === 0) {
        return "<IfModule mod_authz_core.c>\n    $directive\n</IfModule>";
      } else {
        return "<IfModule mod_access_compat.c>\n    $directive\n</IfModule>";
      }
    }

    return $directive;
  }

  /**
   * [v3.12.16] Consolidates adjacent or similar IfModule blocks and deduplicates headers.
   */
  private static function consolidate_rules($rules)
  {
    $modules = [];
    $others = [];
    $headers = [];
    $vapt_secure_ids = [];

    foreach ($rules as $rule) {
      // Extract VAPTID if present at the start of rule (v3.12.14 formatting)
      if (preg_match('/^# VAPTID-([a-f0-9]+)/i', $rule, $id_match)) {
        if (!in_array($id_match[0], $vapt_secure_ids)) {
          $vapt_secure_ids[] = $id_match[0];
        }
        // Remove the VAPTID line from the rule for further processing
        $rule = trim(preg_replace('/^# VAPTID-[a-f0-9]+/i', '', $rule));
      }

      if (preg_match('/^<IfModule\s+([^>]+)>\s*(.*?)\s*<\/IfModule>$/is', $rule, $matches)) {
        $module = trim($matches[1]);
        $content = trim($matches[2]);

        if ($module === 'mod_headers.c') {
          $header_lines = explode("\n", $content);
          foreach ($header_lines as $h_line) {
            $h_line = trim($h_line);
            if (empty($h_line)) continue;
            // Deduplicate headers
            if (!in_array($h_line, $headers)) {
              $headers[] = $h_line;
            }
          }
        } else {
          if (!isset($modules[$module])) $modules[$module] = [];
          $modules[$module][] = $content;
        }
      } elseif (!empty($rule)) {
        $others[] = $rule;
      }
    }

    $final_rules = [];

    // 1. Add VAPTIDs at the top (joined to next element with only one \n)
    $ids_and_first = "";
    if (!empty($vapt_secure_ids)) {
      $ids_and_first = implode("\n", $vapt_secure_ids) . "\n";
    }

    // 2. Add mod_headers
    if (!empty($headers)) {
      $h_block = "<IfModule mod_headers.c>\n";
      foreach ($headers as $h) {
        $h_block .= "    " . $h . "\n";
      }
      $h_block .= "</IfModule>";

      if ($ids_and_first !== "") {
        $final_rules[] = $ids_and_first . $h_block;
        $ids_and_first = "";
      } else {
        $final_rules[] = $h_block;
      }
    }

    // 3. Add other modules
    foreach ($modules as $mod => $contents) {
      $m_block = "<IfModule $mod>\n";
      foreach ($contents as $c) {
        $lines = explode("\n", $c);
        foreach ($lines as $l) {
          $l = trim($l);
          if (empty($l)) continue;
          $m_block .= "    " . $l . "\n";
        }
        $m_block .= "\n"; // Feature gap
      }
      $m_block = trim($m_block) . "\n</IfModule>";

      if ($ids_and_first !== "") {
        $final_rules[] = $ids_and_first . $m_block;
        $ids_and_first = "";
      } else {
        $final_rules[] = $m_block;
      }
    }

    // If still have IDs (unlikely if rules existed), add them
    if ($ids_and_first !== "") {
      $final_rules[] = trim($ids_and_first);
    }

    return array_merge($final_rules, trim(implode("\n", $others)) ? $others : []);

    return array_merge($final_rules, trim(implode("\n", $others)) ? $others : []);
  }

  /**
   * Substitutes template variables like {{site_url}} with actual values.
   */
  private static function substitute_variables($directive)
  {
    $site_url = get_site_url();
    $home_url = get_home_url();
    $admin_url = get_admin_url();

    $replacements = [
      '{{site_url}}' => $site_url,
      '{{home_url}}' => $home_url,
      '{{admin_url}}' => $admin_url,
      '{{domain}}'   => parse_url($site_url, PHP_URL_HOST),
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $directive);
  }

  private static function validate_htaccess_directive($directive)
  {
    if (empty($directive) || !is_string($directive)) {
      return ['valid' => false, 'reason' => 'Directive must be a non-empty string'];
    }

    foreach (self::$dangerous_patterns as $pattern) {
      if (preg_match($pattern, $directive)) {
        return [
          'valid' => false,
          'reason' => sprintf('Contains dangerous pattern: %s', $pattern)
        ];
      }
    }

    // [FIX] Refine PHP detection to allow PHP filenames in legitimate tags (v3.12.13)
    if (preg_match('/<\?php|<\?=|<script\s+language=["\']php["\']/i', $directive)) {
      return ['valid' => false, 'reason' => 'Contains PHP-related tags'];
    }

    if (preg_match('/[<>{}]/', $directive) && !preg_match('/<(?:IfModule|Files|Directory|FilesMatch|DirectoryMatch)/i', $directive)) {
      return ['valid' => false, 'reason' => 'Contains unescaped special characters'];
    }

    if (strlen($directive) > 4096) {
      return ['valid' => false, 'reason' => 'Directive exceeds maximum length (4096 characters)'];
    }

    return ['valid' => true, 'reason' => ''];
  }
}
