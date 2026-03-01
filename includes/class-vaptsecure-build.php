<?php

/**
 * Build Generator for VAPT Secure
 */

if (! defined('ABSPATH')) {
  exit;
}

class VAPTSECURE_Build
{
  /**
   * Generate a build ZIP for a specific domain
   */
  public static function generate($data)
  {
    global $wpdb;
    $domain = sanitize_text_field($data['domain']);
    $features = isset($data['features']) ? $data['features'] : [];
    $version = sanitize_text_field($data['version']);
    $white_label = $data['white_label'];
    $generate_type = isset($data['generate_type']) ? $data['generate_type'] : 'full_build';

    // Fetch License Information from DB for this domain
    $table_name = $wpdb->prefix . 'vaptsecure_domains';
    $domain_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE domain = %s", $domain), ARRAY_A);

    $license_id = $domain_record ? $domain_record['license_id'] : '';
    $manual_expiry_date = $domain_record ? $domain_record['manual_expiry_date'] : '';
    $license_scope = isset($data['license_scope']) ? $data['license_scope'] : ($domain_record ? $domain_record['license_scope'] : 'single');
    $domain_limit = isset($data['installation_limit']) ? intval($data['installation_limit']) : ($domain_record ? intval($domain_record['installation_limit']) : 1);

    // Master URL Local Environment Detection
    $current_host = $_SERVER['HTTP_HOST'];
    if (preg_match('/\.local$|\.test$|localhost|^127\.0\.0\.1/', $current_host)) {
      $master_url = 'https://vaptsecure.net';
    } else {
      $master_url = get_site_url();
    }

    $plugin_slug = sanitize_title($white_label['text_domain'] ?: $white_label['name']);

    // 1. Setup Build Paths
    $upload_dir = wp_upload_dir();
    $base_storage_dir = $upload_dir['basedir'] . '/' . $plugin_slug; // Dynamic Storage Path

    // Ensure storage directory exists
    if (!file_exists($base_storage_dir)) {
      wp_mkdir_p($base_storage_dir);
      // Secure the directory
      file_put_contents($base_storage_dir . '/index.php', '<?php // Silence is golden');
      file_put_contents($base_storage_dir . '/.htaccess', 'Options -Indexes');
    }

    $build_slug = sanitize_title($domain . '-' . $version);
    $build_dir = $base_storage_dir . '/' . $domain . '/' . $version;
    wp_mkdir_p($build_dir);

    // Temp dir for assembly
    $temp_dir = get_temp_dir() . 'vapt-build-' . time() . '-' . wp_generate_password(8, false);
    wp_mkdir_p($temp_dir);

    $plugin_dir = $temp_dir . '/' . $plugin_slug;
    wp_mkdir_p($plugin_dir);

    // 2. Output Config Content (Generated)
    $active_data_file_name = null;
    if (isset($data['include_data']) && ($data['include_data'] === true || $data['include_data'] === 'true' || $data['include_data'] === 1)) {
      $active_data_file_name = get_option('vaptsecure_active_feature_file', 'Feature-List-99.json');
    }

    $config_content = self::generate_config_content($domain, $version, $features, $active_data_file_name, $license_scope, $domain_limit, $license_id, $manual_expiry_date);

    // If Config Only -> Save and ZIP just that
    if ($generate_type === 'config_only') {
      $config_filename = "vapt-{$domain}-config-{$version}.php";
      file_put_contents($build_dir . '/' . $config_filename, $config_content);
      return $build_dir . '/' . $config_filename; // Return path to file directly
    }

    // 3. Full Build: Copy Plugin Files Recursively
    self::copy_plugin_files(VAPTSECURE_PATH, $plugin_dir, $active_data_file_name);

    // 4. Inject Config File (If Requested)
    if (!isset($data['include_config']) || $data['include_config'] === true || $data['include_config'] === 'true' || $data['include_config'] === 1) {
      file_put_contents($plugin_dir . "/config-{$domain}.php", $config_content);
    }

    // 5. Rewrite Main Plugin File Headers & Logic
    self::rewrite_main_plugin_file($plugin_dir, $plugin_slug, $white_label, $version, $domain, $master_url);

    // 6. Generate Documentation
    self::generate_docs($plugin_dir, $domain, $version, $features, $white_label);

    // 7. Create ZIP Archive
    $zip_filename = "{$plugin_slug}-{$version}.zip";
    $zip_path = $build_dir . '/' . $zip_filename;

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
      self::add_dir_to_zip($plugin_dir, $zip, $plugin_slug);
      $zip->close();
    }

    // Cleanup Temp
    self::recursive_rmdir($temp_dir);

    // Return URL to the ZIP
    $base_storage_url = $upload_dir['baseurl'] . '/' . $plugin_slug;
    return $base_storage_url . '/' . $domain . '/' . $version . '/' . $zip_filename;
  }

  public static function generate_config_content($domain, $version, $features, $active_data_file = null, $license_scope = 'single', $domain_limit = 1, $license_id = '', $expiry = '')
  {
    $config = "<?php\n";
    $config .= "/**\n * VAPT Secure Configuration for " . esc_html($domain) . "\n * Build Version: " . esc_html($version) . "\n */\n\n";
    $config .= "if ( ! defined( 'ABSPATH' ) ) { exit; }\n\n";

    $config .= "// Domain Locking & Licensing\n";
    $config .= "define( 'VAPTSECURE_DOMAIN_LOCKED', '" . esc_sql($domain) . "' );\n";
    $config .= "define( 'VAPTSECURE_BUILD_VERSION', '" . esc_sql($version) . "' );\n";
    $config .= "define( 'VAPTSECURE_LICENSE_SCOPE', '" . esc_sql($license_scope) . "' );\n";
    $config .= "define( 'VAPTSECURE_DOMAIN_LIMIT', " . intval($domain_limit) . " );\n";
    $config .= "define( 'VAPTSECURE_LICENSE_EXPIRY', '" . esc_sql($expiry) . "' );\n";

    if ($active_data_file) {
      $config .= "define( 'VAPTSECURE_ACTIVE_DATA_FILE', '" . esc_sql($active_data_file) . "' );\n";
    }

    $config .= "\n// Active Features\n";
    foreach ($features as $key) {
      $config .= "define( 'VAPTSECURE_FEATURE_" . strtoupper(str_replace('-', '_', $key)) . "', true );\n";
    }

    // Obfuscated Payload & Signature
    $payload_array = array(
      'domain' => $domain,
      'scope' => $license_scope,
      'limit' => $domain_limit,
      'expiry' => $expiry,
      'license_id' => $license_id,
      'version' => $version
    );
    $obfuscated_payload = base64_encode(json_encode($payload_array));
    $secret_salt = wp_generate_password(64, true, true);
    $signature = hash_hmac('sha256', $obfuscated_payload, $secret_salt);

    $config .= "\n// License Enforcement Handlers\n";
    $config .= "define( 'VAPTSECURE_OBFUSCATED_PAYLOAD', '" . esc_sql($obfuscated_payload) . "' );\n";
    $config .= "define( 'VAPTSECURE_CONFIG_SIGNATURE', '" . esc_sql($signature) . "' );\n";
    $config .= "define( 'VAPTSECURE_SALT', '" . esc_sql($secret_salt) . "' );\n";

    return $config;
  }

  private static function copy_plugin_files($source, $dest, $active_data_file = null)
  {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    $exclusions = ['.git', '.vscode', 'node_modules', 'brain', 'tests', 'vapt-debug.txt', 'Implementation Plan', 'plans', 'patch2.py', '.agent'];

    foreach ($iterator as $item) {
      $subPath = $iterator->getSubPathName();

      // Check Exclusions
      $is_excluded = false;
      foreach ($exclusions as $exclude) {
        if (strpos($subPath, $exclude) === 0) {
          $is_excluded = true;
          break;
        }
      }
      if ($is_excluded) continue;

      // Ensure NO markdown files are copied except what is explicitly processed later
      if (!$item->isDir() && strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION)) === 'md') {
        // We will generate our own README.md and USER_GUIDE.md later, block all .md files globally
        continue;
      }

      // Handle Data Directory (Intelligent Bundling)
      if (strpos($subPath, 'data' . DIRECTORY_SEPARATOR) === 0 || $subPath === 'data') {
        if ($active_data_file) {
          if ($item->isDir()) {
            // Let the data directory pass so it gets created
          } else {
            $ext = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));

            // Only consider markdown and JSON files
            if (!in_array($ext, ['json', 'md'])) {
              continue;
            }

            // If it is a JSON file, check if it's considered part of the official bundle.
            if ($ext === 'json') {
              static $allowed_json_files = null;

              // Lazy-load the allowed JSON files list by scanning MD files in data/
              if ($allowed_json_files === null) {
                $allowed_json_files = [];
                $data_dir_path = $source . DIRECTORY_SEPARATOR . 'data';
                if (is_dir($data_dir_path)) {
                  $md_files = glob($data_dir_path . DIRECTORY_SEPARATOR . '*.md');
                  foreach ($md_files as $md_file) {
                    $md_content = file_get_contents($md_file);
                    // Look for filenames ending in .json mentioned in the markdown
                    if (preg_match_all('/([a-zA-Z0-9_\-]+\.json)/i', $md_content, $matches)) {
                      foreach ($matches[1] as $match) {
                        $allowed_json_files[] = strtolower($match);
                      }
                    }
                  }
                }
              }

              // Only include this JSON file if it was explicitly mentioned in one of the MD files
              if (!in_array(strtolower($item->getFilename()), $allowed_json_files)) {
                continue;
              }
            }
          }
        } else {
          continue; // Skip the entire data directory if not requested
        }
      }


      if ($item->isDir()) {
        if (!file_exists($dest . DIRECTORY_SEPARATOR . $subPath)) {
          mkdir($dest . DIRECTORY_SEPARATOR . $subPath);
        }
      } else {
        copy($item, $dest . DIRECTORY_SEPARATOR . $subPath);
      }
    }
  }

  private static function rewrite_main_plugin_file($plugin_dir, $plugin_slug, $white_label, $version, $domain, $master_url)
  {
    // We need to copy vaptsecure.php to [plugin-slug].php and modify headers
    $source_main = VAPTSECURE_PATH . 'vaptsecure.php';
    $dest_main = $plugin_dir . '/' . $plugin_slug . '.php'; // Rename main file

    $content = file_get_contents($source_main);

    // Rewrite Headers
    $headers = "/**\n";
    $headers .= " * Plugin Name: " . $white_label['name'] . "\n";
    $headers .= " * Plugin URI: " . $white_label['plugin_uri'] . "\n";
    $headers .= " * Description: " . $white_label['description'] . "\n";
    $headers .= " * Version: " . $version . "\n";
    $headers .= " * Author: " . $white_label['author'] . "\n";
    $headers .= " * Author URI: " . $white_label['author_uri'] . "\n";
    $headers .= " * Text Domain: " . $white_label['text_domain'] . "\n";
    $headers .= " */\n";

    // Regex replace the existing header block
    $content = preg_replace('/\/\*\*.*?\*\//s', $headers, $content, 1);

    // Provide a safe guard against Duplicate installations (Collision Prevention & Cleanup)
    $collision_guard = "\n// Proactive Collision Guard & Cleanup\n";
    $collision_guard .= "if (defined('VAPTSECURE_VERSION')) {\n";
    $collision_guard .= "    add_action('admin_notices', function() {\n";
    $collision_guard .= "        echo '<div class=\"notice notice-error is-dismissible\"><p><strong>Security Alert:</strong> Another version of VAPT Secure (or a White Labeled instance) is already active. This instance has halted to prevent a fatal crash.</p></div>';\n";
    $collision_guard .= "    });\n";
    // Attempt Auto-Cleanup of the old plugin directory
    $collision_guard .= "    if (function_exists('deactivate_plugins')) {\n";
    $collision_guard .= "        require_once ABSPATH . 'wp-admin/includes/plugin.php';\n";
    $collision_guard .= "        require_once ABSPATH . 'wp-admin/includes/file.php';\n";
    $collision_guard .= "        \$active_plugins = get_option('active_plugins', array());\n";
    $collision_guard .= "        \$this_plugin = plugin_basename(__FILE__);\n";
    $collision_guard .= "        foreach (\$active_plugins as \$plugin) {\n";
    $collision_guard .= "            if (\$plugin !== \$this_plugin && (strpos(\$plugin, 'vapt-secure.php') !== false || strpos(file_get_contents(WP_PLUGIN_DIR . '/' . \$plugin), 'VAPTSECURE_VERSION') !== false)) {\n";
    $collision_guard .= "                deactivate_plugins(\$plugin);\n";
    $collision_guard .= "                \$plugin_dir = dirname(WP_PLUGIN_DIR . '/' . \$plugin);\n";
    $collision_guard .= "                if (\$plugin_dir !== WP_PLUGIN_DIR) {\n";
    $collision_guard .= "                    WP_Filesystem();\n";
    $collision_guard .= "                    global \$wp_filesystem;\n";
    $collision_guard .= "                    if (\$wp_filesystem) { \$wp_filesystem->delete(\$plugin_dir, true); }\n";
    $collision_guard .= "                }\n";
    $collision_guard .= "            }\n";
    $collision_guard .= "        }\n";
    $collision_guard .= "    }\n";
    $collision_guard .= "    return;\n";
    $collision_guard .= "}\n";

    // Insert the collision guard immediately after the ABSPATH check instead of relying on string replacement
    $content = str_replace("if (! defined('ABSPATH')) {\n  exit;\n}", "if (! defined('ABSPATH')) {\n  exit;\n}\n" . $collision_guard, $content);

    $obfuscated_master_url = base64_encode($master_url);
    $obfuscated_verify_endpoint = base64_encode('/wp-json/vaptsecure/v1/license/verify');
    $obfuscated_email = base64_encode(VAPTSECURE_SUPERADMIN_EMAIL);

    // Inject Domain Guard & Config Loader
    $guard_code = "\n// Client Build Configuration Loader\n";
    $guard_code .= "if ( file_exists( plugin_dir_path( __FILE__ ) . 'config-{$domain}.php' ) ) {\n";
    $guard_code .= "    require_once plugin_dir_path( __FILE__ ) . 'config-{$domain}.php';\n";
    $guard_code .= "}\n\n";

    // Deactivation Hook: Revert All Security Rules
    $guard_code .= "function _vaptsecure_revert_all_rules() {\n";
    $guard_code .= "    \$files = array(\n";
    $guard_code .= "        ABSPATH . '.htaccess',\n";
    $guard_code .= "        ABSPATH . 'wp-config.php'\n";
    $guard_code .= "    );\n";
    $guard_code .= "    foreach (\$files as \$file) {\n";
    $guard_code .= "        if (file_exists(\$file) && is_writable(\$file)) {\n";
    $guard_code .= "            \$content = file_get_contents(\$file);\n";
    $guard_code .= "            \$pattern = '/# BEGIN VAPT .*?# END VAPT [a-zA-Z0-9_-]+\\s*/s';\n";
    $guard_code .= "            \$content = preg_replace(\$pattern, '', \$content);\n";
    $guard_code .= "            \$pattern2 = '/\\/\\* BEGIN VAPT .*?\\/\\* END VAPT [a-zA-Z0-9_-]+ \\*\\/\\s*/s';\n";
    $guard_code .= "            \$content = preg_replace(\$pattern2, '', \$content);\n";
    $guard_code .= "            file_put_contents(\$file, \$content);\n";
    $guard_code .= "        }\n";
    $guard_code .= "    }\n";
    $guard_code .= "}\n";
    $guard_code .= "register_deactivation_hook( __FILE__, '_vaptsecure_revert_all_rules' );\n\n";

    // Master Guard: Expiration, Tamper Check, Phone Home
    $guard_code .= "function _vapt_sys_router_guard() {\n";
    $guard_code .= "    if (!defined('VAPTSECURE_DOMAIN_LOCKED')) return;\n";

    $guard_code .= "    // 1. Signature Check\n";
    $guard_code .= "    if (!defined('VAPTSECURE_OBFUSCATED_PAYLOAD') || !defined('VAPTSECURE_SALT')) { _vaptsecure_revert_all_rules(); _vaptsecure_handle_violation('Tamper Detected: Missing Config'); return; }\n";
    $guard_code .= "    \$calc_sig = hash_hmac('sha256', VAPTSECURE_OBFUSCATED_PAYLOAD, VAPTSECURE_SALT);\n";
    $guard_code .= "    if (\$calc_sig !== VAPTSECURE_CONFIG_SIGNATURE) {\n";
    $guard_code .= "        _vaptsecure_revert_all_rules();\n";
    $guard_code .= "        _vaptsecure_handle_violation('Tamper Detected: Invalid Signature');\n";
    $guard_code .= "    }\n";

    $guard_code .= "    // 2. Storage Cross-Check & Obfuscated State\n";
    $guard_code .= "    \$payload = json_decode(base64_decode(VAPTSECURE_OBFUSCATED_PAYLOAD), true);\n";
    $guard_code .= "    \$saved_cache = get_option('_transient_wp_sec_cache_v3');\n";
    $guard_code .= "    if (!\$saved_cache) {\n";
    $guard_code .= "        update_option('_transient_wp_sec_cache_v3', base64_encode(serialize(\$payload)));\n";
    $guard_code .= "    } else {\n";
    $guard_code .= "        \$stored = unserialize(base64_decode(\$saved_cache));\n";
    $guard_code .= "        if (\$stored['expiry'] !== \$payload['expiry'] || \$stored['limit'] !== \$payload['limit']) {\n";
    $guard_code .= "             _vaptsecure_revert_all_rules();\n";
    $guard_code .= "             _vaptsecure_handle_violation('Tamper Detected: State Mismatch');\n";
    $guard_code .= "        }\n";
    $guard_code .= "    }\n";

    $guard_code .= "    // 3. Expiry Check\n";
    $guard_code .= "    if (!empty(\$payload['expiry']) && strtotime(\$payload['expiry']) < time()) {\n";
    $guard_code .= "        _vaptsecure_revert_all_rules();\n"; // Expired! Revert rules!
    $guard_code .= "        _vaptsecure_handle_violation('License Expired');\n";
    $guard_code .= "    }\n";

    $guard_code .= "    // 4. Domain & Multi-Site Guard (Phone Home)\n";
    $guard_code .= "    \$current_host = \$_SERVER['HTTP_HOST'];\n";
    $guard_code .= "    if ( \$payload['scope'] === 'single' ) {\n";
    $guard_code .= "        if ( \$current_host !== VAPTSECURE_DOMAIN_LOCKED ) {\n";
    $guard_code .= "             _vaptsecure_revert_all_rules();\n";
    $guard_code .= "            _vaptsecure_handle_violation('Domain Mismatch: Locked to ' . VAPTSECURE_DOMAIN_LOCKED);\n";
    $guard_code .= "        }\n";
    $guard_code .= "    } else if ( \$payload['scope'] === 'multisite' ) {\n";
    $guard_code .= "        \$allowed_limit = intval(\$payload['limit']);\n";
    $guard_code .= "        if ( \$allowed_limit > 0 ) {\n";
    $guard_code .= "            \$activated_domains = get_option('vaptsecure_activated_domains', array());\n";
    $guard_code .= "            if ( !in_array(\$current_host, \$activated_domains) ) {\n";
    $guard_code .= "                if ( count(\$activated_domains) >= \$allowed_limit ) {\n";
    $guard_code .= "                    _vaptsecure_revert_all_rules();\n";
    $guard_code .= "                    _vaptsecure_handle_violation('Multi-Site Limit Exceeded');\n";
    $guard_code .= "                } else {\n";
    $guard_code .= "                    // Phone Home Check\n";
    $guard_code .= "                    \$url = base64_decode('" . $obfuscated_master_url . "');\n";
    $guard_code .= "                    \$endpoint = base64_decode('" . $obfuscated_verify_endpoint . "');\n";
    $guard_code .= "                    \$response = wp_remote_post(\$url . \$endpoint, array(\n";
    $guard_code .= "                        'body' => array('license_id' => \$payload['license_id'], 'domain' => \$current_host)\n";
    $guard_code .= "                    ));\n";
    $guard_code .= "                    if (!is_wp_error(\$response) && wp_remote_retrieve_response_code(\$response) == 200) {\n";
    $guard_code .= "                        \$body = json_decode(wp_remote_retrieve_body(\$response), true);\n";
    $guard_code .= "                        if (isset(\$body['status']) && \$body['status'] === 'blocked') {\n";
    $guard_code .= "                            _vaptsecure_revert_all_rules();\n";
    $guard_code .= "                            _vaptsecure_handle_violation('Activation Blocked by License Server');\n";
    $guard_code .= "                        }\n";
    $guard_code .= "                    }\n";
    $guard_code .= "                    \$activated_domains[] = \$current_host;\n";
    $guard_code .= "                    update_option('vaptsecure_activated_domains', \$activated_domains);\n";
    $guard_code .= "                }\n";
    $guard_code .= "            }\n";
    $guard_code .= "        }\n";
    $guard_code .= "    }\n";
    $guard_code .= "}\n\n";

    $guard_code .= "function _vaptsecure_handle_violation( \$reason ) {\n";
    $guard_code .= "    \$alertContact = base64_decode('" . $obfuscated_email . "');\n";
    $guard_code .= "    \$subject = 'Security Alert: Usage Violation';\n";
    $guard_code .= "    \$message = 'Violation on ' . \$_SERVER['HTTP_HOST'] . ' Reason: ' . \$reason;\n";
    $guard_code .= "    if (\$alertContact) { wp_mail(\$alertContact, \$subject, \$message); }\n\n";
    $guard_code .= "    if ( !function_exists('is_admin') || !is_admin() ) {\n";
    $guard_code .= "        wp_die('<h1>Security Alert</h1><p>This security plugin has encountered a licensing error.</p>', 'Protection System');\n";
    $guard_code .= "    }\n";
    $guard_code .= "}\n";

    $guard_code .= "add_action('init', '_vapt_sys_router_guard');\n";
    $guard_code .= "add_action('admin_init', '_vapt_sys_router_guard');\n";

    // Insert Domain Guard after the collision guard
    $content = str_replace($collision_guard, $collision_guard . $guard_code, $content);

    // Remove the original file from the copy if it was copied by the recursive copier
    if (file_exists($plugin_dir . '/vaptsecure.php')) unlink($plugin_dir . '/vaptsecure.php');
    if (file_exists($plugin_dir . '/vapt-copilot.php')) unlink($plugin_dir . '/vapt-copilot.php');

    file_put_contents($dest_main, $content);
  }

  private static function generate_docs($dir, $domain, $version, $features, $white_label)
  {
    // Generate README.md
    $readme = "# Security Build for " . esc_html($domain) . "\n\n";
    $readme .= "Version: " . esc_html($version) . "\n";
    $readme .= "Generated: " . date('Y-m-d') . "\n\n";
    $readme .= "## Active Protection Modules\n";
    foreach ($features as $f) {
      $readme .= "- " . strtoupper(str_replace('-', ' ', $f)) . "\n";
    }
    file_put_contents($dir . '/README.md', $readme);

    // Generate USER_GUIDE.md
    $guide = "# User Guide: " . esc_html($white_label['name']) . "\n\n";
    $guide .= "Welcome to your custom security implementation for **" . esc_html($domain) . "**.\n\n";
    $guide .= "## Installation\n\n";
    $guide .= "1. Log into your WordPress admin dashboard.\n";
    $guide .= "2. Navigate to **Plugins > Add New**.\n";
    $guide .= "3. Click **Upload Plugin** and select this zip file.\n";
    $guide .= "4. Click **Install Now** and then **Activate Plugin**.\n\n";
    $guide .= "## Active Protections\n\n";
    $guide .= "This targeted build automatically enforces the following security definitions:\n\n";
    foreach ($features as $f) {
      $guide .= "- **" . strtoupper(str_replace('-', ' ', $f)) . "**\n";
    }
    $guide .= "\n\n---\n*Generated specifically for " . esc_html($domain) . " on " . date('Y-m-d') . " by " . esc_html($white_label['author']) . "*\n";
    file_put_contents($dir . '/USER_GUIDE.md', $guide);
  }

  private static function add_dir_to_zip($dir, $zip, $zip_path)
  {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $name => $file) {
      if (! $file->isDir()) {
        $file_path = $file->getRealPath();
        $relative_path = $zip_path . '/' . substr($file_path, strlen($dir) + 1);
        $zip->addFile($file_path, $relative_path);
      }
    }
  }

  private static function recursive_rmdir($dir)
  {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object))
            self::recursive_rmdir($dir . "/" . $object);
          else
            unlink($dir . "/" . $object);
        }
      }
      rmdir($dir);
    }
  }
}
