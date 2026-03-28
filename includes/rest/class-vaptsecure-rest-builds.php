<?php

/**
 * Builds REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_Builds extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/build/generate', array(
            'methods'  => 'POST',
            'callback' => array($this, 'generate_build'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'installation_limit' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'license_scope' => array(
                    'type' => 'string',
                    'default' => 'single',
                ),
                'include_config' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'include_data' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/build/save-config', array(
            'methods'  => 'POST',
            'callback' => array($this, 'save_config_to_root'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/build/sync-config', array(
            'methods'  => 'POST',
            'callback' => array($this, 'sync_config_from_file'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    public function generate_build($request)
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data(VAPTSECURE_PATH . 'vaptsecure.php');
        $version = $plugin_data['Version'] ?? '1.0.0';
        $plugin_name = sanitize_title($plugin_data['Name'] ?? 'vaptsecure');

        $installation_limit = $request->get_param('installation_limit') ?? 1;
        $license_scope = $request->get_param('license_scope') ?? 'single';
        $include_config = $request->get_param('include_config') ?? true;
        $include_data = $request->get_param('include_data') ?? false;

        $zip = new ZipArchive();
        $temp_dir = sys_get_temp_dir();
        $zip_file = $temp_dir . '/' . $plugin_name . '-' . $version . '-' . time() . '.zip';

        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->error_response('Could not create zip file', 'zip_error', 500);
        }

        $excluded = array(
            '.git',
            '.gitignore',
            '.DS_Store',
            'node_modules',
            'vendor',
            'composer.lock',
            'package-lock.json',
            'yarn.lock',
            '*.log',
            '*.tmp',
            '*.swp',
            '*.swo',
            'Thumbs.db',
            '.idea',
            '.vscode',
            '*.zip'
        );

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(VAPTSECURE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative_path = str_replace(VAPTSECURE_PATH, '', $item->getPathname());

            $skip = false;
            foreach ($excluded as $pattern) {
                if (fnmatch($pattern, basename($relative_path)) || fnmatch($pattern, $relative_path)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($relative_path);
            } else {
                $zip->addFile($item->getPathname(), $relative_path);
            }
        }

        if ($include_config) {
            $config = $this->generate_build_config($installation_limit, $license_scope);
            $zip->addFromString('vaptsecure-config.json', json_encode($config, JSON_PRETTY_PRINT));
        }

        if ($include_data) {
            $data_files = glob(VAPTSECURE_PATH . 'data/*.json');
            foreach ($data_files as $data_file) {
                $relative = str_replace(VAPTSECURE_PATH, '', $data_file);
                $zip->addFile($data_file, $relative);
            }
        }

        $zip->close();

        $this->log_security_event('build_generated', array(
            'version' => $version,
            'installation_limit' => $installation_limit,
            'license_scope' => $license_scope,
            'include_config' => $include_config,
            'include_data' => $include_data
        ));

        return $this->success_response(array(
            'zip_url' => content_url('/uploads/' . basename($zip_file)),
            'filename' => basename($zip_file),
            'size' => filesize($zip_file),
            'version' => $version
        ), 'Build generated successfully');
    }

    private function generate_build_config($installation_limit, $license_scope)
    {
        global $wpdb;

        $domains = $wpdb->get_results("SELECT domain, features FROM {$wpdb->prefix}vaptsecure_domains", ARRAY_A);
        $features = VAPTSECURE_DB::get_feature_statuses_full();

        return array(
            'version' => defined('VAPTSECURE_VERSION') ? VAPTSECURE_VERSION : '1.0.0',
            'generated_at' => current_time('mysql'),
            'installation_limit' => $installation_limit,
            'license_scope' => $license_scope,
            'domains' => $domains,
            'features' => $features,
            'global_enforcement' => get_option('vaptsecure_global_enforcement', array()),
            'environment_profile' => $this->get_current_environment_profile()
        );
    }

    public function save_config_to_root($request)
    {
        $config_content = $request->get_param('config');
        if (empty($config_content)) {
            return $this->error_response('No config content provided', 'missing_config', 400);
        }

        $config_path = ABSPATH . 'vaptsecure-config.json';
        
        if (file_put_contents($config_path, $config_content) === false) {
            return $this->error_response('Failed to save config file', 'file_error', 500);
        }

        $this->log_security_event('config_saved_to_root', array(
            'path' => $config_path,
            'size' => strlen($config_content)
        ));

        return $this->success_response(array(
            'path' => $config_path,
            'size' => filesize($config_path)
        ), 'Config saved to root directory');
    }

    public function sync_config_from_file($request)
    {
        $config_path = ABSPATH . 'vaptsecure-config.json';
        
        if (!file_exists($config_path)) {
            return $this->not_found_response('Config file not found in root directory');
        }

        $config_content = file_get_contents($config_path);
        $config = json_decode($config_content, true);

        if (!is_array($config)) {
            return $this->error_response('Invalid config file format', 'invalid_json', 400);
        }

        if (isset($config['domains']) && is_array($config['domains'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vaptsecure_domains';
            
            $wpdb->query("TRUNCATE TABLE $table_name");
            
            foreach ($config['domains'] as $domain) {
                $wpdb->insert($table_name, array(
                    'domain' => $domain['domain'] ?? '',
                    'features' => is_array($domain['features']) ? json_encode($domain['features']) : $domain['features']
                ));
            }
        }

        if (isset($config['global_enforcement']) && is_array($config['global_enforcement'])) {
            update_option('vaptsecure_global_enforcement', $config['global_enforcement']);
        }

        $this->log_security_event('config_synced_from_file', array(
            'path' => $config_path,
            'domains_count' => count($config['domains'] ?? []),
            'has_global_enforcement' => isset($config['global_enforcement'])
        ));

        return $this->success_response(array(
            'domains_synced' => count($config['domains'] ?? []),
            'global_enforcement_synced' => isset($config['global_enforcement'])
        ), 'Config synced from file successfully');
    }
}