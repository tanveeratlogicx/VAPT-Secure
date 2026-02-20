#!/usr/bin/env python3
"""
load_catalogue.py
-----------------
Load and perform basic assertions on VAPT-Risk-Catalogue-Full-125-v3.4.1.json.

Usage:
    python scripts/load_catalogue.py [--path PATH]
"""

import argparse
import json
from pathlib import Path

DEFAULT_PATH = Path(
    r"T:\~\Local925 Sites\hermasnet\app\public"
    r"\wp-content\plugins\VAPT-Secure\data"
    r"\VAPT-Risk-Catalogue-Full-125-v3.4.1.json"
)

EXPECTED_COUNT = 125


def load_catalogue(path: Path = DEFAULT_PATH) -> list[dict]:
    """Load the JSON catalogue, handling both bare-array and root-object forms."""
    if not path.exists():
        raise FileNotFoundError(
            f"Catalogue not found at:\n  {path}\n"
            "Check the path or pass --path <absolute_path>"
        )

    with open(path, encoding="utf-8") as fh:
        raw = json.load(fh)

    # Support root-object form: {"risks": [...]} or {"entries": [...]}
    if isinstance(raw, dict):
        entries = raw.get("risks") or raw.get("entries") or []
    else:
        entries = raw  # bare array

    assert len(entries) == EXPECTED_COUNT, (
        f"Expected {EXPECTED_COUNT} entries, found {len(entries)}"
    )

    return entries


def main():
    parser = argparse.ArgumentParser(description="Load VAPT Catalogue Full-125")
    parser.add_argument("--path", type=Path, default=DEFAULT_PATH,
                        help="Override default catalogue path")
    args = parser.parse_args()

    catalogue = load_catalogue(args.path)
    print(f"✓ Loaded {len(catalogue)} entries from:\n  {args.path}")

    # Quick stats
    from collections import Counter
    sev_counts = Counter(e.get("severity", "Unknown") for e in catalogue)
    status_counts = Counter(e.get("status", "Unknown") for e in catalogue)

    print("\nSeverity breakdown:")
    for sev in ["Critical", "High", "Medium", "Low", "Info"]:
        print(f"  {sev:<10} {sev_counts.get(sev, 0)}")

    print("\nStatus breakdown:")
    for st, count in status_counts.most_common():
        print(f"  {st:<20} {count}")

    return catalogue


if __name__ == "__main__":
    main()
