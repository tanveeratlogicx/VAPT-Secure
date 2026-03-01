# Final Advanced License Enforcement & Release Optimizations Plan

This plan finalizes the critical updates to the License Management and Build Generator tabs to enforce strict, tamper-proof license expiration. It integrates advanced obfuscation and encryption to protect the phone-home mechanism, stored license states, completely obscure the Superadmin's identity, and ensures builds created locally correctly phone home to production infrastructure.

## Proposed Changes

### 1. The "Kill-Switch" & Deactivation Hooks (Rule Reversal + State Preservation)

- Inject a `vaptsecure_revert_all_rules()` function into the generated client plugin to remove `# BEGIN VAPT {ID}` blocks from `.htaccess` and `wp-config.php`.
- **State Preservation:** It will **NOT** delete DB options tracking enabled features.
- Hook into `register_deactivation_hook`.
- Reactivation restores rules based on saved state.
- Expiration triggers `vaptsecure_revert_all_rules()` aggressively.

### 2. Human-Readable but Tamper-Proof Configuration

- Config will have human-readable fields (`VAPTSECURE_LICENSE_EXPIRY`).
- Config will have an HMAC checksum (`VAPTSECURE_CONFIG_SIGNATURE`).
- **Obfuscated Payload:** An encrypted payload (`VAPTSECURE_OBFUSCATED_PAYLOAD`) will be attached so that if the signature fails, the system knows what the values were supposed to be.
- **Encrypted DB Storage:** License data will be stored encrypted in `wp_options` under an innocuous name (e.g., `_transient_wp_sec_cache_v3`). Cross-check file vs DB.

### 3. Resilient Cross-Host Installation Limits (Phoning Home)

- Master server endpoint: `/v1/license/verify`.
- **Local Environment Fallback:** Read `$_SERVER['HTTP_HOST']`. If it contains `.local`, `.test`, or `localhost`, hardcode `https://vaptsecure.net` as the master server. Otherwise, use the originating domain.
- **Obfuscated Endpoint:** The master URL and REST path will be obfuscated via Base64.
- **Soft-Fail:** If the master is unreachable, allow activation temporarily and retry later. Only block on a hard `blocked` response.

### 4. Superadmin Data Obfuscation

- **Constant Name Obfuscation:** The constant name `VAPTSECURE_SUPERADMIN_EMAIL` will completely be removed from the client build. We will use innocuous names like `$alertContact` instead.
- **Email and Master Domain Obfuscation:** Stored strictly as Base64 encoded strings within the source files, decoded only at runtime to avoid manual inspection.

### 5. License Management Tab & White-Label Updates

- Add "Invalidate License" functionality in `admin.js`.
- Update UI defaults: URI -> "<https://vaptsecure.net>" (or dynamic).
