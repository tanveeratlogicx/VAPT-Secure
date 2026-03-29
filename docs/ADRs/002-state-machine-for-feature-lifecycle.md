# ADR 002: State Machine for Feature Lifecycle

## Status

**Accepted** - Core architecture since v1.0

## Context

Security features in VAPT Secure progress through multiple stages:
- Concept → Definition → Testing → Deployment → Retirement

**Problem**: Without structured lifecycle management:
- Accidentally deploying untested features to production
- No audit trail for compliance requirements
- Difficult to rollback problematic features
- No clear "production-ready" designation

**Constraints**:
- Must support gradual rollout (A/B testing)
- Must track who changed what and when
- Must allow emergency rollback to any state
- Must integrate with enforcement system

## Decision

We will implement a 4-state lifecycle with strict transition rules and full audit history.

### State Definitions

```
┌─────────┐    ┌──────────┐    ┌───────┐    ┌─────────┐
│  DRAFT  │───▶│ DEVELOP  │───▶│ TEST  │───▶│ RELEASE │
└─────────┘    └──────────┘    └───────┘    └─────────┘
      │              │              │            │
      └──────────────┴──────────────┴────────────┘
           (Emergency rollback paths)
```

| State | Description | Enforcement | Rollback |
|-------|-------------|-------------|----------|
| **Draft** | Initial state, configuration only | Disabled | - |
| **Develop** | Active development, partial implementation | Partial/Hook | To Draft |
| **Test** | Staged for QA, full enforcement | Full (monitor-only if desired) | To Develop |
| **Release** | Production ready | Full enforcement | Any state |

### Transition Rules

```php
$rules = array(
    'draft'   => array('develop'),                    // Draft → Develop only
    'develop' => array('draft', 'test', 'release'),   // Develop → Any
    'test'    => array('develop', 'release'),         // Test → Develop/Release
    'release' => array('test', 'develop', 'draft')   // Release → Any (emergency)
);
```

### Data Model

**wp_vaptsecure_feature_status**:
- `feature_key` (PK)
- `status` (draft|develop|test|release)
- `implemented_at` (timestamp for Release)
- `assigned_to` (user_id for accountability)

**wp_vaptsecure_feature_history**:
- `id` (auto-increment)
- `feature_key` (FK)
- `old_status` / `new_status`
- `user_id` (who made the change)
- `note` (reason for transition)
- `created_at` (timestamp)

## Consequences

### Positive

1. **Safety**: Accidentally can't push Draft → Release without testing
2. **Auditability**: Complete history of who changed what, when, why
3. **Compliance**: Meets security audit requirements
4. **Rollback**: Emergency "Revert to Draft" wipes all implementation

### Negative

1. **Complexity**: More database tables and queries
2. **User friction**: Must transition through states (intentional)
3. **Storage**: History table grows indefinitely (mitigated by rotation)

### Special Behaviors

#### Draft → Draft (Full Reset)

Transitioning to Draft triggers complete purge:
1. Delete all history records for feature
2. Delete meta record (implementation data)
3. Trigger `rebuild_all()` to remove from config files
4. "Nuclear option" for completely starting over

```php
if (strtolower($new_status) === 'draft') {
    $wpdb->delete($table_history, ['feature_key' => $key]);
    $wpdb->delete($table_meta, ['feature_key' => $key]);
    VAPTSECURE_Enforcer::rebuild_all(); // Cleans config files
}
```

#### Release State Overrides

In Test/Release states, feature can use:
- `override_schema` - Override the AI-generated schema
- `override_implementation_data` - Override implementation

Allows critical fixes without regenerating from scratch.

## Alternatives Considered

### Option 1: Boolean Active/Inactive

- **Rejected**: No staging, no audit trail, too simple for security

### Option 2: 3-State (Draft/Active/Archived)

- **Rejected**: Missing critical "testing" phase

### Option 3: Git-style Branching Model

- **Deferred**: Too complex for MVP
- **May implement**: In v5.0 for advanced workflows

### Option 4: Tags instead of States

- **Rejected**: Tags allow invalid combinations (Draft + Production)

## API Integration

```http
POST /wp-json/vaptsecure/v1/features/transition
Content-Type: application/json

{
  "feature_key": "csp-policy",
  "status": "test",
  "note": "Ready for QA team review",
  "user_id": 42
}
```

Response:
```json
{
  "success": true,
  "message": "Feature transitioned from develop to test"
}
```

## Related Code

- `includes/class-vaptsecure-workflow.php`
- `includes/class-vaptsecure-rest.php:transition_feature()`
- Admin UI: Status dropdown with validation

## Notes

The state machine is intentionally restrictive at the API level but flexible in business logic:
- UI enforces the happy path
- API validates and rejects invalid transitions
- Admin can bypass with direct database access (intentional escape hatch)

---

*Last updated: January 2024 | Author: VAPT Secure Team*
