# ADR 003: Multi-Driver Dispatch Pattern

## Status

**Accepted** - Core architecture since v1.0, expanded in v4.0

## Context

Security enforcements must work across diverse hosting environments:
- Apache with .htaccess (shared hosting)
- Nginx (VMs, containers)
- IIS (Windows servers)
- Caddy (modern reverse proxy)
- Cloudflare (edge/CDN)
- WordPress hooks (PHP runtime)
- wp-config.php (server-level)

**Problem**: Each platform requires different configuration syntax, file locations, and deployment methods. A single implementation cannot handle all cases.

**Constraints**:
- Must auto-detect server type when possible
- Must fall back gracefully if primary driver fails
- Must support hybrid deployments (e.g., Nginx + Cloudflare)
- Must validate that rules are actually applied

## Decision

We will implement a Driver Dispatch Pattern with platform-specific drivers and a unified interface.

## Architecture

### High-Level Flow

```
Feature Saved
      │
      ▼
┌─────────────────┐
│ Parse Schema    │─── Extract enforcement block
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ Resolve Driver  │─── htaccess/nginx/hook/config/etc.
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ Dispatch        │─── Switch case based on driver
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ Driver Executes │─── Generate platform-specific rules
│                 │─── Write to appropriate files
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ Verify          │─── Confirm rules exist in target
└─────────────────┘
```

### Driver Registry

```php
$drivers = [
    'htaccess'      => 'VAPTSECURE_Htaccess_Driver',
    'nginx'         => 'VAPTSECURE_Nginx_Driver',
    'iis'           => 'VAPTSECURE_IIS_Driver',
    'caddy'         => 'VAPTSECURE_Caddy_Driver',
    'cloudflare'    => 'VAPTSECURE_Cloudflare_Driver',
    'hook'          => 'VAPTSECURE_Hook_Driver',
    'php_functions' => 'VAPTSECURE_PHP_Driver',
    'config'        => 'VAPTSECURE_Config_Driver',
    'wp_config'     => 'VAPTSECURE_Config_Driver',  // Alias
];
```

### Dispatch Logic

```php
public static function dispatch_enforcement($key, $data) {
    // [v4.0.0] Adaptive Deployment Orchestration
    $is_adaptive = $meta['is_adaptive_deployment'] ?? null;
    if ($is_adaptive) {
        $orchestrator = new VAPTSECURE_Deployment_Orchestrator();
        $results = $orchestrator->orchestrate($key, $schema, $profile, $impl_data);
    }

    $driver_name = $schema['enforcement']['driver'];

    switch ($driver_name) {
        case 'htaccess':
            self::rebuild_htaccess();    // Also handles Nginx detection
            self::rebuild_config();
            break;

        case 'nginx':
            self::rebuild_nginx();
            self::rebuild_htaccess();   // Fallback PHP hooks
            break;

        case 'hook':
        case 'php_functions':
            self::rebuild_php_functions();
            self::rebuild_htaccess();   // Header fallbacks
            self::rebuild_config();
            break;

        // ... other cases
    }
}
```

## Consequences

### Positive

1. **Platform Agnostic**: Same feature schema works on any server
2. **Safety**: Multiple write targets = redundancy
3. **Flexibility**: Hybrid deployments (Cloudflare + Apache)
4. **Testing**: Can develop features locally (hook driver) before prod (htaccess)

### Negative

1. **Complexity**: Managing 8+ driver implementations
2. **Consistency**: Keeping drivers in sync is maintenance burden
3. **Detection**: Auto-detection isn't perfect (fallback to htaccess)

### Driver-Specific Behaviors

| Driver | Target | Rebuild Strategy | Validation |
|--------|--------|------------------|------------|
| htaccess | `.htaccess` | Batch rewrite | File exists, markers present |
| nginx | `nginx.conf` snippet | Batch rewrite | Manual admin deploy |
| hook | PHP runtime | WordPress hooks | Function exists |
| config | `wp-config.php` markers | Batch rewrite | PHP eval check |
| caddy | `Caddyfile` | File write | Syntax validation |
| cloudflare | API | HTTP POST | API response check |

## Fallback Strategy

If primary driver fails, the system falls back:

```
htaccess driver fails ──▶ hook driver (PHP hooks)
nginx detection fails ──▶ htaccess (Apache fallback)
config write fails ────▶ Secure failure, no enforcement
```

**Rule**: No enforcement is better than broken enforcement.

## Deployment Orchestrator (v4.0+)

For adaptive deployment, the orchestrator coordinates:

```php
class VAPTSECURE_Deployment_Orchestrator {
    public function orchestrate($key, $schema, $profile, $impl) {
        // Multi-target deployment with rollback
        $targets = $this->resolve_targets($profile);

        foreach ($targets as $target) {
            $driver = $this->get_driver($target['type']);
            $success = $driver->deploy($key, $schema, $target);

            if (!$success) {
                $this->rollback($key);  // Undo partial changes
                return ['error' => true, 'failed_target' => $target];
            }
        }

        return ['success' => true, 'deployed_to' => $targets];
    }
}
```

## Alternative Patterns Considered

### Option 1: Single Target Only

- **Rejected**: No redundancy, requires perfect detection
- **Use case**: Might simplify for v5.0 "expert mode"

### Option 2: Strategy Pattern with Inheritance

- **Considered**: `abstract class EnforcementDriver`
- **Used for**: Base driver interface

### Option 3: Plugin Architecture

- **Deferred**: External drivers as plugins
- **May implement**: For community-contributed drivers

### Option 4: Configuration as Code

- **Deferred**: Externalize to Terraform/Ansible
- **May implement**: Enterprise CLR/CD integration

## Related Code

- `includes/class-vaptsecure-enforcer.php` - Dispatcher
- `includes/enforcers/class-vaptsecure-*-driver.php` - Implementations
- `includes/class-vaptsecure-deployment-orchestrator.php` - v4.0+ adaptive

## Testing Strategy

Each driver must pass:
1. **Unit tests**: Rule generation logic
2. **Integration tests**: File I/O operations
3. **Property tests**: Schema → rules roundtrip
4. **E2E tests**: Full WordPress request

## Notes

The driver system evolved over versions:
- **v1.0**: htaccess only
- **v2.0**: Added nginx, hooks
- **v3.0**: Added config, IIS, Caddy
- **v4.0**: Adaptive orchestration, Cloudflare API

---

*Last updated: January 2024 | Author: VAPT Secure Team*
