<?php

/**
 * VAPT Admin Interface
 */

if (! defined('ABSPATH')) {
  exit;
}

class VAPTSECURE_Admin
{

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_notices', array($this, 'show_nginx_notice'));
    add_action('admin_notices', array($this, 'show_block_notifications'));
  }

  /**
   * Show recent block notifications recorded by the enforcement engine.
   */
  public function show_block_notifications()
  {
    if (!current_user_can('manage_options')) return;

    $events = get_transient('vaptsecure_block_events');
    if (empty($events) || !is_array($events)) {
      return;
    }

    foreach ($events as $e) {
      $time = isset($e['time']) ? esc_html($e['time']) : esc_html(current_time('mysql'));
      $reason = isset($e['reason']) ? esc_html($e['reason']) : 'Protection Triggered';
      $details = '';
      if (isset($e['uri'])) $details .= ' URI: ' . esc_html($e['uri']);
      if (isset($e['file'])) $details .= ' File: ' . esc_html($e['file']);
      if (isset($e['query'])) $details .= ' Query: ' . esc_html($e['query']);

?>
      <div class="notice notice-warning is-dismissible">
        <p><strong>VAPT Secure blocked: <?php echo $reason; ?></strong></p>
        <p><?php echo $time; ?><?php if (!empty($details)) echo ' - ' . $details; ?></p>
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=vaptsecure-domain-admin')); ?>">Open VAPT Secure Dashboard</a></p>
      </div>
<?php
    }

    // Clear transient after displaying to avoid repeat notices
    delete_transient('vaptsecure_block_events');
  }

  public function show_nginx_notice()
  {
    if (!is_vaptsecure_superadmin()) return;

    $server = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : '';
    if (strpos($server, 'nginx') === false) return;

    $upload_dir = wp_upload_dir();
    $rules_file = $upload_dir['basedir'] . '/vapt-nginx-rules.conf';

    if (file_exists($rules_file)) {
      $include_path = $rules_file;
?>
      <div class="notice notice-info is-dismissible">
        <p><strong>VAPT Nginx Configuration (Action Required)</strong></p>
        <p>To apply VAPT security rules on Nginx, you must include the generated rules file in your main <code>nginx.conf</code> server block:</p>
        <code style="display:block; padding:10px; background:#fff; margin:5px 0;">include <?php echo esc_html($include_path); ?>;</code>
        <p><em>After adding this line, restart Nginx to apply changes.</em></p>
      </div>
<?php
    }
  }



  public function add_admin_menu() {}

  public function enqueue_scripts($hook)
  {
    if ($hook !== 'toplevel_page_vapt-auditor' && $hook !== 'vaptsecure_page_vapt-auditor') {
      return;
    }

    // 1. Enqueue Dependencies
    wp_enqueue_script('vapt-interface-generator', VAPTSECURE_URL . 'assets/js/modules/interface-generator.js', array(), VAPTSECURE_VERSION, true);
    wp_enqueue_script('vapt-generated-interface-ui', VAPTSECURE_URL . 'assets/js/modules/generated-interface.js', array('wp-element', 'wp-components'), VAPTSECURE_VERSION, true);

    // 2. Enqueue Admin Dashboard Script with full dependency block
    wp_enqueue_script(
      'vapt-admin-js',
      VAPTSECURE_URL . 'assets/js/admin.js',
      array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'vapt-interface-generator', 'vapt-generated-interface-ui'),
      VAPTSECURE_VERSION,
      true
    );

    wp_enqueue_style('vapt-admin-css', VAPTSECURE_URL . 'assets/css/admin.css', array('wp-components'), VAPTSECURE_VERSION);

    wp_localize_script('vapt-admin-js', 'vaptSecureSettings', array(
      'root' => esc_url_raw(rest_url()),
      'homeUrl' => esc_url_raw(home_url()),
      'nonce' => wp_create_nonce('wp_rest'),
      'isSuper' => is_vaptsecure_superadmin(),
      'pluginVersion' => VAPTSECURE_VERSION
    ));

    wp_localize_script('vapt-admin-js', 'vaptsecure_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('vaptsecure_scan_nonce')
    ));
  }


  public function admin_page()
  {
    wp_die(__('The VAPT Auditor has been removed.', 'vaptsecure'));
  }
}
