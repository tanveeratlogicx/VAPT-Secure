<?php

/**
 * Security REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_Security extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/security/stats', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_security_stats'),
            'permission_callback' => array($this, 'check_read_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/security/logs', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_security_logs'),
            'permission_callback' => array($this, 'check_read_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/clear-cache', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'clear_enforcement_cache'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/reset-limit', array(
            'methods' => 'POST',
            'callback' => array($this, 'reset_rate_limit'),
            'permission_callback' => '__return_true',
            )
        );
    }

    public function get_security_stats($request)
    {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'vaptsecure_security_stats';
        $logs_table = $wpdb->prefix . 'vaptsecure_security_logs';
        $features_table = $wpdb->prefix . 'vaptsecure_feature_status';

        $total_events = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
        $events_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $logs_table WHERE DATE(timestamp) = %s",
            current_time('Y-m-d')
        ));

        $event_types = $wpdb->get_results(
            "SELECT event_type, COUNT(*) as count FROM $logs_table GROUP BY event_type ORDER BY count DESC",
            ARRAY_A
        );

        $feature_stats = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $features_table GROUP BY status",
            ARRAY_A
        );

        $status_distribution = array();
        foreach ($feature_stats as $stat) {
            $status_distribution[$stat['status']] = (int) $stat['count'];
        }

        $top_events = array_slice($event_types, 0, 10);

        $cache_stats = array(
            'apc_enabled' => function_exists('apc_cache_info'),
            'opcache_enabled' => function_exists('opcache_get_status'),
            'transients_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'")
        );

        return $this->success_response(array(
            'events' => array(
                'total' => (int) $total_events,
                'today' => (int) $events_today,
                'by_type' => $event_types,
                'top_events' => $top_events
            ),
            'features' => array(
                'status_distribution' => $status_distribution,
                'total_features' => array_sum($status_distribution)
            ),
            'cache' => $cache_stats,
            'environment' => $this->get_current_environment_profile()
        ));
    }

    public function get_security_logs($request)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vaptsecure_security_logs';

        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(10, (int) ($request->get_param('per_page') ?? 50)));
        $offset = ($page - 1) * $per_page;

        $event_type = $request->get_param('event_type');
        $user_id = $request->get_param('user_id');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $where = array('1=1');
        $prepare_args = array();

        if (!empty($event_type)) {
            $where[] = 'event_type = %s';
            $prepare_args[] = sanitize_text_field($event_type);
        }

        if (!empty($user_id) && is_numeric($user_id)) {
            $where[] = 'user_id = %d';
            $prepare_args[] = (int) $user_id;
        }

        if (!empty($start_date)) {
            $where[] = 'timestamp >= %s';
            $prepare_args[] = sanitize_text_field($start_date);
        }

        if (!empty($end_date)) {
            $where[] = 'timestamp <= %s';
            $prepare_args[] = sanitize_text_field($end_date);
        }

        $where_clause = implode(' AND ', $where);

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
            $prepare_args
        ));

        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $prepare_args[] = $per_page;
        $prepare_args[] = $offset;

        $logs = $wpdb->get_results($wpdb->prepare($query, $prepare_args), ARRAY_A);

        foreach ($logs as &$log) {
            if (isset($log['details']) && !empty($log['details'])) {
                $log['details'] = json_decode($log['details'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $log['details'] = array('raw' => $log['details']);
                }
            }

            if ($log['user_id']) {
                $user = get_userdata($log['user_id']);
                $log['username'] = $user ? $user->user_login : 'Unknown';
                $log['user_email'] = $user ? $user->user_email : '';
            }
        }

        return $this->success_response(array(
            'logs' => $logs,
            'pagination' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $total,
                'total_pages' => ceil($total / $per_page)
            ),
            'filters' => array(
                'event_type' => $event_type,
                'user_id' => $user_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            )
        ));
    }

    public function clear_enforcement_cache($request)
    {
        global $wpdb;

        $cache_type = $request->get_param('cache_type') ?? 'all';

        $cleared = array();

        if ($cache_type === 'all' || $cache_type === 'transients') {
            $transients = $wpdb->get_col(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_vaptsecure_%' OR option_name LIKE '_transient_timeout_vaptsecure_%'"
            );

            foreach ($transients as $transient) {
                $name = str_replace('_transient_', '', $transient);
                $name = str_replace('_transient_timeout_', '', $name);
                delete_transient($name);
            }

            $cleared['transients'] = count($transients);
        }

        if ($cache_type === 'all' || $cache_type === 'options') {
            $options = array(
                'vaptsecure_cached_pattern_library',
                'vaptsecure_environment_profile',
                'vaptsecure_license_cache'
            );

            foreach ($options as $option) {
                delete_option($option);
            }

            $cleared['options'] = count($options);
        }

        if ($cache_type === 'all' || $cache_type === 'object_cache') {
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
                $cleared['object_cache'] = true;
            }
        }

        $this->log_security_event('cache_cleared', array(
            'cache_type' => $cache_type,
            'cleared_items' => $cleared
        ));

        return $this->success_response(array(
            'cache_type' => $cache_type,
            'cleared' => $cleared
        ), 'Cache cleared successfully');
    }

    public function reset_rate_limit($request)
    {
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $rate_limit_key = 'vaptsecure_rate_limit_' . md5($client_ip);
        delete_transient($rate_limit_key);

        $this->log_security_event('rate_limit_reset', array(
            'client_ip' => $client_ip
        ));

        return $this->success_response(array(
            'client_ip' => $client_ip,
            'reset' => true
        ), 'Rate limit reset for your IP address');
    }
}