# Advanced License Enforcement & Release Optimizations Plan

**Created**: 20260301_@0222

This plan outlines critical updates to the License Management and Build Generator tabs to enforce strict, tamper-proof license expiration and ensure security rules are completely removed if a license expires or the plugin is deactivated/deleted, while preserving state for seamless reactivation.

## Proposed Changes

### 1. The "Kill-Switch" & Deactivation Hooks (Rule Reversal + State Preservation)

**Goal:** When the license expires, or the plugin is deactivated/deleted, all applied security features MUST cease to work, and `.htaccess`/`wp-config.php` modifications must be taken off. However, the state of what *was* enabled must be preserved so protections restore seamlessly upon reactivation or license renewal.

- Inject a `vaptsecure_revert_all_rules()` function into the generated client plugin via `class-vaptsecure-build.php`.
- This function will rigidly remove all blocks between `# BEGIN VAPT {ID}` and `# END VAPT {ID}` from targeted files.
- Crucially, it will **NOT** delete the database options tracking which features the user had enabled. The state remains saved in the DB.
- Inject `register_deactivation_hook` to run this function.
- Ensure the plugin activation/init process re-applies rules based on the saved DB state when reactivated.
- If the license check fails (Expiration Date passed), execution halts AND `vaptsecure_revert_all_rules()` is fired aggressively.

### 2. Human-Readable but Tamper-Proof Configuration

**Goal:** Ensure the configuration file remains human-readable so users can see their limits and expiry dates, but protect it with a cryptographic checksum so any tampering instantly invalidates the license.

- Continue injecting standard, readable PHP definitions: `VAPTSECURE_LICENSE_EXPIRY` and `VAPTSECURE_DOMAIN_LIMIT`.
- Generate a secure HMAC Hash (Checksum Signature) of these exact values using a secret salt generated during the build process, and append this signature at the bottom: `VAPTSECURE_CONFIG_SIGNATURE`.
- **Client Side Verification:** The client recalculates the HMAC signature. If they do not match (tampering detected), it fires the Kill-Switch.
- **Client Side Storage:** On first successful run, the plugin saves the vital license stats to `wp_options`.

### 3. Resilient Cross-Host Installation Limits (Phoning Home)

**Goal:** Enforce installation limits when a single zip is given to a client who installs it across multiple different hosting providers, with fail-safes.

- **Master Server:** Add a new REST endpoint `/v1/license/verify` to track active installs across all hosts for a given license ID.
- **Client Plugin:** During activation, it makes an API call to the `MASTER_URL` (dynamic based on builder domain, fallback: `vaptsecure.net`).
- **Soft-Fail Logic:** If the master server is offline/unreachable, the plugin allows activation to proceed and tries again later. It only blocks if it receives a definitive `blocked` status.

### 4. License Management Tab & White-Label Updates

- Complete the "Invalidate License" functionality in `admin.js` and `class-vaptsecure-rest.php`.
- Update the hardcoded white-label defaults in `admin.js`: Keep "Tanveer Malik", change "vapt.builder" to "VAPTSecure".
