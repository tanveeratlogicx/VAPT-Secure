<?php
$wp_load = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once($wp_load);

global $wpdb;
$table_status = $wpdb->prefix . 'vaptsecure_feature_status';
$table_meta = $wpdb->prefix . 'vaptsecure_feature_meta';

$features = $wpdb->get_results("
    SELECT s.feature_key, s.status, m.is_enabled, m.implementation_data, m.generated_schema
    FROM $table_status s
    LEFT JOIN $table_meta m ON s.feature_key = m.feature_key
    WHERE s.status IN ('Develop', 'Test', 'Release')
", ARRAY_A);

header('Content-Type: application/json');
echo json_encode($features, JSON_PRETTY_PRINT);
