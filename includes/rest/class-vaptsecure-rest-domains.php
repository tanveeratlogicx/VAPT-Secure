<?php

/**
 * Domains REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_Domains extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/domains', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_domains'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/domains/update', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_domain'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/domains/delete', array(
            'methods'  => 'POST',
            'callback' => array($this, 'delete_domain'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/domains/batch-delete', array(
            'methods'  => 'POST',
            'callback' => array($this, 'batch_delete_domains'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/domains/update-features', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_domain_features'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    public function get_domains()
    {
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'vaptsecure_domains';
            
            $domains = $wpdb->get_results("SELECT * FROM $table ORDER BY domain_name", ARRAY_A);
            
            if (!is_array($domains)) {
                $domains = array();
            }
            
            foreach ($domains as &$domain) {
                $domain['features'] = isset($domain['features']) ? json_decode($domain['features'], true) : array();
                if (!is_array($domain['features'])) {
                    $domain['features'] = array();
                }
                
                $domain['settings'] = isset($domain['settings']) ? json_decode($domain['settings'], true) : array();
                if (!is_array($domain['settings'])) {
                    $domain['settings'] = array();
                }
            }
            
            return $this->success_response(array('domains' => $domains));
            
        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Domains::get_domains ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to load domains: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function update_domain($request)
    {
        try {
            $params = $request->get_json_params();
            $domain_id = isset($params['id']) ? intval($params['id']) : 0;
            $domain_name = isset($params['domain_name']) ? sanitize_text_field($params['domain_name']) : '';
            $settings = isset($params['settings']) ? $params['settings'] : array();
            
            if (empty($domain_name)) {
                return $this->error_response('Domain name is required', 'missing_domain_name', 400);
            }
            
            if (!filter_var($domain_name, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                return $this->error_response('Invalid domain name format', 'invalid_domain', 400);
            }
            
            $sanitized_settings = array();
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    if (in_array($key, array('ssl_enabled', 'cdn_enabled', 'waf_enabled'))) {
                        $sanitized_settings[$key] = boolval($value);
                    } else {
                        $sanitized_settings[$key] = sanitize_text_field($value);
                    }
                }
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'vaptsecure_domains';
            
            $domain_data = array(
                'domain_name' => $domain_name,
                'settings' => json_encode($sanitized_settings),
                'updated_at' => current_time('mysql')
            );
            
            if ($domain_id > 0) {
                $result = $wpdb->update($table, $domain_data, array('id' => $domain_id));
            } else {
                $domain_data['created_at'] = current_time('mysql');
                $result = $wpdb->insert($table, $domain_data);
                $domain_id = $wpdb->insert_id;
            }
            
            if ($result !== false) {
                $this->log_action('update_domain', array(
                    'domain_id' => $domain_id,
                    'domain_name' => $domain_name,
                    'action' => ($domain_id > 0) ? 'updated' : 'created'
                ));
                
                return $this->success_response(
                    array(
                        'domain_id' => $domain_id,
                        'action' => ($domain_id > 0) ? 'updated' : 'created'
                    ),
                    'Domain ' . (($domain_id > 0) ? 'updated' : 'created') . ' successfully'
                );
            } else {
                return $this->error_response('Failed to save domain', 'save_failed', 500);
            }
            
        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Domains::update_domain ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to update domain: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function delete_domain($request)
    {
        try {
            $params = $request->get_json_params();
            $domain_id = isset($params['id']) ? intval($params['id']) : 0;
            
            if ($domain_id <= 0) {
                return $this->error_response('Valid domain ID is required', 'invalid_id', 400);
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'vaptsecure_domains';
            
            $domain = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $domain_id), ARRAY_A);
            
            if (!$domain) {
                return $this->error_response('Domain not found', 'not_found', 404);
            }
            
            $result = $wpdb->delete($table, array('id' => $domain_id));
            
            if ($result) {
                $this->log_action('delete_domain', array(
                    'domain_id' => $domain_id,
                    'domain_name' => $domain['domain_name']
                ));
                
                return $this->success_response(
                    array('deleted' => true),
                    'Domain deleted successfully'
                );
            } else {
                return $this->error_response('Failed to delete domain', 'delete_failed', 500);
            }
            
        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Domains::delete_domain ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to delete domain: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function batch_delete_domains($request)
    {
        try {
            $params = $request->get_json_params();
            $domain_ids = isset($params['ids']) ? $params['ids'] : array();
            
            if (empty($domain_ids) || !is_array($domain_ids)) {
                return $this->error_response('Domain IDs array is required', 'missing_ids', 400);
            }
            
            $sanitized_ids = array_map('intval', $domain_ids);
            $sanitized_ids = array_filter($sanitized_ids, function($id) { return $id > 0; });
            
            if (empty($sanitized_ids)) {
                return $this->error_response('No valid domain IDs provided', 'no_valid_ids', 400);
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'vaptsecure_domains';
            
            $ids_string = implode(',', $sanitized_ids);
            $domains = $wpdb->get_results("SELECT id, domain_name FROM $table WHERE id IN ($ids_string)", ARRAY_A);
            
            $deleted_count = 0;
            $failed_ids = array();
            
            foreach ($sanitized_ids as $domain_id) {
                $result = $wpdb->delete($table, array('id' => $domain_id));
                if ($result) {
                    $deleted_count++;
                } else {
                    $failed_ids[] = $domain_id;
                }
            }
            
            $this->log_action('batch_delete_domains', array(
                'domain_ids' => $sanitized_ids,
                'deleted_count' => $deleted_count,
                'failed_ids' => $failed_ids
            ));
            
            $response_data = array(
                'deleted_count' => $deleted_count,
                'total_requested' => count($sanitized_ids),
                'failed_ids' => $failed_ids
            );
            
            $message = "Deleted {$deleted_count} domain(s)";
            if (!empty($failed_ids)) {
                $message .= ", failed to delete " . count($failed_ids) . " domain(s)";
            }
            
            return $this->success_response($response_data, $message);
            
        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Domains::batch_delete_domains ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to delete domains: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function update_domain_features($request)
    {
        try {
            $params = $request->get_json_params();
            $domain_id = isset($params['id']) ? intval($params['id']) : 0;
            $feature_keys = isset($params['features']) ? $params['features'] : array();
            
            if ($domain_id <= 0) {
                return $this->error_response('Valid domain ID is required', 'invalid_id', 400);
            }
            
            if (!is_array($feature_keys)) {
                return $this->error_response('Features must be an array', 'invalid_features', 400);
            }
            
            $sanitized_features = array_map('sanitize_text_field', $feature_keys);
            
            global $wpdb;
            $table = $wpdb->prefix . 'vaptsecure_domains';
            
            $domain = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $domain_id), ARRAY_A);
            
            if (!$domain) {
                return $this->error_response('Domain not found', 'not_found', 404);
            }
            
            $current_features = isset($domain['features']) ? json_decode($domain['features'], true) : array();
            if (!is_array($current_features)) {
                $current_features = array();
            }
            
            $merged_features = array_unique(array_merge($current_features, $sanitized_features));
            
            $result = $wpdb->update(
                $table,
                array(
                    'features' => json_encode($merged_features),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $domain_id)
            );
            
            if ($result !== false) {
                $this->log_action('update_domain_features', array(
                    'domain_id' => $domain_id,
                    'domain_name' => $domain['domain_name'],
                    'added_features' => $sanitized_features,
                    'total_features' => count($merged_features)
                ));
                
                return $this->success_response(
                    array(
                        'updated' => true,
                        'total_features' => count($merged_features),
                        'added_count' => count($sanitized_features)
                    ),
                    'Domain features updated successfully'
                );
            } else {
                return $this->error_response('Failed to update domain features', 'update_failed', 500);
            }
            
        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Domains::update_domain_features ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to update domain features: ' . $e->getMessage(), 'internal_error', 500);
        }
    }
}