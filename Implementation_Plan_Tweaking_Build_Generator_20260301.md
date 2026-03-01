# Update Build Generator Process

The user has requested three tweaks to the "Download Zip" build generator process in `VAPT-Secure`, plus a proactive safeguard against duplicate installations:

1. **Exclusions:** Exclude any Developer Guides or other markdown documents EXCEPT `README.md` and `USER_GUIDE.md`.
2. **Inclusions:** Generate a `USER_GUIDE.md` file dynamically and include it in the build.
3. **Path Setup:** Dynamically create the storage path for the build using the Plugin Name and its slug, rather than a hardcoded `/VAPT-Builds`.
4. **Collision Prevention:** Ensure only one build of the product can be active simultaneously to avoid fatals or configuration conflicts if the domain owner changes the White Label name and activates alongside an old one.

## Proposed Changes

### 1. `includes/class-vaptsecure-build.php`

- **Modify `generate()` method (Path Setup):**
  - Currently: `$base_storage_dir = $upload_dir['basedir'] . '/VAPT-Builds';`
  - Change to use the dynamic plugin slug for the directory structure: `$base_storage_dir = $upload_dir['basedir'] . '/' . $plugin_slug;`
  - **Example:** If the plugin slug is `vapt-secure`, the directory path will be `/wp-content/uploads/vapt-secure/`. If white-labeled to `my-security`, the path will be `/wp-content/uploads/my-security/`.
- **Modify `generate()` method (Generate Documentation):**
  - Update the call to `self::generate_docs()` to also write the `USER_GUIDE.md`.
- **Modify `generate_docs()` method:**
  - Currently, it only creates `README.md`.
  - Update it to also create a `USER_GUIDE.md` file with generic installation instructions and a list of included protections logic meant for end-users.
- **Modify `copy_plugin_files()` method (Exclusions):**
  - Implement strict exclusion of all `.md` files in the root plugin source. Since `README.md` and `USER_GUIDE.md` are dynamically generated *after* the copy, excluding all source `.md` files safely blocks `Implementation Plan.md`, `SKILL.md`, `plans`, and any Developer Guides.
- **Modify `rewrite_main_plugin_file()` (Collision Prevention & Cleanup):**
  - Inject a proactive collision guard at the very top of the rewritten main plugin file.
  - **Guard Logic:** Check if `VAPTSECURE_VERSION` is already defined (which means another instance of the plugin is running).
  - If it is, the code will identify the older plugin's path. It will attempt to proactively **deactivate** and **delete** the older plugin folder from the `wp-content/plugins/` directory using WordPress filesystem APIs (or standard PHP file deletion if `WP_Filesystem` isn't ready).
  - If deletion/deactivation succeeds, the current plugin will continue loading.
  - If deletion fails due to permissions, it will immediately output an `admin_notices` error asking the user to manually remove the old version, and call `return;` to halt execution, completely avoiding PHP Fatal Errors (`Cannot declare class...`).

## Verification Plan

### Automated/Manual Verification

1. **Generate a Build:** Go to the VAPT Domain Admin UI and initiate a build (Download Zip) for a target domain.
2. **Verify Path:** Check the network tab or the downloaded zip URL to confirm it dynamically uses the White Label plugin name/slug instead of `VAPT-Builds`. (e.g., `wp-content/uploads/my-security/...`)
3. **Verify Contents (Inclusions/Exclusions):** Unzip the downloaded file.
    - Confirm that `README.md` and `USER_GUIDE.md` exist.
    - Confirm that no other internal `.md` files are present in the root or included directories (excluding the explicitly authorized JSON/MD items mapped to `data/`).
4. **Verify Collision Guard & Cleanup:**
    - Install the downloaded zip alongside the current `VAPT-Secure` plugin.
    - Attempt to activate the new plugin.
    - Verify that the new plugin successfully activates, while the old `VAPT-Secure` plugin folder is simultaneously deactivated and removed from the active plugins list and filesystem.
