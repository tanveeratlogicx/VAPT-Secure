# Add Superadmin Dashboard Link to Alerts

This plan outlines the changes required to ensure the correct OTP-protected Superadmin dashboard link is featured prominently in the initial plugin activation alert email and the Usage Violation alert emails.

## Proposed Changes

### VAPT-Secure/vaptsecure.php

- **[MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)**
  - In `vaptsecure_send_activation_email()`, the current variable `$admin_url` points to `admin_url('admin.php?page=vaptsecure-domain-admin')`. This is the correct OTP-protected Superadmin Master Dashboard.
  - Rename 'Access Dashboard' in the email text to 'Superadmin Dashboard' to make the distinction perfectly clear.
  - Bump `VAPTSECURE_VERSION` from `2.3.0` to `2.3.1` to reflect the patch change according to the version bump policy.
  - Update the version string in the main plugin file header block.

### VAPT-Secure/includes/class-vaptsecure-build.php

- **[MODIFY] [class-vaptsecure-build.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-build.php)**
  - In `rewrite_main_plugin_file()`, locate `_vaptsecure_handle_violation()`.
  - Update the `$message` variable to append the Superadmin Dashboard link. We will use the stored Master URL (which is passed down to the builder or reconstructed based on environment logic, e.g., using a defined constant or hardcoded `https://vaptsecure.net/wp-admin/admin.php?page=vaptsecure-domain-admin`).
  - *Note on Master URL logic in builder:* We'll use the `$master_url` variable already defined in `VAPTSECURE_Build::generate()` and pass it into the violation handler code block, so the violation email links directly back to the VAPT Secure Dashboard where the superadmin can manage the licenses.

## Verification Plan

### Automated Tests

- Not applicable as there are no automated unit tests for this specific email dispatch in the current workspace.

### Manual Verification

1. I will use the browser tool to log in to the Hermasnet WordPress instance as a superadmin.
2. I will deactivate and reactivate the VAPT Secure plugin to trigger a fresh install condition.
3. *Alternative:* Check if there's a way to trigger the email without full deactivation (e.g., using `wp eval` via `run_command` to execute `vaptsecure_send_activation_email()`).
4. Since we might not have access to the `tanmalik786@gmail.com` inbox, I can temporarily inject a logging statement right before `wp_mail` to log the exact `$message` content being sent to ensure the new link is present, or check the system's email logs if available via WP CLI. Using `wp eval "vaptsecure_send_activation_email();"` and checking a local mail catcher (if one exists in LocalWP) or logging the output will be the best approach.
