# CWE → VAPT Risk ID Crosswalk

Quick-reference table mapping MITRE CWE identifiers to the Full-125 catalogue.

| CWE ID   | Name                                              | Typical Risk IDs             |
|----------|---------------------------------------------------|------------------------------|
| CWE-20   | Improper Input Validation                         | VAPT-003, VAPT-005           |
| CWE-22   | Path Traversal                                    | VAPT-008, VAPT-009           |
| CWE-77   | Command Injection                                 | VAPT-004                     |
| CWE-78   | OS Command Injection                              | VAPT-004, VAPT-007           |
| CWE-79   | Cross-Site Scripting (XSS)                        | VAPT-095 – VAPT-105          |
| CWE-89   | SQL Injection                                     | VAPT-001, VAPT-002           |
| CWE-90   | LDAP Injection                                    | VAPT-003                     |
| CWE-94   | Code Injection                                    | VAPT-006                     |
| CWE-98   | PHP File Inclusion                                | VAPT-006, VAPT-009           |
| CWE-200  | Exposure of Sensitive Information                 | VAPT-116 – VAPT-120          |
| CWE-209  | Error Message with Sensitive Information          | VAPT-043                     |
| CWE-250  | Execution with Unnecessary Privileges             | VAPT-012                     |
| CWE-256  | Plaintext Storage of a Password                   | VAPT-020                     |
| CWE-259  | Use of Hard-coded Password                        | VAPT-021                     |
| CWE-284  | Improper Access Control                           | VAPT-010 – VAPT-014          |
| CWE-285  | Improper Authorization                            | VAPT-011, VAPT-013           |
| CWE-287  | Improper Authentication                           | VAPT-075 – VAPT-078          |
| CWE-295  | Improper Certificate Validation                   | VAPT-022                     |
| CWE-306  | Missing Authentication for Critical Function      | VAPT-076                     |
| CWE-311  | Missing Encryption of Sensitive Data              | VAPT-022                     |
| CWE-319  | Cleartext Transmission of Sensitive Information   | VAPT-022                     |
| CWE-326  | Inadequate Encryption Strength                    | VAPT-021                     |
| CWE-352  | Cross-Site Request Forgery (CSRF)                 | VAPT-014                     |
| CWE-400  | Uncontrolled Resource Consumption                 | VAPT-030                     |
| CWE-434  | Unrestricted Upload of Dangerous File             | VAPT-007, VAPT-008           |
| CWE-502  | Deserialization of Untrusted Data                 | VAPT-080                     |
| CWE-521  | Weak Password Requirements                        | VAPT-077                     |
| CWE-522  | Insufficiently Protected Credentials              | VAPT-020                     |
| CWE-539  | Use of Persistent Cookies Containing Sensitive Info | VAPT-078                   |
| CWE-601  | Open Redirect                                     | VAPT-013                     |
| CWE-611  | XML External Entity (XXE)                         | VAPT-005                     |
| CWE-613  | Insufficient Session Expiration                   | VAPT-078                     |
| CWE-639  | Authorization Bypass Through User-Controlled Key  | VAPT-011                     |
| CWE-770  | Allocation Without Limits (DoS)                   | VAPT-031                     |
| CWE-798  | Use of Hard-coded Credentials                     | VAPT-021                     |
| CWE-862  | Missing Authorization                             | VAPT-010, VAPT-012           |
| CWE-918  | Server-Side Request Forgery (SSRF)                | VAPT-090, VAPT-091           |
| CWE-1021 | Improper Restriction of Rendered UI Layers (Clickjacking) | VAPT-044          |

---

## Usage

```bash
# Find all catalogue entries for a given CWE
python scripts/query_catalogue.py --cwe CWE-89
```

Or filter in the resources above using the `cwe_id` field.
