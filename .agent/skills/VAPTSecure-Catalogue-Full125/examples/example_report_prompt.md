# Example: Report Generation Prompts

Use these prompts verbatim (or adapt them) to trigger report generation from
the Full-125 catalogue.

---

## Executive PDF Summary (all findings)

> Generate an executive-level PDF summary of all 125 VAPT findings for the
> hermasnet engagement. Include a risk heat-map, severity breakdown pie chart,
> top-10 findings table, and a one-page remediation road-map.

**Skill triggers:** `generate_report.py --format pdf` + PDF SKILL.md

---

## Detailed DOCX Report (Critical + High only)

> Create a detailed Word document covering only the Critical and High findings
> from the VAPT catalogue. Each finding should have its own section with
> description, impact, verbatim evidence, numbered remediation steps, and
> reference links. Add a cover page and table of contents.

**Skill triggers:** `generate_report.py --format docx --severity Critical,High` + DOCX SKILL.md

---

## Risk Register Spreadsheet

> Export the full 125-item catalogue to an Excel risk register. Columns:
> Risk ID, Title, Category, Severity, CVSS Score, CVSS Vector, OWASP Ref,
> CWE ID, Affected Asset, Status, Discovered At, SLA Deadline.
> Colour-code rows by severity tier and freeze the header row.

**Skill triggers:** `generate_report.py --format xlsx` + XLSX SKILL.md

---

## Slide Deck for Client Presentation

> Build a PowerPoint slide deck for the client meeting. Include:
> slide 1 – Engagement overview, slide 2 – Severity distribution chart,
> slides 3–7 – One slide per Critical finding, slide 8 – Remediation timeline,
> slide 9 – Next steps. Use a professional dark-blue security theme.

**Skill triggers:** `generate_report.py --format pptx` + PPTX SKILL.md

---

## Developer-Facing Markdown

> Output a Markdown file listing all Open findings grouped by category.
> Each entry should show: Risk ID, Title, CVSS, affected asset, and a
> collapsed `<details>` block containing full description + remediation.

**Skill triggers:** `generate_report.py --format md --status Open`

---

## Delta Report (newly discovered this sprint)

> Compare the current catalogue against the baseline snapshot in
> `resources/baseline_snapshot.json` and generate a DOCX delta report
> showing only new, changed, or resolved findings since the last scan.

**Skill triggers:** `generate_report.py --format docx --delta resources/baseline_snapshot.json`
