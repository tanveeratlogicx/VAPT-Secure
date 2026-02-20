<?php

/**
 * VAPT Admin Interface
 */

if (! defined('ABSPATH')) {
  exit;
}

class VAPT_SECURE_Admin
{

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_notices', array($this, 'show_nginx_notice'));
  }

  public function show_nginx_notice()
  {
    if (!is_vapt_secure_superadmin()) return;

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
    if ($hook !== 'toplevel_page_vapt-auditor' && $hook !== 'vapt-secure_page_vapt-auditor') {
      return;
    }

    // 1. Enqueue Dependencies
    wp_enqueue_script('vapt-interface-generator', VAPT_SECURE_URL . 'assets/js/modules/interface-generator.js', array(), VAPT_SECURE_VERSION, true);
    wp_enqueue_script('vapt-generated-interface-ui', VAPT_SECURE_URL . 'assets/js/modules/generated-interface.js', array('wp-element', 'wp-components'), VAPT_SECURE_VERSION, true);

    // 2. Enqueue Admin Dashboard Script with full dependency block
    wp_enqueue_script(
      'vapt-admin-js',
      VAPT_SECURE_URL . 'assets/js/admin.js',
      array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'vapt-interface-generator', 'vapt-generated-interface-ui'),
      VAPT_SECURE_VERSION,
      true
    );

    wp_enqueue_style('vapt-admin-css', VAPT_SECURE_URL . 'assets/css/admin.css', array('wp-components'), VAPT_SECURE_VERSION);

    wp_localize_script('vapt-admin-js', 'vaptSecureSettings', array(
      'root' => esc_url_raw(rest_url()),
      'homeUrl' => esc_url_raw(home_url()),
      'nonce' => wp_create_nonce('wp_rest'),
      'isSuper' => is_vapt_secure_superadmin(),
      'pluginVersion' => VAPT_SECURE_VERSION
    ));

    wp_localize_script('vapt-admin-js', 'vapt_secure_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('vapt_secure_scan_nonce')
    ));
  }


  public function admin_page()
  {
    wp_die(__('The VAPT Auditor has been removed.', 'vapt-secure'));
  }
}
