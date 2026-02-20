#!/usr/bin/env python3
"""
validate_catalogue.py
---------------------
Full schema validation for VAPT-Risk-Catalogue-Full-125-v3.4.1.json.
Flags every entry that violates the v3.4.1 contract.

Usage:
    python scripts/validate_catalogue.py [--path PATH] [--strict]
"""

import argparse
import json
import re
import sys
from pathlib import Path

from load_catalogue import DEFAULT_PATH, load_catalogue

# ── Schema constants ──────────────────────────────────────────────────────────

REQUIRED_FIELDS = [
    "risk_id", "title", "category", "severity",
    "cvss_score", "cvss_vector", "description",
    "impact", "evidence", "remediation",
    "affected_asset", "discovered_at", "status",
]

SEVERITY_VALUES = {"Critical", "High", "Medium", "Low", "Info"}
STATUS_VALUES   = {"Open", "In Progress", "Resolved", "Accepted Risk", "False Positive"}

SEVERITY_CVSS_RANGES = {
    "Critical": (9.0, 10.0),
    "High":     (7.0,  8.9),
    "Medium":   (4.0,  6.9),
    "Low":      (0.1,  3.9),
    "Info":     (0.0,  0.0),
}

RISK_ID_PATTERN = re.compile(r"^VAPT-\d{3}$")
CVSS_VECTOR_PATTERN = re.compile(r"^CVSS:3\.[01]/")
ISO8601_PATTERN = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$")

VALID_CATEGORIES = {
    "Injection", "Broken Authentication", "Sensitive Data Exposure",
    "XML External Entities", "Broken Access Control",
    "Security Misconfiguration", "Cross-Site Scripting",
    "Insecure Deserialization", "Known Vulnerable Components",
    "Insufficient Logging", "Server-Side Request Forgery",
    "Cryptographic Failures", "Software Integrity Failures",
    "Business Logic", "File Upload", "Authentication Failures",
}

# ── Validator ─────────────────────────────────────────────────────────────────

def validate_entry(entry: dict, strict: bool = False) -> list[str]:
    """Return a list of violation strings (empty = valid)."""
    errs = []
    rid = entry.get("risk_id", "<missing>")

    # Required fields
    for field in REQUIRED_FIELDS:
        if field not in entry or entry[field] is None or entry[field] == "":
            errs.append(f"[{rid}] Missing required field: '{field}'")

    # risk_id format
    if "risk_id" in entry and not RISK_ID_PATTERN.match(str(entry["risk_id"])):
        errs.append(f"[{rid}] Invalid risk_id format (expected VAPT-NNN): '{entry['risk_id']}'")

    # severity enum
    if "severity" in entry and entry["severity"] not in SEVERITY_VALUES:
        errs.append(f"[{rid}] Invalid severity '{entry['severity']}'. Must be one of {SEVERITY_VALUES}")

    # status enum
    if "status" in entry and entry["status"] not in STATUS_VALUES:
        errs.append(f"[{rid}] Invalid status '{entry['status']}'. Must be one of {STATUS_VALUES}")

    # cvss_score range + severity alignment
    if "cvss_score" in entry and "severity" in entry:
        score = entry["cvss_score"]
        sev   = entry["severity"]
        if sev in SEVERITY_CVSS_RANGES:
            lo, hi = SEVERITY_CVSS_RANGES[sev]
            if not (lo <= score <= hi):
                errs.append(
                    f"[{rid}] CVSS score {score} is out of range for severity '{sev}' "
                    f"(expected {lo}–{hi})"
                )

    # cvss_vector format
    if "cvss_vector" in entry and not CVSS_VECTOR_PATTERN.match(str(entry.get("cvss_vector", ""))):
        errs.append(f"[{rid}] cvss_vector does not start with 'CVSS:3.x/': '{entry['cvss_vector']}'")

    # discovered_at ISO 8601
    if "discovered_at" in entry and not ISO8601_PATTERN.match(str(entry.get("discovered_at", ""))):
        errs.append(f"[{rid}] discovered_at is not ISO 8601 format: '{entry['discovered_at']}'")

    # category (strict mode)
    if strict and "category" in entry and entry["category"] not in VALID_CATEGORIES:
        errs.append(f"[{rid}] Unknown category '{entry['category']}' (strict mode)")

    # title length
    if "title" in entry and len(entry["title"]) > 120:
        errs.append(f"[{rid}] title exceeds 120 characters ({len(entry['title'])} chars)")

    return errs


def main():
    parser = argparse.ArgumentParser(description="Validate VAPT Catalogue Full-125")
    parser.add_argument("--path", type=Path, default=DEFAULT_PATH)
    parser.add_argument("--strict", action="store_true",
                        help="Enable strict category validation")
    args = parser.parse_args()

    catalogue = load_catalogue(args.path)

    all_errors = []
    for entry in catalogue:
        all_errors.extend(validate_entry(entry, strict=args.strict))

    passed = len(catalogue) - len({e.split("]")[0].lstrip("[") for e in all_errors})
    print(f"Validated {len(catalogue)} entries  "
          f"({'strict' if args.strict else 'normal'} mode)\n")

    if all_errors:
        print(f"✗ {len(all_errors)} violation(s) found:\n")
        for err in all_errors:
            print(f"  {err}")
        sys.exit(1)
    else:
        print(f"✓ {len(catalogue)}/{len(catalogue)} entries passed schema validation")
        print("✗ 0 schema violations found")


if __name__ == "__main__":
    main()
