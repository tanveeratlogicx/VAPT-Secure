<?php

/**
 * VAPTSECURE_Apache_Deployer: Adaptive .htaccess Deployment
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Apache_Deployer
{
  private $htaccess_path;

  public function __construct()
  {
    // Path resolution is now dynamic per deployment
  }

  private function resolve_target_path($target)
  {
    if ($target === 'uploads') {
      $upload_dir = wp_upload_dir();
      $this->htaccess_path = $upload_dir['basedir'] . '/.htaccess';
    } else {
      $this->htaccess_path = ABSPATH . '.htaccess';
    }
  }

  public function can_deploy()
  {
    return is_writable($this->htaccess_path) || (!file_exists($this->htaccess_path) && is_writable(ABSPATH));
  }

  public function deploy($risk_id, $implementation, $is_enabled = true)
  {
    $target = $implementation['target'] ?? 'root';
    $this->resolve_target_path($target);

    if (!$this->can_deploy()) {
      return new WP_Error('vapt_deploy_failed', sprintf('.htaccess is not writable at target: %s', $target));
    }

    $rules = trim($this->extract_rules($implementation));
    
    // If rules are empty and we are NOT enabled, it means we should undeploy
    if (empty($rules) && !$is_enabled) {
      $removed = $this->undeploy($risk_id, $target);
      return $removed ? ['status' => 'undeployed', 'platform' => 'apache_htaccess'] : new WP_Error('vapt_undeploy_failed', 'Failed to remove rules from .htaccess');
    }

    // Ensure global whitelist exists before deploying individual rules
    $this->ensure_global_whitelist();

    return $this->write_rules($risk_id, $rules, $is_enabled);
  }

  private function extract_rules($implementation)
  {
    // Try the standard format from platform_matrix
    if (isset($implementation['rules'])) {
      return is_array($implementation['rules']) ? implode("\n", $implementation['rules']) : $implementation['rules'];
    }

    // 🛡️ Compatibility: Support 'code' field (v3.13.14)
    if (isset($implementation['code'])) {
      return is_array($implementation['code']) ? implode("\n", $implementation['code']) : $implementation['code'];
    }

    // Fallback to legacy extraction logic
    if (class_exists('VAPTSECURE_Enforcer')) {
      return VAPTSECURE_Enforcer::extract_code_from_mapping($implementation, 'htaccess');
    }

    return '';
  }

  private function write_rules($risk_id, $rules, $is_enabled = true)
  {
    $content = file_exists($this->htaccess_path) ? file_get_contents($this->htaccess_path) : '';

    $status_suffix = $is_enabled ? ' - ACTIVE' : ' - DISABLED';
    $start_marker = "# BEGIN VAPT PROTECTION: {$risk_id}";
    $end_marker = "# END VAPT PROTECTION: {$risk_id}";

    // Handle content neutralization (comment out) if disabled
    if (!$is_enabled) {
        $lines = explode("\n", trim($rules));
        $rules = implode("\n", array_map(function($l) {
            $l = trim($l);
            if ($l === '') return '';
            return '# ' . ltrim($l, '# ');
        }, $lines));
    }

    // Regex to match existing block with any suffix (- ACTIVE, - DISABLED or none)
    $pattern = "/" . preg_quote($start_marker, '/') . ".*?" . preg_quote($end_marker, '/') . "/s";
    $content = preg_replace($pattern, '', $content);

    // Add new block with refined markers
    $final_start_marker = $start_marker . $status_suffix;
    $new_block = "\n{$final_start_marker}\n{$rules}\n{$end_marker}\n";

    // Insert after Global Whitelist or WordPress markers
    if (strpos($content, '# END VAPT GLOBAL WHITELIST') !== false) {
      $content = str_replace('# END VAPT GLOBAL WHITELIST', "# END VAPT GLOBAL WHITELIST\n" . $new_block, $content);
    } elseif (strpos($content, '# BEGIN WordPress') !== false) {
      $content = str_replace('# BEGIN WordPress', $new_block . '# BEGIN WordPress', $content);
    } else {
      $content = $new_block . $content;
    }

    $result = file_put_contents($this->htaccess_path, trim($content) . "\n", LOCK_EX);

    return $result !== false ? ['status' => 'deployed', 'platform' => 'apache_htaccess'] : new WP_Error('vapt_write_error', 'Failed to write to .htaccess');
  }

  private function ensure_global_whitelist()
  {
    $content = file_exists($this->htaccess_path) ? file_get_contents($this->htaccess_path) : '';
    
    $start_marker = "# BEGIN VAPT GLOBAL WHITELIST";
    $end_marker = "# END VAPT GLOBAL WHITELIST";
    
    if (strpos($content, $start_marker) !== false) return;

    $whitelist_rules = "{$start_marker}\n<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteCond %{REQUEST_URI} ^/wp-admin/ [OR]\n    RewriteCond %{REQUEST_URI} ^/wp-json/wp/v2/ [OR]\n    RewriteCond %{REQUEST_URI} ^/wp-json/vaptsecure/v1/ [OR]\n    RewriteCond %{REQUEST_URI} /admin-ajax\\.php$ [OR]\n    RewriteCond %{REQUEST_URI} /wp-login\\.php$\n    RewriteRule ^ - [E=VAPT_WHITELIST:1]\n</IfModule>\n{$end_marker}\n";

    if (strpos($content, '# BEGIN WordPress') !== false) {
      $content = str_replace('# BEGIN WordPress', $whitelist_rules . "\n# BEGIN WordPress", $content);
    } else {
      $content = $whitelist_rules . "\n" . $content;
    }

    file_put_contents($this->htaccess_path, trim($content) . "\n", LOCK_EX);
  }

  public function undeploy($risk_id, $target = 'root')
  {
    $this->resolve_target_path($target);
    if (!file_exists($this->htaccess_path)) return true;

    $content = file_get_contents($this->htaccess_path);
    $start_marker = "# BEGIN VAPT PROTECTION: {$risk_id}";
    $end_marker = "# END VAPT PROTECTION: {$risk_id}";

    // Use regex to match block regardless of suffix
    $pattern = "/" . preg_quote($start_marker, '/') . ".*?" . preg_quote($end_marker, '/') . "/s";
    $new_content = preg_replace($pattern, '', $content);

    if ($new_content !== $content) {
      return file_put_contents($this->htaccess_path, trim($new_content) . "\n", LOCK_EX);
    }

    return true;
  }
}
