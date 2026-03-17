<?php

/**
 * VAPTSECURE_Config_Deployer: Adaptive wp-config.php Deployment
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Config_Deployer
{
  public function can_deploy()
  {
    return is_writable(ABSPATH . 'wp-config.php');
  }

  public function deploy($risk_id, $implementation, $is_enabled = true)
  {
    if (!$this->can_deploy()) {
      return new WP_Error('vapt_deploy_failed', 'wp-config.php is not writable.');
    }

    // Since wp-config.php is managed as a batch by VAPTSECURE_Config_Driver,
    // we don't write individual rules here. Instead, we trigger a global rebuild.
    // The Config Driver will pull the latest meta and write all active rules.
    
    require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-enforcer.php';
    $result = VAPTSECURE_Enforcer::rebuild_config();

    return $result ? ['status' => 'rebuild_triggered', 'platform' => 'wp_config'] : new WP_Error('vapt_rebuild_failed', 'Failed to rebuild wp-config.php');
  }

  public function undeploy($risk_id)
  {
    // Same as deploy, trigger a rebuild which will effectively remove it if disabled
    require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-enforcer.php';
    return VAPTSECURE_Enforcer::rebuild_config();
  }
}
