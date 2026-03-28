<?php

/**
 * Features REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_Features extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/features', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_features'),
            'permission_callback' => array($this, 'check_read_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/update', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_feature'),
            'permission_callback' => array($this, 'check_read_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/transition', array(
            'methods'  => 'POST',
            'callback' => array($this, 'transition_feature'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/verify', array(
            'methods'  => 'POST',
            'callback' => array($this, 'verify_implementation'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/batch-revert', array(
            'methods'  => 'POST',
            'callback' => array($this, 'batch_revert_to_draft'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/preview-revert', array(
            'methods'  => 'POST',
            'callback' => array($this, 'preview_revert_to_draft'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/history', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_feature_history'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/stats', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_feature_stats'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/stats/reset', array(
            'methods'  => 'POST',
            'callback' => array($this, 'reset_feature_stats'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    public function get_features($request)
    {
        try {
            $default_file = defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : 'interface_schema_v2.0.json';
            $requested_file = $request->get_param('file') ?: $default_file;

            $files_to_load = [];
            if ($requested_file === '__all__') {
                $data_dir = VAPTSECURE_PATH . 'data';
                if (is_dir($data_dir)) {
                    $all_json = array_filter(
                        scandir($data_dir), function ($f) {
                            return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'json';
                        }
                    );
                    $hidden_files = get_option('vaptsecure_hidden_json_files', array());
                    $removed_files = get_option('vaptsecure_removed_json_files', array());
                    $hidden_normalized = array_map('sanitize_file_name', $hidden_files);
                    $removed_normalized = array_map('sanitize_file_name', $removed_files);

                    foreach ($all_json as $f) {
                            $normalized = sanitize_file_name($f);
                        if (!in_array($normalized, $hidden_normalized) && !in_array($normalized, $removed_normalized)) {
                            $files_to_load[] = $f;
                        }
                    }
                }
            } else {
                $files_to_load = array_filter(explode(',', $requested_file));
            }

            $files_to_load = array_filter(
                $files_to_load, function ($f) {
                    return file_exists(VAPTSECURE_PATH . 'data/' . sanitize_file_name($f));
                }
            );

            $statuses = VAPTSECURE_DB::get_feature_statuses_full();
            $status_map = [];
            foreach ($statuses as $row) {
                  $status_map[$row['feature_key']] = array(
                    'status' => $row['status'],
                    'implemented_at' => $row['implemented_at'],
                    'assigned_to' => $row['assigned_to']
                  );
            }

            global $wpdb;
            $history_table = $wpdb->prefix . 'vaptsecure_feature_history';
            $history_counts = $wpdb->get_results("SELECT feature_key, COUNT(*) as count FROM $history_table GROUP BY feature_key", OBJECT_K);

            $is_superadmin = is_vaptsecure_superadmin();
            $scope = $request->get_param('scope');
            $severity_param = $request->get_param('severity');

            $features = [];
            $schema = [];
            $merged_features = [];
            $design_prompt = null;
            $ai_agent_instructions = null;
            $global_settings = null;

            include_once VAPTSECURE_PATH . 'includes/class-vaptsecure-environment-detector.php';
            $detector = new VAPTSECURE_Environment_Detector();
      
            if ($request->get_param('redetect')) {
                $environment_profile = $detector->redetect();
            } else {
                $environment_profile = $detector->detect();
            }

            foreach ($files_to_load as $file) {
                $json_path = VAPTSECURE_PATH . 'data/' . sanitize_file_name($file);
                if (! file_exists($json_path)) { continue;
                }

                $content = file_get_contents($json_path);
                $raw_data = json_decode($content, true);
                if (! is_array($raw_data)) { continue;
                }

                if (!$design_prompt && isset($raw_data['design_prompt'])) { $design_prompt = $raw_data['design_prompt'];
                }
                if (!$ai_agent_instructions && isset($raw_data['ai_agent_instructions'])) { $ai_agent_instructions = $raw_data['ai_agent_instructions'];
                }
                if (!$global_settings && isset($raw_data['global_settings'])) { $global_settings = $raw_data['global_settings'];
                }

                $current_features = [];
                $current_schema = [];

                if (isset($raw_data['wordpress_vapt']) && is_array($raw_data['wordpress_vapt'])) {
                    $current_features = $raw_data['wordpress_vapt'];
                    $current_schema = isset($raw_data['schema']) ? $raw_data['schema'] : [];
                }

                if (empty($current_features) && isset($raw_data['features']) && is_array($raw_data['features'])) {
                    $current_features = $raw_data['features'];
                }

                if (empty($current_schema) && isset($raw_data['schema']) && is_array($raw_data['schema'])) {
                    $current_schema = $raw_data['schema'];
                }

                if (isset($raw_data['interface_schema']) && is_array($raw_data['interface_schema'])) {
                    $current_schema = $raw_data['interface_schema'];
                }

                if (!is_array($current_schema)) {
                    $current_schema = [];
                }

                if (is_array($current_schema) && !empty($current_schema)) {
                    $schema = array_merge($schema, $current_schema);
                }

                $enabled_features = get_option('vaptsecure_enabled_features', array());
                $enabled_features = is_array($enabled_features) ? $enabled_features : [];

                $filtered_features = array_filter(
                    $current_features, function ($f) use ($enabled_features, $is_superadmin) {
                        $key = isset($f['key']) ? $f['key'] : '';
                        return $is_superadmin || in_array($key, $enabled_features);
                    }
                );

                $severity_values = array(
                    'critical' => 4,
                    'high' => 3,
                    'medium' => 2,
                    'low' => 1,
                    '' => 0
                );

                usort(
                    $filtered_features, function ($a, $b) use ($severity_values) {
                        $severity_a = isset($a['severity']) ? strtolower($a['severity']) : '';
                        $severity_b = isset($b['severity']) ? strtolower($b['severity']) : '';
                        $val_a = isset($severity_values[$severity_a]) ? $severity_values[$severity_a] : 0;
                        $val_b = isset($severity_values[$severity_b]) ? $severity_values[$severity_b] : 0;
                        return $val_b - $val_a;
                    }
                );

                foreach ($filtered_features as $f) {
                    $key = isset($f['key']) ? $f['key'] : '';
                    $label = isset($f['label']) ? $f['label'] : '';
                    $normalized_label = strtolower(trim($label));

                    if (!isset($merged_features[$normalized_label])) {
                        $f['file'] = $file;
                        $f['status'] = isset($status_map[$key]) ? $status_map[$key]['status'] : 'draft';
                        $f['implemented_at'] = isset($status_map[$key]) ? $status_map[$key]['implemented_at'] : null;
                        $f['assigned_to'] = isset($status_map[$key]) ? $status_map[$key]['assigned_to'] : null;
                        $f['history_count'] = isset($history_counts[$key]) ? $history_counts[$key]->count : 0;
                        $f['strategy_analysis'] = self::analyze_enforcement_strategy($f, $key);

                        $merged_features[$normalized_label] = $f;
                    } else {
                        $existing = $merged_features[$normalized_label];
                        $merged_features[$normalized_label] = array_merge($existing, $f);
                    }
                }
            }

            $final_features = array_values($merged_features);

            if ($scope === 'enabled') {
                $final_features = array_filter(
                    $final_features, function ($f) {
                        return isset($f['status']) && $f['status'] === 'implemented';
                    }
                );
            } elseif ($scope === 'assigned') {
                $user_id = get_current_user_id();
                $final_features = array_filter(
                    $final_features, function ($f) use ($user_id) {
                        return isset($f['assigned_to']) && $f['assigned_to'] == $user_id;
                    }
                );
            }

            if ($severity_param) {
                $final_features = array_filter(
                    $final_features, function ($f) use ($severity_param) {
                        $feature_severity = isset($f['severity']) ? strtolower($f['severity']) : '';
                        return $feature_severity === strtolower($severity_param);
                    }
                );
            }

            $response_data = array(
                'features' => array_values($final_features),
                'schema' => $schema,
                'design_prompt' => $design_prompt,
                'ai_agent_instructions' => $ai_agent_instructions,
                'global_settings' => $global_settings,
                'environment_profile' => $environment_profile,
                'total' => count($final_features)
            );

            return $this->success_response($response_data);

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::get_features ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to load features: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function update_feature($request)
    {
        try {
            $params = $request->get_json_params();
            $feature_key = isset($params['key']) ? sanitize_text_field($params['key']) : '';
            $updates = isset($params['updates']) ? $params['updates'] : array();

            if (empty($feature_key)) {
                return $this->error_response('Feature key is required', 'missing_key', 400);
            }

            if (empty($updates) || !is_array($updates)) {
                return $this->error_response('Updates must be a non-empty array', 'invalid_updates', 400);
            }

            $allowed_fields = array('label', 'description', 'severity', 'recommendation', 'implementation_notes');
            $sanitized_updates = array();

            foreach ($updates as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    if ($field === 'implementation_notes') {
                        $sanitized_updates[$field] = sanitize_textarea_field($value);
                    } else {
                        $sanitized_updates[$field] = sanitize_text_field($value);
                    }
                }
            }

            if (empty($sanitized_updates)) {
                return $this->error_response('No valid fields to update', 'no_valid_fields', 400);
            }

            $result = VAPTSECURE_DB::update_feature($feature_key, $sanitized_updates);

            if ($result) {
                $this->log_action('update_feature', array('feature_key' => $feature_key, 'updates' => $sanitized_updates));
                return $this->success_response(array('updated' => true), 'Feature updated successfully');
            } else {
                return $this->error_response('Failed to update feature', 'update_failed', 500);
            }

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::update_feature ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to update feature: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function transition_feature($request)
    {
        try {
            $params = $request->get_json_params();
            $feature_key = isset($params['key']) ? sanitize_text_field($params['key']) : '';
            $new_status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
            $assigned_to = isset($params['assigned_to']) ? intval($params['assigned_to']) : null;

            if (empty($feature_key) || empty($new_status)) {
                return $this->error_response('Feature key and status are required', 'missing_params', 400);
            }

            $valid_statuses = array('draft', 'in_progress', 'implemented', 'verified', 'rejected');
            if (!in_array($new_status, $valid_statuses)) {
                return $this->error_response('Invalid status value', 'invalid_status', 400);
            }

            $current_status = VAPTSECURE_DB::get_feature_status($feature_key);
            $implemented_at = ($new_status === 'implemented') ? current_time('mysql') : null;

            $update_data = array(
                'status' => $new_status,
                'implemented_at' => $implemented_at
            );

            if ($assigned_to !== null) {
                $update_data['assigned_to'] = $assigned_to;
            }

            $result = VAPTSECURE_DB::update_feature_status($feature_key, $update_data);

            if ($result) {
                $history_data = array(
                    'feature_key' => $feature_key,
                    'from_status' => $current_status,
                    'to_status' => $new_status,
                    'assigned_to' => $assigned_to,
                    'changed_by' => get_current_user_id()
                );
                VAPTSECURE_DB::add_feature_history($history_data);

                $this->log_action('transition_feature', array(
                    'feature_key' => $feature_key,
                    'from_status' => $current_status,
                    'to_status' => $new_status,
                    'assigned_to' => $assigned_to
                ));

                return $this->success_response(array('transitioned' => true), 'Feature status updated successfully');
            } else {
                return $this->error_response('Failed to update feature status', 'transition_failed', 500);
            }

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::transition_feature ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to transition feature: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function verify_implementation($request)
    {
        try {
            $params = $request->get_json_params();
            $feature_key = isset($params['key']) ? sanitize_text_field($params['key']) : '';
            $verification_data = isset($params['verification_data']) ? $params['verification_data'] : array();

            if (empty($feature_key)) {
                return $this->error_response('Feature key is required', 'missing_key', 400);
            }

            $current_status = VAPTSECURE_DB::get_feature_status($feature_key);
            if ($current_status !== 'implemented') {
                return $this->error_response('Feature must be in implemented status to verify', 'invalid_status', 400);
            }

            $result = VAPTSECURE_DB::update_feature_status($feature_key, array('status' => 'verified'));

            if ($result) {
                $history_data = array(
                    'feature_key' => $feature_key,
                    'from_status' => 'implemented',
                    'to_status' => 'verified',
                    'verification_data' => $verification_data,
                    'changed_by' => get_current_user_id()
                );
                VAPTSECURE_DB::add_feature_history($history_data);

                $this->log_action('verify_implementation', array(
                    'feature_key' => $feature_key,
                    'verification_data' => $verification_data
                ));

                return $this->success_response(array('verified' => true), 'Feature verification completed successfully');
            } else {
                return $this->error_response('Failed to verify feature', 'verification_failed', 500);
            }

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::verify_implementation ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to verify implementation: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function batch_revert_to_draft($request)
    {
        try {
            $params = $request->get_json_params();
            $feature_keys = isset($params['keys']) ? $params['keys'] : array();

            if (empty($feature_keys) || !is_array($feature_keys)) {
                return $this->error_response('Feature keys array is required', 'missing_keys', 400);
            }

            $sanitized_keys = array_map('sanitize_text_field', $feature_keys);
            $results = array();

            foreach ($sanitized_keys as $key) {
                $current_status = VAPTSECURE_DB::get_feature_status($key);
                $result = VAPTSECURE_DB::update_feature_status($key, array('status' => 'draft', 'implemented_at' => null));

                if ($result) {
                    $history_data = array(
                        'feature_key' => $key,
                        'from_status' => $current_status,
                        'to_status' => 'draft',
                        'changed_by' => get_current_user_id()
                    );
                    VAPTSECURE_DB::add_feature_history($history_data);
                    $results[$key] = true;
                } else {
                    $results[$key] = false;
                }
            }

            $success_count = count(array_filter($results));
            $this->log_action('batch_revert', array('feature_keys' => $sanitized_keys, 'success_count' => $success_count));

            return $this->success_response(
                array(
                    'results' => $results,
                    'total' => count($sanitized_keys),
                    'successful' => $success_count
                ),
                "Reverted {$success_count} features to draft status"
            );

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::batch_revert_to_draft ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to revert features: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function preview_revert_to_draft($request)
    {
        try {
            $params = $request->get_json_params();
            $feature_keys = isset($params['keys']) ? $params['keys'] : array();

            if (empty($feature_keys) || !is_array($feature_keys)) {
                return $this->error_response('Feature keys array is required', 'missing_keys', 400);
            }

            $sanitized_keys = array_map('sanitize_text_field', $feature_keys);
            $preview_data = array();

            foreach ($sanitized_keys as $key) {
                $current_status = VAPTSECURE_DB::get_feature_status($key);
                $feature_data = VAPTSECURE_DB::get_feature_data($key);

                $preview_data[$key] = array(
                    'current_status' => $current_status,
                    'feature_data' => $feature_data,
                    'will_be_reverted' => ($current_status !== 'draft')
                );
            }

            return $this->success_response(
                array('preview' => $preview_data),
                'Preview of features to be reverted'
            );

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::preview_revert_to_draft ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to generate preview: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function get_feature_history($request)
    {
        try {
            $feature_key = $request->get_param('key');
            $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 50;
            $offset = $request->get_param('offset') ? intval($request->get_param('offset')) : 0;

            if (empty($feature_key)) {
                return $this->error_response('Feature key is required', 'missing_key', 400);
            }

            $history = VAPTSECURE_DB::get_feature_history($feature_key, $limit, $offset);

            return $this->success_response(
                array(
                    'history' => $history,
                    'total' => count($history)
                )
            );

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::get_feature_history ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to load feature history: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function get_feature_stats($request)
    {
        try {
            global $wpdb;
            $status_table = $wpdb->prefix . 'vaptsecure_feature_status';
            $history_table = $wpdb->prefix . 'vaptsecure_feature_history';

            $status_counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $status_table GROUP BY status", ARRAY_A);
            $recent_activity = $wpdb->get_results("SELECT * FROM $history_table ORDER BY changed_at DESC LIMIT 10", ARRAY_A);

            $stats = array(
                'status_counts' => array(),
                'total_features' => 0,
                'recent_activity' => $recent_activity
            );

            foreach ($status_counts as $row) {
                $stats['status_counts'][$row['status']] = intval($row['count']);
                $stats['total_features'] += intval($row['count']);
            }

            return $this->success_response(array('stats' => $stats));

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::get_feature_stats ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to load feature stats: ' . $e->getMessage(), 'internal_error', 500);
        }
    }

    public function reset_feature_stats($request)
    {
        try {
            $params = $request->get_json_params();
            $feature_key = isset($params['key']) ? sanitize_text_field($params['key']) : '';

            if (empty($feature_key)) {
                return $this->error_response('Feature key is required', 'missing_key', 400);
            }

            global $wpdb;
            $history_table = $wpdb->prefix . 'vaptsecure_feature_history';
            $deleted = $wpdb->delete($history_table, array('feature_key' => $feature_key));

            $this->log_action('reset_feature_stats', array('feature_key' => $feature_key, 'deleted_count' => $deleted));

            return $this->success_response(
                array('reset' => true, 'deleted_count' => $deleted),
                'Feature history reset successfully'
            );

        } catch (Exception $e) {
            error_log('VAPTSECURE_REST_Features::reset_feature_stats ERROR: ' . $e->getMessage());
            return $this->error_response('Failed to reset feature stats: ' . $e->getMessage(), 'internal_error', 500);
        }
    }
}