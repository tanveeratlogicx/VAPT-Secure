<?php

/**
 * VAPTSECURE_IIS_Driver
 * Handles enforcement of rules for IIS via web.config XML injection.
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_IIS_Driver
{
  /**
   * Generates a list of valid IIS XML nodes based on the provided data and schema.
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
        $directive = VAPTSECURE_Enforcer::extract_code_from_mapping($directive, 'iis');
        if (empty($directive)) continue;

        $iis_rule = self::translate_to_iis($key, $directive);
        if ($iis_rule) {
          $rules[] = $iis_rule;
        }
      }
    }

    if (!empty($rules)) {
      $feature_key = isset($schema['feature_key']) ? $schema['feature_key'] : 'unknown';
      $title = isset($schema['title']) ? $schema['title'] : '';

      $wrapped_rules = array();
      $wrapped_rules[] = "<!-- BEGIN VAPT $feature_key" . ($title ? ": $title" : "") . " -->";
      $wrapped_rules = array_merge($wrapped_rules, $rules);
      $wrapped_rules[] = "<!-- VAPT-Feature: $feature_key -->"; // Marker for verify
      $wrapped_rules[] = "<!-- END VAPT $feature_key -->";

      return $wrapped_rules;
    }

    return array();
  }

  /**
   * 🔍 VERIFICATION LOGIC (v3.6.19)
   */
  public static function verify($key, $impl_data, $schema)
  {
    $config_path = ABSPATH . 'web.config';
    if (!file_exists($config_path)) {
      return false;
    }

    $content = file_get_contents($config_path);
    return (strpos($content, "VAPT-Feature: $key") !== false);
  }

  /**
   * Translates Apache-style rules to IIS XML fragments.
   */
  private static function translate_to_iis($key, $directive)
  {
    // 1. Headers -> <customHeaders>
    if (strpos($directive, 'Header') !== false && strpos($directive, 'set') !== false) {
      $clean = str_replace(['Header always set ', 'Header set ', '"'], ['', '', ''], $directive);
      $parts = explode(' ', trim($clean), 2);
      if (count($parts) == 2) {
        return '<add name="' . $parts[0] . '" value="' . trim($parts[1]) . '" />';
      }
    }

    // 2. Directory Browsing -> <directoryBrowse enabled="false" />
    if (strpos($directive, 'Options -Indexes') !== false || $key === 'disable_directory_browsing') {
      return '<directoryBrowse enabled="false" />';
    }

    // 3. Block XMLRPC -> <requestFiltering><hiddenSegments>...
    if ($key === 'block_xmlrpc' || strpos($directive, 'xmlrpc.php') !== false) {
      return '<add segment="xmlrpc.php" />';
    }

    // 4. Block Sensitive Files
    if ($key === 'block_sensitive_files') {
      return '<add segment="web.config" /><add segment="wp-config.php" />';
    }

    return null;
  }

  /**
   * Writes batch to web.config using marker-based replacement.
   * [v4.2.3] Robust XML Block Insertion.
   */
  public static function write_batch($all_rules_array)
  {
    $config_path = ABSPATH . 'web.config';

    // Ensure directory exists
    $dir = dirname($config_path);
    if (!is_dir($dir)) {
      wp_mkdir_p($dir);
    }

    $content = "";
    if (file_exists($config_path)) {
      $content = file_get_contents($config_path);
    } else {
      // Initial skeleton if missing
      $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n    <system.webServer>\n    </system.webServer>\n</configuration>";
    }

    $start_marker = "<!-- BEGIN VAPT SECURITY RULES -->";
    $end_marker = "<!-- END VAPT SECURITY RULES -->";

    // Prepare headers and nodes
    $header_nodes = [];
    $segment_nodes = [];
    $misc_nodes = [];

    // 🛡️ COMPLIANCE MARKER (v4.2.3)
    $header_nodes[] = '<add name="X-VAPT-Enforced" value="iis" />';

    foreach ($all_rules_array as $rule) {
      if (strpos($rule, '<add name=') !== false) {
        $header_nodes[] = $rule;
      } elseif (strpos($rule, '<add segment=') !== false) {
        $segment_nodes[] = $rule;
      } elseif (!empty($rule)) {
        $misc_nodes[] = $rule;
      }
    }

    // Construct the VAPT XML block
    $vapt_block = "\n        " . $start_marker . "\n";

    if (!empty($header_nodes)) {
      $vapt_block .= "        <httpProtocol>\n            <customHeaders>\n                " . implode("\n                ", array_unique($header_nodes)) . "\n            </customHeaders>\n        </httpProtocol>\n";
    }

    if (!empty($segment_nodes)) {
      $vapt_block .= "        <security>\n            <requestFiltering>\n                <hiddenSegments>\n                    " . implode("\n                    ", array_unique($segment_nodes)) . "\n                </hiddenSegments>\n            </requestFiltering>\n        </security>\n";
    }

    foreach (array_unique($misc_nodes) as $node) {
      if (strpos($node, '<!--') === 0) continue; // Skip comments in main flow
      $vapt_block .= "        " . $node . "\n";
    }

    $vapt_block .= "        " . $end_marker . "\n";

    // Strip old block
    $start_pos = strpos($content, $start_marker);
    $end_pos = strpos($content, $end_marker);

    if ($start_pos !== false && $end_pos !== false && $end_pos > $start_pos) {
      $before = substr($content, 0, $start_pos);
      $after = substr($content, $end_pos + strlen($end_marker));
      $content = trim($before) . "\n" . trim($after);
    }

    // Insert into <system.webServer>
    if (strpos($content, '<system.webServer>') !== false) {
      $parts = explode('<system.webServer>', $content, 2);
      $new_content = $parts[0] . "<system.webServer>" . $vapt_block . $parts[1];
    } else {
      // Fallback: End of file
      $new_content = $content . $vapt_block;
    }

    // Write
    if (trim($new_content) !== trim($content) || !file_exists($config_path)) {
      @copy($config_path, $config_path . '.bak');
      return @file_put_contents($config_path, trim($new_content) . "\n", LOCK_EX) !== false;
    }

    return true;
  }
}
