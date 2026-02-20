#!/usr/bin/env python3
"""
generate_report.py
------------------
Generate multi-format VAPT reports from the Full-125 catalogue.

Formats: pdf | docx | xlsx | md | pptx

Usage:
    python scripts/generate_report.py --format pdf
    python scripts/generate_report.py --format docx --severity Critical,High
    python scripts/generate_report.py --format xlsx
    python scripts/generate_report.py --format md --status Open
    python scripts/generate_report.py --format docx --filter "VAPT-001,VAPT-007,VAPT-019"
    python scripts/generate_report.py --format docx \
        --delta resources/baseline_snapshot.json

NOTE:
    This script prepares the data payload and writes a clean Markdown source
    file which Claude then hands off to the appropriate document-generation
    skill (DOCX/PDF/XLSX/PPTX SKILL.md) for final rendering.

    Run with --dry-run to preview the filtered entry list without writing files.
"""

import argparse
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

from load_catalogue import DEFAULT_PATH, load_catalogue

SEVERITY_ORDER = {"Critical": 0, "High": 1, "Medium": 2, "Low": 3, "Info": 4}
SLA_DAYS = {"Critical": 1, "High": 7, "Medium": 30, "Low": 90, "Info": 0}

VALID_FORMATS = {"pdf", "docx", "xlsx", "md", "pptx"}


def parse_args():
    p = argparse.ArgumentParser(description="Generate VAPT Reports")
    p.add_argument("--path",     type=Path, default=DEFAULT_PATH)
    p.add_argument("--format",   required=True, choices=VALID_FORMATS,
                   help="Output format")
    p.add_argument("--output",   help="Output filename (auto-generated if omitted)")
    p.add_argument("--severity", help="Filter by severity (comma-separated)")
    p.add_argument("--status",   help="Filter by status (comma-separated)")
    p.add_argument("--filter",   help="Explicit comma-separated risk IDs")
    p.add_argument("--delta",    type=Path,
                   help="Baseline JSON for delta/diff report")
    p.add_argument("--title",    default="VAPT Security Assessment Report",
                   help="Report title")
    p.add_argument("--client",   default="Hermasnet",
                   help="Client name for cover page")
    p.add_argument("--dry-run",  action="store_true",
                   help="Preview filtered entries without writing output")
    return p.parse_args()


def apply_filters(catalogue, args):
    results = catalogue

    if args.filter:
        ids = {i.strip().upper() for i in args.filter.split(",")}
        results = [e for e in results if e.get("risk_id") in ids]

    if args.severity:
        sevs = {s.strip() for s in args.severity.split(",")}
        results = [e for e in results if e.get("severity") in sevs]

    if args.status:
        statuses = {s.strip() for s in args.status.split(",")}
        results = [e for e in results if e.get("status") in statuses]

    if args.delta:
        baseline = json.loads(args.delta.read_text(encoding="utf-8"))
        baseline_ids = {e.get("risk_id") for e in
                        (baseline if isinstance(baseline, list)
                         else baseline.get("risks", []))}
        results = [e for e in results if e.get("risk_id") not in baseline_ids]

    results.sort(key=lambda e: (
        SEVERITY_ORDER.get(e.get("severity", "Info"), 99),
        -e.get("cvss_score", 0.0)
    ))
    return results


def build_markdown_payload(entries, args):
    """Build the Markdown source that will be passed to the document skill."""
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d")

    lines = [
        f"# {args.title}",
        f"",
        f"**Client:** {args.client}  ",
        f"**Date:** {now}  ",
        f"**Total Findings:** {len(entries)}  ",
        f"",
    ]

    # Severity summary table
    from collections import Counter
    sev_counts = Counter(e.get("severity") for e in entries)
    lines += [
        "## Severity Summary",
        "",
        "| Severity | Count | SLA |",
        "|----------|-------|-----|",
    ]
    for sev in ["Critical", "High", "Medium", "Low", "Info"]:
        count = sev_counts.get(sev, 0)
        sla = f"{SLA_DAYS[sev]} day(s)" if SLA_DAYS[sev] else "Best effort"
        lines.append(f"| {sev} | {count} | {sla} |")
    lines.append("")

    # Findings index
    lines += [
        "## Findings Index",
        "",
        "| # | Risk ID | Title | Severity | CVSS | Asset | Status |",
        "|---|---------|-------|----------|------|-------|--------|",
    ]
    for i, e in enumerate(entries, 1):
        lines.append(
            f"| {i} | {e['risk_id']} | {e['title']} | {e['severity']} "
            f"| {e['cvss_score']} | {e['affected_asset']} | {e['status']} |"
        )
    lines.append("")

    # Detailed findings
    lines.append("## Detailed Findings")
    for e in entries:
        sla_days = SLA_DAYS.get(e.get("severity", "Info"), 0)
        sla_str  = f"{sla_days} day(s)" if sla_days else "Best effort"
        lines += [
            "",
            f"---",
            "",
            f"### {e['risk_id']} · {e['title']}",
            "",
            f"| Field | Value |",
            f"|-------|-------|",
            f"| **Severity** | {e['severity']} |",
            f"| **CVSS Score** | {e['cvss_score']} |",
            f"| **CVSS Vector** | `{e.get('cvss_vector', '')}` |",
            f"| **Category** | {e.get('category', '')} |",
            f"| **OWASP** | {e.get('owasp_ref', 'N/A')} |",
            f"| **CWE** | {e.get('cwe_id', 'N/A')} |",
            f"| **Affected Asset** | `{e['affected_asset']}` |",
            f"| **Status** | {e['status']} |",
            f"| **SLA** | {sla_str} |",
            "",
            f"**Description**",
            "",
            e.get("description", ""),
            "",
            f"**Impact**",
            "",
            e.get("impact", ""),
            "",
            f"**Evidence**",
            "",
            f"```",
            e.get("evidence", ""),
            f"```",
            "",
            f"**Remediation**",
            "",
            e.get("remediation", ""),
            "",
        ]
        refs = e.get("references", [])
        if refs:
            lines.append("**References**")
            lines.append("")
            for r in refs:
                lines.append(f"- {r}")
            lines.append("")

    return "\n".join(lines)


def default_filename(args, now_str):
    client = args.client.replace(" ", "_")
    return f"{client}_VAPT_Report_{now_str}.{args.format}"


def main():
    args = parse_args()
    catalogue = load_catalogue(args.path)
    entries   = apply_filters(catalogue, args)

    if not entries:
        print("No entries matched the filter criteria.")
        sys.exit(0)

    print(f"Filtered to {len(entries)} entries for '{args.format}' report")

    if args.dry_run:
        from collections import Counter
        for sev, count in Counter(e["severity"] for e in entries).most_common():
            print(f"  {sev:<10} {count}")
        return

    md_payload = build_markdown_payload(entries, args)

    now_str     = datetime.now(timezone.utc).strftime("%Y-%m-%d")
    output_name = args.output or default_filename(args, now_str)
    output_path = Path(output_name)

    # Write Markdown source (always)
    md_path = output_path.with_suffix(".md")
    md_path.write_text(md_payload, encoding="utf-8")
    print(f"✓ Markdown source written: {md_path}")

    # Format-specific instructions printed for Claude to action
    format_instructions = {
        "pdf":  "→ Read /mnt/skills/public/pdf/SKILL.md then render the Markdown source to PDF.",
        "docx": "→ Read /mnt/skills/public/docx/SKILL.md then render the Markdown source to DOCX.",
        "xlsx": "→ Read /mnt/skills/public/xlsx/SKILL.md then build the risk register from the JSON payload.",
        "pptx": "→ Read /mnt/skills/public/pptx/SKILL.md then build the slide deck from the Markdown source.",
        "md":   "→ Markdown report is ready — no further conversion needed.",
    }

    print(format_instructions.get(args.format, ""))

    # Write JSON payload (useful for XLSX/PPTX skills)
    json_path = output_path.with_suffix(".data.json")
    json_path.write_text(
        json.dumps(entries, indent=2, ensure_ascii=False),
        encoding="utf-8"
    )
    print(f"✓ JSON data payload written: {json_path}")


if __name__ == "__main__":
    main()
