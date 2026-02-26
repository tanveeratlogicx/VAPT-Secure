<?php

/**
 * Centralized Security Logger for VAPT Secure
 */

if (! defined('ABSPATH')) {
  exit;
}

class VAPTSECURE_Logger
{
  /**
   * Log a security event
   * 
   * @param string $feature_key The feature that triggered the event
   * @param string $event_type  Type of event (block, detect, warning)
   * @param string $details     Additional context or notes
   */
  public static function log($feature_key, $event_type = 'block', $details = '')
  {
    global $wpdb;
    $table = $wpdb->prefix . 'vaptsecure_security_events';

    $ip = self::get_ip();
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    $data = array(
      'feature_key' => $feature_key,
      'event_type'  => $event_type,
      'ip_address'  => $ip,
      'request_uri' => $uri,
      'details'     => $details,
      'created_at'  => current_time('mysql'),
    );

    $wpdb->insert($table, $data, array('%s', '%s', '%s', '%s', '%s', '%s'));
  }

  /**
   * Get recent logs
   */
  public static function get_recent_logs($limit = 100)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'vaptsecure_security_events';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d", $limit), ARRAY_A);
  }

  /**
   * Get aggregate statistics
   */
  public static function get_stats_summary()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'vaptsecure_security_events';

    $total_blocks = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_type = 'block'");
    $top_risks = $wpdb->get_results("SELECT feature_key, COUNT(*) as count FROM $table GROUP BY feature_key ORDER BY count DESC LIMIT 5", ARRAY_A);

    return array(
      'total_blocks' => (int)$total_blocks,
      'top_risks'    => $top_risks,
    );
  }

  /**
   * Purge old logs based on retention setting
   */
  public static function prune_logs()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'vaptsecure_security_events';

    $retention_days = (int) get_option('vaptsecure_log_retention', 30);
    if (!in_array($retention_days, [30, 60, 90])) {
      $retention_days = 30;
    }

    $wpdb->query($wpdb->prepare(
      "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
      $retention_days
    ));
  }

  /**
   * Immediate clear of all logs
   */
  public static function clear_all()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'vaptsecure_security_events';
    return $wpdb->query("DELETE FROM $table");
  }

  /**
   * Helper: Get real IP
   */
  private static function get_ip()
  {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }
}
