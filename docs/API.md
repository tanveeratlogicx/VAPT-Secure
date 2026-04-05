# VAPT Secure REST API Documentation

## Overview

The VAPT Secure plugin exposes a comprehensive REST API at `/wp-json/vaptsecure/v1/`. All endpoints (except where noted) require authentication via WordPress REST API permissions.

## Authentication

All endpoints require `manage_options` capability (administrator) unless otherwise specified.

### Permission Levels

- **Read Permission** (`check_read_permission`): Requires `manage_options` capability
- **Full Permission** (`check_permission`): Requires `manage_options` capability AND non-restricted mode
- **Public**: Open access (rate limited to IP)

---

## Feature Management

### `GET /features`

Retrieve all security features with optional filters.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `draft`, `develop`, `test`, `release` |
| `driver` | string | Filter by enforcement driver |
| `enforced` | boolean | Filter by enforcement status |

**Response (200 OK):**

```json
{
  "success": true,
  "features": [
    {
      "feature_key": "security-header-xss",
      "feature_label": "XSS Protection Headers",
      "risk_category": "_headers",
      "driver": "htaccess",
      "status": "release",
      "is_enforced": true,
      "is_enabled": true,
      "updated_at": "2024-01-15 10:30:00"
    }
  ],
  "count": 42,
  "by_status": {
    "release": 30,
    "test": 8,
    "develop": 4,
    "draft": 0
  }
}
```

### `POST /features/update`

Create or update a security feature.

**Request Body:**

```json
{
  "feature_key": "security-header-xss",
  "feature_label": "XSS Protection",
  "risk_category": "_headers",
  "generated_schema": "{\"enforcement\":{\"driver\":\"htaccess\"}}",
  "original_user_need": "Prevent XSS attacks",
  "is_enforced": true,
  "is_enabled": true,
  "strict_mode_override": false
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Feature updated successfully"
}
```

### `POST /features/transition`

Transition a feature through the state machine.

**Request Body:**

```json
{
  "feature_key": "security-header-xss",
  "status": "release",
  "note": "Tested in staging environment",
  "user_id": 1
}
```

**Allowed Transitions:**

- `draft` → `develop`
- `develop` → `draft`, `test`, `release`
- `test` → `develop`, `release`
- `release` → `test`, `develop`, `draft`

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Feature transitioned from develop to release"
}
```

**Error Response (403 Forbidden):**

```json
{
  "success": false,
  "message": "Transition from draft to release is not allowed."
}
```

### `GET /features/{key}/history`

Get transition history for a feature.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | string | Feature key (alphanumeric, hyphens, underscores) |

**Response (200 OK):**

```json
{
  "success": true,
  "history": [
    {
      "old_status": "develop",
      "new_status": "release",
      "user_name": "Admin User",
      "note": "Ready for production",
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### `GET /features/{key}/stats`

Get execution statistics for a feature.

**Response (200 OK):**

```json
{
  "success": true,
  "feature_key": "security-header-xss",
  "violations_blocked": 42,
  "last_triggered": "2024-01-15 14:22:00",
  "execution_count": 1523
}
```

### `POST /features/{key}/reset`

Reset statistics for a feature.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Statistics reset"
}
```

### `POST /features/{key}/verify`

Verify implementation of a feature (check if rules exist in config files).

**Response (200 OK):**

```json
{
  "success": true,
  "verified": true,
  "found_in": [".htaccess", "wp-config.php"]
}
```

---

## Domain Management

### `GET /domains`

List all configured domains.

**Response (200 OK):**

```json
{
  "success": true,
  "domains": [
    {
      "id": 1,
      "domain": "example.com",
      "is_primary": true,
      "features": ["header-xss", "csp-policy"]
    }
  ]
}
```

### `POST /domains/update`

Add or update a domain.

**Request Body:**

```json
{
  "domain": "example.com",
  "is_primary": true,
  "features": ["header-xss", "csp-policy"]
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "domain_id": 1,
  "message": "Domain updated"
}
```

### `DELETE /domains`

Delete a domain.

**Request Body:**

```json
{
  "id": 1
}
```

### `POST /domains/batch-delete`

Delete multiple domains.

**Request Body:**

```json
{
  "ids": [1, 2, 3]
}
```

### `POST /domains/features`

Update domain feature assignments.

**Request Body:**

```json
{
  "domain_id": 1,
  "features": ["header-xss", "new-feature"],
  "append": false
}
```

---

## Configuration Files

### `GET /data-files`

List JSON configuration files in the data directory.

**Response (200 OK):**

```json
{
  "success": true,
  "files": [
    {
      "name": "interface_schema_v2.0.json",
      "path": "/wp-content/plugins/vaptsecure/data/interface_schema_v2.0.json",
      "size": 524288,
      "modified": "2024-01-15 10:00:00"
    }
  ]
}
```

### `GET /data-files/all`

Get content of all data files (for import/export).

### `POST /update-hidden-files`

Update the hidden files list.

**Request Body:**

```json
{
  "files": [
    ".env",
    "wp-config.php.bak",
    "phpinfo.php"
  ]
}
```

---

## Batch Operations

### `POST /features/batch-revert-to-draft`

Bulk revert features from Develop/Release to Draft status.

**Request Body:**

```json
{
  "features": ["feature-1", "feature-2"],
  "include_broken": false,
  "include_release": false
}
```

### `GET /features/preview-revert-to-draft`

Preview what would be affected by a batch revert.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `include_broken` | boolean | false | Include inconsistent ('broken') features |
| `include_release` | boolean | false | Include Release status features |

---

## System Operations

### `POST /clear-enforcement-cache`

Clear the enforcement transient cache.

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Enforcement cache cleared",
  "cleared_at": "2024-01-15 15:00:00"
}
```

### `POST /debug/enforcement-state`

Get detailed enforcement state for debugging.

### `POST /system/generate-build`

Generate a deployment package.

### `POST /system/save-config-root`

Export configurations to root directory.

### `POST /system/sync-config-from-file`

Import configurations from a JSON file.

---

## License

### `GET /license/status`

Check license status (public endpoint, rate limited).

---

## Rate Limiting

### `POST /reset-limit`

Reset rate limit for current IP.

**⚠️ Public Endpoint** - No authentication required

---

## Error Responses

All endpoints return consistent error formats:

```json
{
  "code": "rest_forbidden",
  "message": "You don't have permission to access this endpoint.",
  "data": {
    "status": 403
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request (invalid parameters) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found (feature/domain not found) |
| 500 | Server Error |

---

## PHP API Client

```php
<?php
/**
 * Example API Client for VAPT Secure
 */
class VAPTSecure_API_Client {
    private $base_url;
    private $nonce;

    public function __construct() {
        $this->base_url = get_rest_url() . 'vaptsecure/v1/';
        $this->nonce = wp_create_nonce('wp_rest');
    }

    /**
     * Get all features in release status
     */
    public function get_release_features() {
        $response = wp_remote_get(
            $this->base_url . 'features?status=release',
            [
                'headers' => [
                    'X-WP-Nonce' => $this->nonce
                ]
            ]
        );
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Transition a feature
     */
    public function transition_feature($key, $status, $note = '') {
        $response = wp_remote_post(
            $this->base_url . 'features/transition',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-WP-Nonce' => $this->nonce
                ],
                'body' => json_encode([
                    'feature_key' => $key,
                    'status' => $status,
                    'note' => $note
                ])
            ]
        );
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
```

---

## Response Schema Types

### Feature Object

```typescript
interface Feature {
  feature_key: string;           // Unique identifier
  feature_label: string;         // Human-readable name
  risk_category: string;         // OWASP/MISC category
  driver: string;               // enforcement driver
  status: 'draft' | 'develop' | 'test' | 'release';
  is_enforced: boolean;
  is_enabled: boolean;
  is_strict_mode: boolean;
  generated_schema: object;       // Security rule schema
  implementation_data: object;    // Runtime implementation
  override_schema?: object;     // Test/Release overrides
  override_implementation_data?: object;
  updated_at: string;
}
```

### Domain Object

```typescript
interface Domain {
  id: number;
  domain: string;
  is_primary: boolean;
  features: string[];            // Feature keys assigned
  created_at: string;
}
```

---

*Generated from source: `includes/class-vaptsecure-rest.php`*
