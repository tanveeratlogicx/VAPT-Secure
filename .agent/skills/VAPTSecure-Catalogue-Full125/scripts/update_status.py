#!/usr/bin/env python3
"""
update_status.py
----------------
Write status changes back to the catalogue with a mandatory audit trail.

Usage:
    python scripts/update_status.py \
        --id VAPT-042 \
        --status Resolved \
        --note "Patched in plugin v2.1.4 – verified by re-scan 2024-03-15"

    python scripts/update_status.py \
        --id VAPT-001 \
        --status "In Progress" \
        --note "WAF rule deployed as interim mitigation" \
        --actor "analyst@hermasnet.com"
"""

import argparse
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

from load_catalogue import DEFAULT_PATH, load_catalogue

VALID_STATUSES = {"Open", "In Progress", "Resolved", "Accepted Risk", "False Positive"}


def parse_args():
    p = argparse.ArgumentParser(description="Update finding status with audit trail")
    p.add_argument("--path",   type=Path, default=DEFAULT_PATH)
    p.add_argument("--id",     required=True, help="Risk ID to update (e.g. VAPT-042)")
    p.add_argument("--status", required=True, help=f"New status. One of: {VALID_STATUSES}")
    p.add_argument("--note",   required=True, help="Mandatory audit note explaining the change")
    p.add_argument("--actor",  default="system",
                   help="Who is making the change (email or name)")
    return p.parse_args()


def main():
    args = parse_args()

    if args.status not in VALID_STATUSES:
        print(f"✗ Invalid status '{args.status}'. Must be one of:\n  {VALID_STATUSES}")
        sys.exit(1)

    if not args.note.strip():
        print("✗ --note is required and cannot be empty")
        sys.exit(1)

    # Load raw JSON to preserve structure
    if not args.path.exists():
        print(f"✗ Catalogue not found: {args.path}")
        sys.exit(1)

    with open(args.path, encoding="utf-8") as fh:
        raw = json.load(fh)

    entries = raw if isinstance(raw, list) else raw.get("risks") or raw.get("entries", [])

    # Find the target entry
    target = next((e for e in entries if e.get("risk_id") == args.id.upper()), None)
    if target is None:
        print(f"✗ Risk ID '{args.id}' not found in catalogue")
        sys.exit(1)

    old_status = target.get("status", "Unknown")

    if old_status == args.status:
        print(f"ℹ  {args.id} is already '{args.status}' — no change needed")
        return

    # Build audit trail entry
    trail_entry = {
        "timestamp":   datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "actor":       args.actor,
        "from_status": old_status,
        "to_status":   args.status,
        "note":        args.note.strip(),
    }

    # Apply changes
    target["status"] = args.status

    if args.status == "Resolved":
        target["resolved_at"] = trail_entry["timestamp"]

    if "audit_trail" not in target:
        target["audit_trail"] = []
    target["audit_trail"].append(trail_entry)

    # Write back
    with open(args.path, "w", encoding="utf-8") as fh:
        json.dump(raw, fh, indent=2, ensure_ascii=False)

    print(f"✓ {args.id}: '{old_status}' → '{args.status}'")
    print(f"  Actor    : {args.actor}")
    print(f"  Note     : {args.note}")
    print(f"  Timestamp: {trail_entry['timestamp']}")
    print(f"  Catalogue written: {args.path}")


if __name__ == "__main__":
    main()
