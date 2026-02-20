# Example: Querying the VAPT Catalogue

These examples demonstrate prompts that trigger this skill and the expected
Claude behaviour at each step.

---

## Example 1 — Filter by Severity

**User prompt:**
> Show me all Critical findings from the VAPT catalogue.

**Expected Claude behaviour:**

1. Run `scripts/query_catalogue.py --severity Critical`
2. Present a table: Risk ID | Title | CVSS | Affected Asset | Status
3. Remind the user that Critical findings have a **24-hour remediation SLA**.
4. Offer to generate a focused report or create tickets.

**Sample output table:**

| Risk ID  | Title                              | CVSS | Asset               | Status |
|----------|------------------------------------|------|---------------------|--------|
| VAPT-001 | SQL Injection – Login Form         | 9.8  | /wp-login.php       | Open   |
| VAPT-007 | Remote Code Execution via File Upload | 9.6 | /wp-admin/upload.php | Open  |
| VAPT-019 | Unauthenticated API Key Disclosure | 9.1  | /wp-json/vapt/v1/   | Open   |

---

## Example 2 — Search by Keyword

**User prompt:**
> Find anything in the catalogue about file uploads.

**Expected Claude behaviour:**

1. Run `scripts/query_catalogue.py --keyword "file upload"`
2. Return all matching entries (title + risk_id + severity).
3. Highlight the highest CVSS match first.

---

## Example 3 — Look Up a Specific Risk

**User prompt:**
> Give me the full details for VAPT-042.

**Expected Claude behaviour:**

1. Run `scripts/query_catalogue.py --id VAPT-042`
2. Display ALL fields: description, impact, evidence (verbatim), remediation
   as a numbered list, references as clickable links, CVSS vector string.
3. State the applicable SLA based on severity tier.

---

## Example 4 — Cross-Reference OWASP

**User prompt:**
> Which catalogue entries map to OWASP A01:2021?

**Expected Claude behaviour:**

1. Consult `resources/OWASP_MAPPING.md`
2. Filter catalogue for `"owasp_ref": "A01:2021"`
3. Return grouped results sorted by CVSS descending.

---

## Example 5 — Status Filter

**User prompt:**
> What findings are still Open with CVSS above 7?

**Expected Claude behaviour:**

1. Run `scripts/query_catalogue.py --status Open --min-cvss 7.0`
2. Present results sorted by CVSS descending.
3. Summarise: X Critical, Y High — offer to kick off triage workflow.
