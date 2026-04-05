# ADR 001: Transients for Enforcement Caching

## Status

**Accepted** - Implemented in v1.0, Persisted through v4.0+

## Context

The VAPT Secure plugin applies security enforcements on every WordPress request. These enforcements are stored in database tables (`wp_vaptsecure_feature_meta`, `wp_vaptsecure_feature_status`) and must be queried to determine which rules to apply.

**Problem**: Querying the database on every request adds ~5-15ms of latency per request. With thousands of features, this becomes a bottleneck.

**Constraints**:
- Must work in shared hosting environments (no Redis/Memcached guaranteed)
- Must respect WordPress object caching when available
- Must be invalidatable immediately when features change
- Must handle cache eviction gracefully (auto-rebuild)

## Decision

We will use WordPress transients (`get_transient()` / `set_transient()`) for caching enforcement data.

### Implementation Details

```php
// In class-vaptsecure-enforcer.php::runtime_enforcement()
$cache_key = 'vaptsecure_active_enforcements';
$enforced = get_transient($cache_key);

if (false === $enforced) {
    // Cache miss - query database
    $enforced = $wpdb->get_results(/* ... */);
    set_transient($cache_key, $enforced, HOUR_IN_SECONDS);
}
```

### Cache Invalidation Strategy

| Event | Action |
|-------|--------|
| Feature saved | `delete_transient('vaptsecure_active_enforcements')` |
| Feature toggled | Clear + Rebuild config files |
| Manual clear | Admin UI "Clear Cache" button |
| Cache expires | Auto-rebuild on next request |

## Consequences

### Positive

1. **Performance**: Eliminates database queries on most requests (~10ms saved)
2. **Compatibility**: Works on any WordPress hosting (uses options table as fallback)
3. **Simplicity**: No external dependencies, native WordPress API
4. **Distributed**: Works with Redis Object Cache drop-in automatically

### Negative

1. **Options table bloat**: Large transient values (serialized arrays) stored in wp_options
2. **1-hour staleness**: Changes take up to 1 hour to propagate if invalidation fails
3. **Cache poisoning**: Malformed data in transient can cause enforcement failures

### Mitigations

- Manual cache clear via admin UI
- Cache validation on each request (checksum comparison)
- Emergency `vaptsecure_clear_enforcement_cache()` function
- Force-transient-clear on critical errors

## Alternatives Considered

### Option 1: Direct Database Queries (No Cache)

- **Rejected**: 10-15ms overhead per request unacceptable
- **Use case**: Fallback if transients fail

### Option 2: WordPress Object Cache (wp_cache_*)

- **Rejected**: Not guaranteed to persist across requests in stock WordPress
- **Used for**: Transient backend when external cache available

### Option 3: File-based Cache

- **Rejected**: File I/O slower than database in many shared hosts
- **Security concerns**: Writable files increase attack surface

### Option 4: Constant-time lookups (pre-generated PHP file)

- **Deferred**: Considered for v5.0 optimization
- **Pros**: Zero database/cache dependence
- **Cons**: Requires file writing on every config change

## Related Code

- `includes/class-vaptsecure-enforcer.php:runtime_enforcement()`
- `includes/class-vaptsecure-rest.php:clear_enforcement_cache()`

## Notes

The 1-hour TTL was chosen based on:
- Typical feature update frequency (once per session)
- WordPress cron job frequency
- Balance between freshness and performance

For high-traffic sites, recommend installing a persistent object cache (Redis/Memcached).

---

*Last updated: January 2024 | Author: VAPT Secure Team*
