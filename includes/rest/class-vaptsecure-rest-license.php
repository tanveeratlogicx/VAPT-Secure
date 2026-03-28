<?php

/**
 * License REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_License extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/license/status', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_license_status'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );
        
        register_rest_route(
            'vaptsecure/v1', '/license/restore', array(
                'methods' => 'POST',
                'callback' => array($this, 'restore_license_cache'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );
        
        register_rest_route(
            'vaptsecure/v1', '/license/check', array(
                'methods' => 'POST',
                'callback' => array($this, 'force_license_check'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    public function get_license_status($request)
    {
        $cached = get_option('vaptsecure_license_cache', array());
        $force_refresh = $request->get_param('refresh') === 'true';

        if (empty($cached) || $force_refresh) {
            $status = $this->check_license_remote();
            update_option('vaptsecure_license_cache', $status);
            $cached = $status;
        }

        $installations = $this->get_current_installations();
        $max_installations = $cached['max_installations'] ?? 1;
        $usage_percentage = $max_installations > 0 ? round(($installations / $max_installations) * 100, 2) : 0;

        $response = array(
            'valid' => $cached['valid'] ?? false,
            'license_key' => isset($cached['license_key']) ? $this->mask_license_key($cached['license_key']) : null,
            'license_type' => $cached['license_type'] ?? 'unknown',
            'expires_at' => $cached['expires_at'] ?? null,
            'max_installations' => $max_installations,
            'current_installations' => $installations,
            'usage_percentage' => $usage_percentage,
            'product_name' => $cached['product_name'] ?? 'VAPT Secure',
            'customer_name' => $cached['customer_name'] ?? null,
            'customer_email' => $cached['customer_email'] ?? null,
            'last_checked' => $cached['last_checked'] ?? null,
            'is_trial' => $cached['is_trial'] ?? false,
            'days_remaining' => $this->calculate_days_remaining($cached['expires_at'] ?? null),
            'cached' => !$force_refresh && !empty($cached)
        );

        return $this->success_response($response);
    }

    public function restore_license_cache($request)
    {
        $backup_key = $request->get_param('backup_key');
        
        if (empty($backup_key)) {
            return $this->error_response('Backup key is required', 'missing_backup_key', 400);
        }

        $backup_data = get_option('vaptsecure_license_backup_' . md5($backup_key), array());
        
        if (empty($backup_data)) {
            return $this->not_found_response('No backup found for the provided key');
        }

        update_option('vaptsecure_license_cache', $backup_data);

        $this->log_security_event('license_cache_restored', array(
            'backup_key' => $backup_key,
            'license_type' => $backup_data['license_type'] ?? 'unknown'
        ));

        return $this->success_response(array(
            'restored' => true,
            'license_type' => $backup_data['license_type'] ?? 'unknown',
            'expires_at' => $backup_data['expires_at'] ?? null
        ), 'License cache restored from backup');
    }

    public function force_license_check($request)
    {
        $license_key = $request->get_param('license_key');
        
        if (empty($license_key)) {
            $cached = get_option('vaptsecure_license_cache', array());
            $license_key = $cached['license_key'] ?? '';
        }

        if (empty($license_key)) {
            return $this->error_response('No license key provided', 'missing_license_key', 400);
        }

        $status = $this->check_license_remote($license_key);
        update_option('vaptsecure_license_cache', $status);

        $backup_key = wp_generate_password(12, false);
        update_option('vaptsecure_license_backup_' . md5($backup_key), $status);

        $this->log_security_event('license_force_checked', array(
            'license_key' => $this->mask_license_key($license_key),
            'valid' => $status['valid'] ?? false,
            'license_type' => $status['license_type'] ?? 'unknown'
        ));

        $response = array(
            'valid' => $status['valid'] ?? false,
            'license_type' => $status['license_type'] ?? 'unknown',
            'expires_at' => $status['expires_at'] ?? null,
            'max_installations' => $status['max_installations'] ?? 1,
            'product_name' => $status['product_name'] ?? 'VAPT Secure',
            'customer_name' => $status['customer_name'] ?? null,
            'is_trial' => $status['is_trial'] ?? false,
            'days_remaining' => $this->calculate_days_remaining($status['expires_at'] ?? null),
            'backup_key' => $backup_key
        );

        return $this->success_response($response, 'License check completed');
    }

    private function check_license_remote($license_key = null)
    {
        if (empty($license_key)) {
            $cached = get_option('vaptsecure_license_cache', array());
            $license_key = $cached['license_key'] ?? '';
        }

        if (empty($license_key)) {
            return array(
                'valid' => false,
                'error' => 'No license key configured',
                'last_checked' => current_time('mysql')
            );
        }

        $api_url = 'https://api.vaptsecure.com/v1/license/check';
        $site_url = site_url();

        $response = wp_remote_post($api_url, array(
            'timeout' => 30,
            'body' => array(
                'license_key' => $license_key,
                'domain' => $site_url,
                'action' => 'check_license'
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'valid' => false,
                'error' => $response->get_error_message(),
                'license_key' => $license_key,
                'last_checked' => current_time('mysql')
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return array(
                'valid' => false,
                'error' => 'Invalid response from license server',
                'license_key' => $license_key,
                'last_checked' => current_time('mysql')
            );
        }

        $result = array(
            'valid' => $data['valid'] ?? false,
            'license_key' => $license_key,
            'license_type' => $data['license_type'] ?? 'unknown',
            'expires_at' => $data['expires_at'] ?? null,
            'max_installations' => $data['max_installations'] ?? 1,
            'product_name' => $data['product_name'] ?? 'VAPT Secure',
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'is_trial' => $data['is_trial'] ?? false,
            'last_checked' => current_time('mysql')
        );

        if (isset($data['error'])) {
            $result['error'] = $data['error'];
        }

        return $result;
    }

    private function get_current_installations()
    {
        global $wpdb;

        $installations = get_option('vaptsecure_installations', array());
        
        if (is_array($installations)) {
            $active_installations = array_filter($installations, function($installation) {
                $days_since = isset($installation['last_seen']) ? 
                    (time() - strtotime($installation['last_seen'])) / DAY_IN_SECONDS : 30;
                return $days_since < 30;
            });

            return count($active_installations);
        }

        return 1;
    }

    private function mask_license_key($license_key)
    {
        if (strlen($license_key) <= 8) {
            return '••••••••';
        }

        $first = substr($license_key, 0, 4);
        $last = substr($license_key, -4);
        $middle = str_repeat('•', strlen($license_key) - 8);

        return $first . $middle . $last;
    }

    private function calculate_days_remaining($expires_at)
    {
        if (empty($expires_at)) {
            return null;
        }

        $expires_timestamp = strtotime($expires_at);
        $current_timestamp = current_time('timestamp');

        if ($expires_timestamp <= $current_timestamp) {
            return 0;
        }

        $diff = $expires_timestamp - $current_timestamp;
        return floor($diff / DAY_IN_SECONDS);
    }
}