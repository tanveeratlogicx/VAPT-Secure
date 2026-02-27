<?php

/**
 * VAPTSECURE_Sync_Cron
 * Handles recurring synchronization of the Risk Catalog and Enforcement.
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Sync_Cron
{
  /**
   * Initialize the cron hooks
   */
  public static function init()
  {
    add_action('vaptsecure_daily_sync_event', [self::class, 'run_sync']);

    if (!wp_next_scheduled('vaptsecure_daily_sync_event')) {
      wp_schedule_event(time(), 'daily', 'vaptsecure_daily_sync_event');
    }
  }

  /**
   * Run the automated synchronization
   */
  public static function run_sync()
  {
    error_log('VAPT: Starting daily catalog synchronization...');

    $schema_path = VAPTSECURE_PATH . 'data/' . VAPTSECURE_ACTIVE_DATA_FILE;
    $pattern_path = VAPTSECURE_PATH . 'data/enforcer_pattern_library_v2.0.json';

    if (!file_exists($schema_path) || !file_exists($pattern_path)) {
      error_log('VAPT ERROR: Sync failed. Master data files missing.');
      return;
    }

    $schema_data = json_decode(file_get_contents($schema_path), true);
    $patterns = json_decode(file_get_contents($pattern_path), true);
    $risk_catalog = $schema_data['risk_interfaces'] ?? [];

    foreach ($risk_catalog as $key => $risk) {
      // 1. Fetch existing status from DB. Default to 'Draft' if missing.
      $db_row = VAPTSECURE_DB::get_feature($key);
      $status = $db_row ? $db_row['status'] : 'Draft';

      // 🛡️ Automatic Enforcement Principle: 
      // release/implemented: Always enforced.
      // draft/available: Never enforced.
      // develop/testing: Preserve manual deployment state (Step 2 gate).
      $norm_status = strtolower($status);
      $current_meta = VAPTSECURE_DB::get_feature_meta($key);
      $current_is_enforced = $current_meta ? (int)($current_meta['is_enforced'] ?? 0) : 0;

      if (in_array($norm_status, ['release', 'implemented'])) {
        $is_enforced = 1;
      } elseif (in_array($norm_status, ['draft', 'available'])) {
        $is_enforced = 0;
      } else {
        // preserve existing flag for Develop/Testing
        $is_enforced = $current_is_enforced;
      }

      // 2. Resolve/Update Mappings and Schema
      $enforcement = $risk['enforcement'] ?? [];
      $platforms = $risk['available_platforms'] ?? [];
      $pref_platform = $platforms[0] ?? null;

      if ($pref_platform) {
        $lib_key = $risk['platform_implementations'][$pref_platform]['lib_key'] ?? null;
        $search_key = str_replace(['.', '-'], ['', '_'], $lib_key);

        if ($search_key && isset($patterns['patterns'][$key][$search_key]['code'])) {
          $code = $patterns['patterns'][$key][$search_key]['code'];
          $enforcement['mappings'] = [];
          foreach ($risk['components'] as $comp) {
            if (($comp['type'] ?? '') === 'toggle') {
              $enforcement['mappings'][$comp['component_id']] = $code;
              if (!empty($comp['settings_key'])) {
                $enforcement['mappings'][$comp['settings_key']] = $code;
              }
            }
          }
          $enforcement['driver'] = $lib_key;
        }
      }
      if (empty($enforcement['driver'])) $enforcement['driver'] = 'hook';
      $risk['enforcement'] = $enforcement;

      // 3. Prepare Implementation Data
      $impl_data = [];
      if ($is_enforced) {
        foreach ($risk['components'] as $comp) {
          if (($comp['type'] ?? '') === 'toggle') {
            $impl_data[$comp['component_id']] = true;
            if (!empty($comp['settings_key'])) {
              $impl_data[$comp['settings_key']] = true;
            }
          }
        }
      }

      // ALWAYS update meta to ensure schema and mapping consistency
      VAPTSECURE_DB::update_feature_meta($key, [
        'generated_schema' => json_encode($risk),
        'implementation_data' => json_encode($impl_data),
        'is_enforced' => $is_enforced
      ]);

      // Only update status table if it's missing (to preserve user manual overrides)
      if (!$db_row) {
        VAPTSECURE_DB::update_feature_status($key, $status);
      }
    }
    VAPTSECURE_Enforcer::rebuild_all();
    error_log('VAPT: Daily catalog synchronization complete.');
  }
}
