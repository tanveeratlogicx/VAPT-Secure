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
        $domain = sanitize_text_field($data['domain']);
        $features = isset($data['features']) ? $data['features'] : [];
        $version = sanitize_text_field($data['version']);
        $white_label = $data['white_label'];
        $generate_type = isset($data['generate_type']) ? $data['generate_type'] : 'full_build';

        // 1. Setup Build Paths
        $upload_dir = wp_upload_dir();
        $base_storage_dir = $upload_dir['basedir'] . '/VAPT-Builds'; // Custom Storage Path

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

        $plugin_slug = sanitize_title($white_label['text_domain'] ?: $white_label['name']);
        $plugin_dir = $temp_dir . '/' . $plugin_slug;
        wp_mkdir_p($plugin_dir);

        // 2. Output Config Content (Generated)
        $active_data_file_name = null;
        if (isset($data['include_data']) && ($data['include_data'] === true || $data['include_data'] === 'true' || $data['include_data'] === 1)) {
            $active_data_file_name = get_option('vaptsecure_active_feature_file', 'Feature-List-99.json');
        }

        $license_scope = isset($data['license_scope']) ? $data['license_scope'] : 'single';
        $domain_limit = isset($data['installation_limit']) ? intval($data['installation_limit']) : 1;

        $config_content = self::generate_config_content($domain, $version, $features, $active_data_file_name, $license_scope, $domain_limit);

        // If Config Only -> Save and ZIP just that
        if ($generate_type === 'config_only') {
            $config_filename = "vapt-{$domain}-config-{$version}.php";
            file_put_contents($build_dir . '/' . $config_filename, $config_content);
            return $build_dir . '/' . $config_filename; // Return path to file directly
        }

        // 3. Full Build: Copy Plugin Files Recursively
        self::copy_plugin_files(VAPTSECURE_PATH, $plugin_dir, $active_data_file_name, $generate_type, $data);

        // 4. Inject Config File (If Requested)
        if (!isset($data['include_config']) || $data['include_config'] === true || $data['include_config'] === 'true' || $data['include_config'] === 1) {
            $config_filename = "vapt-{$domain}-config-{$version}.php";
            file_put_contents($plugin_dir . "/" . $config_filename, $config_content);
        }

        // 5. Rewrite Main Plugin File Headers & Logic
        self::rewrite_main_plugin_file($plugin_dir, $plugin_slug, $white_label, $version, $domain);

        // 6. Generate Documentation
        self::generate_docs($plugin_dir, $domain, $version, $features);

        // 7. Create ZIP Archive
        $zip_filename = "{$plugin_slug}-{$domain}-{$version}.zip";
        $zip_path = $build_dir . '/' . $zip_filename;

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            self::add_dir_to_zip($plugin_dir, $zip, $plugin_slug);
            $zip->close();
        }

        // Cleanup Temp
        self::recursive_rmdir($temp_dir);

        // Return URL to the ZIP
        $base_storage_url = $upload_dir['baseurl'] . '/VAPT-Builds';
        return $base_storage_url . '/' . $domain . '/' . $version . '/' . $zip_filename;
    }

    public static function generate_config_content($domain, $version, $features, $active_data_file = null, $license_scope = 'single', $domain_limit = 1)
    {
        $config = "<?php\n";
        $config .= "/**\n * VAPT Secure Configuration for $domain\n * Build Version: $version\n */\n\n";
        $config .= "if ( ! defined( 'ABSPATH' ) ) { exit; }\n\n";

        $config .= "// Domain Locking & Licensing\n";
        $config .= "define( 'VAPTSECURE_DOMAIN_LOCKED', '" . esc_sql($domain) . "' );\n";
        $config .= "define( 'VAPTSECURE_BUILD_VERSION', '" . esc_sql($version) . "' );\n";
        $config .= "define( 'VAPTSECURE_LICENSE_SCOPE', '" . esc_sql($license_scope) . "' );\n";
        $config .= "define( 'VAPTSECURE_DOMAIN_LIMIT', " . intval($domain_limit) . " );\n";

        // Security alert email (obfuscated to remove human-readable references)
        $alert_email = 'dGFubWFsaWs3ODZAZ21haWwuY29t'; // base64 encoded tanmalik786@gmail.com
        $config .= "define( 'VAPTSECURE_SECURITY_ALERT_EMAIL', base64_decode('" . $alert_email . "') );\n";

        if ($active_data_file) {
            $config .= "define( 'VAPTSECURE_ACTIVE_DATA_FILE', '" . esc_sql($active_data_file) . "' );\n";
        }

        $config .= "\n// Active Features\n";
        foreach ($features as $key) {
            $config .= "define( 'VAPTSECURE_FEATURE_" . strtoupper(str_replace('-', '_', $key)) . "', true );\n";
        }

        return $config;
    }

    private static function copy_plugin_files($source, $dest, $active_data_file = null, $generate_type = 'full_build', $build_data = [])
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        // Determine if config should be included
        $include_config = isset($build_data['include_config']) &&
                          ($build_data['include_config'] === true ||
                           $build_data['include_config'] === 'true' ||
                           $build_data['include_config'] === 1);

        // Core exclusions - development and testing files
        $exclusions = [
            '.git', '.vscode', 'node_modules', 'brain', 'tests', 'vapt-debug.txt',
            'Implementation Plan', 'plans', 'tools', 'archive', 'Debug', 'backup_debug_cleanup',
            // AI/Agent configuration directories
            '.ai', '.roo', '.claude', '.cursor', '.gemini', '.kilocode', '.qoder', '.trae',
            '.windsurf', '.opencode', '.agent', '.kilo',
            // Specific AI subdirectories
            '.ai/workflows', '.ai/skills', '.ai/rules',
            '.claude/skills', '.cursor/skills', '.gemini/antigravity/skills',
            '.kilocode/rules', '.qoder/skills', '.trae/skills', '.windsurf/skills',
            '.roo/rules', '.roo/skills',
            // Deployment directory
            'deployment',
            // Debug and search files
            'debug-field-mapping.js', 'debug-field-structure.js', 'search-enforcer-fields.js'
        ];

        // Documentation files to exclude (keep only README.md and How to User.md)
        $doc_exclusions = [
            'CLAUDE.md', 'DEBUG-MODE.md', 'VERSION_HISTORY.md', 'SOUL.md', 'SOUL_Claude-Notes.md',
            'SOUL_comprehensive.md', 'SOUL_enhanced.md', 'SOUL_with_selfcheck.md', 'SOUL-Claude-Ext.md',
            'SOUL-Claude.md', 'AGENTS.md', 'README-Claude-Ext.md'
        ];

        foreach ($iterator as $item) {
            $subPath = $iterator->getSubPathName();
            $filename = basename($subPath);

            // Check Exclusions
            foreach ($exclusions as $exclude) {
                if (strpos($subPath, $exclude) === 0) { continue 2;
                }
            }

            // Handle Data Directory
            if (strpos($subPath, 'data') === 0) {
                // When active data file is specified (Include Active Data enabled)
                if ($active_data_file) {
                    $active_file_allowed = false;

                    // Allow the data directory itself (root of data folder)
                    if ($item->isDir() && (strcasecmp($subPath, 'data') === 0 || strcasecmp($subPath, 'data/') === 0 || strcasecmp($subPath, 'data\\') === 0)) {
                        $active_file_allowed = true;
                    }
                    // Allow specific active data file
                    elseif (strpos($subPath, 'data\\' . $active_data_file) !== false ||
                        strpos($subPath, 'data/' . $active_data_file) !== false) {
                        $active_file_allowed = true;
                    }
                    // Allow top-level JSON files in data folder (files with exactly one slash)
                    elseif ((strpos($subPath, 'data/') === 0 || strpos($subPath, 'data\\') === 0) &&
                             (substr_count($subPath, '/') === 1 || substr_count($subPath, '\\') === 1) &&
                             !$item->isDir()) {
                        // Check if it's a JSON file (not ZIP)
                        if (preg_match('/\.json$/i', $filename)) {
                            $active_file_allowed = true;
                        }
                    }
                    // Allow Enforcers directory and its files (case-insensitive)
                    elseif (stripos($subPath, 'data/Enforcers/') === 0 || stripos($subPath, 'data\\Enforcers\\') === 0) {
                        // Allow the Enforcers directory itself so files can be copied into it
                        if ($item->isDir()) {
                            $active_file_allowed = true;
                        }
                        // Allow JSON template files in Enforcers
                        elseif (preg_match('/\.json$/i', $filename)) {
                            $active_file_allowed = true;
                        }
                    }
                    // Allow the Enforcers directory itself (case-insensitive, for directory creation)
                    elseif (strcasecmp($subPath, 'data/Enforcers') === 0 || strcasecmp($subPath, 'data\\Enforcers') === 0) {
                        if ($item->isDir()) {
                            $active_file_allowed = true;
                        }
                    }

                    if (!$active_file_allowed) {
                        continue;
                    }
                }
                // When no active data file (Include Active Data disabled)
                else {
                    continue; // Skip entire data folder
                }
            }

            // Exclude documentation files (except README.md and How to User.md)
            if (in_array($filename, $doc_exclusions, true)) {
                continue;
            }

            // Special case: exclude .md files in root except README.md and "How to User.md"
            if (strpos($subPath, '.md') !== false && strpos($subPath, '/') === false && strpos($subPath, '\\') === false) {
                if ($filename !== 'README.md' && $filename !== 'How to User.md') {
                    continue;
                }
            }

            // Exclude test files (files starting with "test-")
            if (strpos($filename, 'test-') === 0) {
                continue;
            }

            // Exclude domain-specific configuration files (vapt-*-config-*.php)
            // In config-only builds, allow all config files
            if (preg_match('/^vapt-.*-config-.*\.php$/i', $filename)) {
                // In config-only builds, allow config files
                if ($generate_type === 'config_only') {
                    // Allow - config files are purpose of this build
                }
                // When config inclusion is enabled, still exclude existing ones
                // (a new one will be generated in the generate() method)
                else {
                    continue;
                }
            }

            // Exclude ZIP files globally
            if (preg_match('/\.zip$/i', $filename)) {
                continue;
            }

            if ($item->isDir()) {
                if (!file_exists($dest . DIRECTORY_SEPARATOR . $subPath)) {
                    mkdir($dest . DIRECTORY_SEPARATOR . $subPath, 0755, true);
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $subPath);
            }
        }
    }

    private static function rewrite_main_plugin_file($plugin_dir, $plugin_slug, $white_label, $version, $domain)
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

        // Remove ALL superadmin functionality from generated builds using more precise patterns
        
        // 1. Remove vaptsecure_get_superadmin_identity() function with its specific docblock
        // Match from "ðŸ”’ Obfuscated Superadmin Identity" to end of function
        $content = preg_replace('/\/\*\*\s*\n \* ðŸ”’ Obfuscated Superadmin Identity[\s\S]*?function vaptsecure_get_superadmin_identity\s*\(\)\s*\{[^}]+\}/s', '', $content);
        
        // 2. Remove VAPTSECURE_SUPERADMIN_USER and VAPTSECURE_SUPERADMIN_EMAIL constants definition
        // Match from "// Set Superadmin Constants" to the end of the second define statement
        $content = preg_replace('/\/\/ Set Superadmin Constants\s*\n\$vaptsecure_identity = vaptsecure_get_superadmin_identity\(\);\s*\nif \(! defined\(\'VAPTSECURE_SUPERADMIN_USER\'\)\) \{[^}]+\}\s*\nif \(! defined\(\'VAPTSECURE_SUPERADMIN_EMAIL\'\)\) \{[^}]+\}/s', '', $content);
        
        // 3. Remove is_vaptsecure_superadmin() function with its specific docblock
        // Match from "ðŸ”’ Strict Superadmin Check" to end of function
        $content = preg_replace('/\/\*\*\s*\n \* ðŸ”’ Strict Superadmin Check[\s\S]*?function is_vaptsecure_superadmin\s*\([^)]*\)\s*\{[^}]+\}/s', '', $content);
        
        // 4. Remove superadmin menu logic (lines 607-645 in vaptsecure.php)
        // This removes the conditional superadmin menu items
        $content = preg_replace('/\$is_superadmin_identity = is_vaptsecure_superadmin\(false\);\s*\/\/ 1\. Parent Menu[\s\S]*?remove_submenu_page\(\'vaptsecure\', \'vaptsecure\'\);/s', '// 1. Parent Menu (Visible to all admins with manage_options)
        add_menu_page(
            __(\'VAPT Secure\', \'vaptsecure\'),
            __(\'VAPT Secure\', \'vaptsecure\'),
            \'manage_options\',
            \'vaptsecure\',
            \'vaptsecure_render_client_status_page\',
            \'dashicons-shield\',
            80
        );

        // Remove the default duplicate submenu item created by WordPress
        remove_submenu_page(\'vaptsecure\', \'vaptsecure\');', $content);
        
        // 5. Remove superadmin page rendering functions
        // Match each function individually with more precise patterns
        $content = preg_replace('/\/\*\*\s*\n \* Render Workbench Page[\s\S]*?function vaptsecure_render_workbench_page\s*\([^)]*\)\s*\{[^}]+\}/s', '', $content);
        $content = preg_replace('/\/\*\*\s*\n \* Render Admin Page[\s\S]*?function vaptsecure_render_admin_page\s*\([^)]*\)\s*\{[^}]+\}/s', '', $content);
        $content = preg_replace('/\/\*\*\s*\n \* Master Dashboard Page[\s\S]*?function vaptsecure_master_dashboard_page\s*\([^)]*\)\s*\{[^}]+\}/s', '', $content);

        // Inject Domain Guard & Config Loader
        $guard_code = "\n// VAPT Secure Client Build Configuration\n";
        $guard_code .= "if ( file_exists( plugin_dir_path( __FILE__ ) . 'vapt-{$domain}-config-{$version}.php' ) ) {\n";
        $guard_code .= "    require_once plugin_dir_path( __FILE__ ) . 'vapt-{$domain}-config-{$version}.php';\n";
        $guard_code .= "}\n\n";

        $guard_code .= "// Domain Integrity & Multi-Site Guard\n";
        $guard_code .= "if ( defined('VAPTSECURE_LICENSE_SCOPE') ) {\n";
        $guard_code .= "    \$current_host = \$_SERVER['HTTP_HOST'];\n";
        $guard_code .= "    if ( VAPTSECURE_LICENSE_SCOPE === 'single' ) {\n";
        $guard_code .= "        if ( \$current_host !== VAPTSECURE_DOMAIN_LOCKED ) {\n";
        $guard_code .= "            vaptsecure_handle_unauthorized_domain( \$current_host, VAPTSECURE_DOMAIN_LOCKED );\n";
        $guard_code .= "        }\n";
        $guard_code .= "    } else if ( VAPTSECURE_LICENSE_SCOPE === 'multisite' ) {\n";
        $guard_code .= "        \$allowed_limit = defined('VAPTSECURE_DOMAIN_LIMIT') ? intval(VAPTSECURE_DOMAIN_LIMIT) : 0;\n";
        $guard_code .= "        if ( \$allowed_limit > 0 ) {\n";
        $guard_code .= "            \$activated_domains = get_option('vaptsecure_activated_domains', array());\n";
        $guard_code .= "            if ( !in_array(\$current_host, \$activated_domains) ) {\n";
        $guard_code .= "                if ( count(\$activated_domains) >= \$allowed_limit ) {\n";
        $guard_code .= "                    vaptsecure_handle_unauthorized_domain( \$current_host, 'Multi-Site Limit Exceeded' );\n";
        $guard_code .= "                } else {\n";
        $guard_code .= "                    \$activated_domains[] = \$current_host;\n";
        $guard_code .= "                    update_option('vaptsecure_activated_domains', \$activated_domains);\n";
        $guard_code .= "                }\n";
        $guard_code .= "            }\n";
        $guard_code .= "        }\n";
        $guard_code .= "    }\n";
        $guard_code .= "}\n\n";

        $guard_code .= "function vaptsecure_handle_unauthorized_domain( \$host, \$target ) {\n";
        $guard_code .= "    // Use site admin email for security alerts\n";
        $guard_code .= "    \$admin_email = get_option('admin_email');\n";
        $guard_code .= "    \$subject = 'Security Alert: Unauthorized VAPT Secure Usage';\n";
        $guard_code .= "    \$message = 'The VAPT Secure plugin was detected on an unauthorized domain: ' . \$host . ' (Locked to: ' . \$target . ')';\n";
        $guard_code .= "    wp_mail(\$admin_email, \$subject, \$message);\n\n";
        $guard_code .= "    if ( !function_exists('is_admin') || !is_admin() ) {\n";
        $guard_code .= "        wp_die('<h1>Security Alert</h1><p>This security plugin is not licensed for this domain.</p>', 'VAPT Licensing');\n";
        $guard_code .= "    }\n";
        $guard_code .= "}\n";

        // Insert after defined('ABSPATH') check
        $content = str_replace("if (! defined('ABSPATH')) {\n  exit;\n}", "if (! defined('ABSPATH')) {\n  exit;\n}\n" . $guard_code, $content);

        // Remove the original file from the copy if it was copied by the recursive copier
        if (file_exists($plugin_dir . '/vaptsecure.php')) { unlink($plugin_dir . '/vaptsecure.php');
        }
        if (file_exists($plugin_dir . '/vapt-copilot.php')) { unlink($plugin_dir . '/vapt-copilot.php');
        }

        file_put_contents($dest_main, $content);
    }

    private static function generate_docs($dir, $domain, $version, $features)
    {
        $readme = "# VAPT Secure Security Build for $domain\n\n";
        $readme .= "Version: $version\n";
        $readme .= "Generated: " . date('Y-m-d') . "\n\n";
        $readme .= "## Active Protection Modules\n";
        foreach ($features as $f) {
            $readme .= "- " . strtoupper(str_replace('-', ' ', $f)) . "\n";
        }
        file_put_contents($dir . '/README.md', $readme);
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
                    if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object)) {
                        self::recursive_rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
