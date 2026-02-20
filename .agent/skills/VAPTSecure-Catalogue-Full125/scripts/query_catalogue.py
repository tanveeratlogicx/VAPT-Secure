#!/usr/bin/env python3
"""
query_catalogue.py
------------------
Filter and search the VAPT-Risk-Catalogue-Full-125-v3.4.1 dataset.

Usage examples:
    python scripts/query_catalogue.py --severity Critical
    python scripts/query_catalogue.py --severity Critical,High --status Open
    python scripts/query_catalogue.py --category Injection --min-cvss 7.0
    python scripts/query_catalogue.py --id VAPT-042
    python scripts/query_catalogue.py --keyword "file upload"
    python scripts/query_catalogue.py --owasp A03:2021
    python scripts/query_catalogue.py --cwe CWE-89
    python scripts/query_catalogue.py --status Open --min-cvss 7.0 --json
"""

import argparse
import json
import sys
from pathlib import Path

from load_catalogue import DEFAULT_PATH, load_catalogue


def parse_args():
    p = argparse.ArgumentParser(description="Query VAPT Catalogue Full-125")
    p.add_argument("--path", type=Path, default=DEFAULT_PATH)
    p.add_argument("--id",       help="Filter by risk_id (exact, e.g. VAPT-042)")
    p.add_argument("--severity", help="Comma-separated severities (Critical,High,...)")
    p.add_argument("--status",   help="Comma-separated statuses (Open,'In Progress',...)")
    p.add_argument("--category", help="Category substring match")
    p.add_argument("--keyword",  help="Full-text search across title/description/tags")
    p.add_argument("--owasp",    help="OWASP ref (e.g. A03:2021)")
    p.add_argument("--cwe",      help="CWE ID (e.g. CWE-89)")
    p.add_argument("--min-cvss", type=float, default=0.0,
                   help="Minimum CVSS score (inclusive)")
    p.add_argument("--max-cvss", type=float, default=10.0,
                   help="Maximum CVSS score (inclusive)")
    p.add_argument("--json",     action="store_true",
                   help="Output raw JSON instead of table")
    p.add_argument("--full",     action="store_true",
                   help="Show all fields (not just summary columns)")
    return p.parse_args()


def filter_catalogue(catalogue: list[dict], args) -> list[dict]:
    results = catalogue

    if args.id:
        results = [e for e in results if e.get("risk_id") == args.id.upper()]

    if args.severity:
        sevs = {s.strip() for s in args.severity.split(",")}
        results = [e for e in results if e.get("severity") in sevs]

    if args.status:
        statuses = {s.strip() for s in args.status.split(",")}
        results = [e for e in results if e.get("status") in statuses]

    if args.category:
        kw = args.category.lower()
        results = [e for e in results
                   if kw in (e.get("category") or "").lower()
                   or kw in (e.get("sub_category") or "").lower()]

    if args.keyword:
        kw = args.keyword.lower()
        def matches(e):
            haystack = " ".join([
                e.get("title", ""),
                e.get("description", ""),
                e.get("impact", ""),
                " ".join(e.get("tags", [])),
            ]).lower()
            return kw in haystack
        results = [e for e in results if matches(e)]

    if args.owasp:
        ref = args.owasp.upper()
        results = [e for e in results if e.get("owasp_ref", "").upper() == ref]

    if args.cwe:
        cwe = args.cwe.upper()
        results = [e for e in results if e.get("cwe_id", "").upper() == cwe]

    results = [e for e in results
               if args.min_cvss <= e.get("cvss_score", 0.0) <= args.max_cvss]

    # Sort: by CVSS descending
    results.sort(key=lambda e: e.get("cvss_score", 0.0), reverse=True)

    return results


SEV_ORDER = {"Critical": 0, "High": 1, "Medium": 2, "Low": 3, "Info": 4}
SEV_COLOUR = {
    "Critical": "\033[91m",  # red
    "High":     "\033[93m",  # yellow
    "Medium":   "\033[94m",  # blue
    "Low":      "\033[96m",  # cyan
    "Info":     "\033[37m",  # grey
}
RESET = "\033[0m"


def print_table(results: list[dict], full: bool = False):
    if not results:
        print("No matching findings.")
        return

    if full:
        for e in results:
            sev = e.get("severity", "?")
            col = SEV_COLOUR.get(sev, "")
            print(f"\n{'═'*70}")
            print(f"{col}[{e.get('risk_id')}] {e.get('title')}{RESET}")
            print(f"  Severity  : {col}{sev}{RESET}  (CVSS {e.get('cvss_score')}  {e.get('cvss_vector','')})")
            print(f"  Category  : {e.get('category')} / {e.get('sub_category','')}")
            print(f"  OWASP     : {e.get('owasp_ref','')}   CWE: {e.get('cwe_id','')}")
            print(f"  Asset     : {e.get('affected_asset')}")
            print(f"  Status    : {e.get('status')}")
            print(f"\n  Description:\n  {e.get('description','')}")
            print(f"\n  Impact:\n  {e.get('impact','')}")
            print(f"\n  Evidence:\n  {e.get('evidence','')}")
            print(f"\n  Remediation:\n  {e.get('remediation','')}")
            refs = e.get("references", [])
            if refs:
                print(f"\n  References:")
                for r in refs:
                    print(f"    - {r}")
        print(f"\n{'═'*70}")
    else:
        # Summary table
        col_widths = [9, 42, 6, 25, 16]
        headers = ["Risk ID", "Title", "CVSS", "Affected Asset", "Status"]
        sep = "  ".join("─" * w for w in col_widths)
        hdr = "  ".join(h.ljust(w) for h, w in zip(headers, col_widths))
        print(f"\n{hdr}")
        print(sep)
        for e in results:
            sev = e.get("severity", "?")
            col = SEV_COLOUR.get(sev, "")
            row = [
                e.get("risk_id", ""),
                e.get("title", "")[:40],
                str(e.get("cvss_score", "")),
                e.get("affected_asset", "")[:23],
                e.get("status", ""),
            ]
            line = "  ".join(v.ljust(w) for v, w in zip(row, col_widths))
            print(f"{col}{line}{RESET}")
        print(f"\n{len(results)} finding(s) returned.")


def main():
    args = parse_args()
    catalogue = load_catalogue(args.path)
    results   = filter_catalogue(catalogue, args)

    if args.json:
        print(json.dumps(results, indent=2, ensure_ascii=False))
    else:
        print_table(results, full=args.full)


if __name__ == "__main__":
    main()
