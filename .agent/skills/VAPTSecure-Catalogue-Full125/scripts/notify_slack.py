#!/usr/bin/env python3
"""
notify_slack.py
---------------
Send formatted Slack notifications for VAPT findings — especially
Critical SLA alerts.

Usage:
    python scripts/notify_slack.py \
        --channel "#security-alerts" \
        --severity Critical \
        --webhook https://hooks.slack.com/services/T.../B.../xxx

    python scripts/notify_slack.py \
        --channel "#security-alerts" \
        --ids "VAPT-001,VAPT-007,VAPT-019" \
        --webhook https://hooks.slack.com/services/T.../B.../xxx

    python scripts/notify_slack.py \
        --summary \
        --webhook https://hooks.slack.com/services/T.../B.../xxx

Environment variable alternative (avoids putting webhook in shell history):
    export VAPT_SLACK_WEBHOOK="https://hooks.slack.com/services/T.../..."
    python scripts/notify_slack.py --channel "#sec" --severity Critical
"""

import argparse
import json
import os
import sys
import urllib.request
from collections import Counter
from pathlib import Path

from load_catalogue import DEFAULT_PATH, load_catalogue

SLA_DAYS = {"Critical": 1, "High": 7, "Medium": 30, "Low": 90, "Info": 0}

SEVERITY_EMOJI = {
    "Critical": ":red_circle:",
    "High":     ":orange_circle:",
    "Medium":   ":yellow_circle:",
    "Low":      ":blue_circle:",
    "Info":     ":white_circle:",
}


def parse_args():
    p = argparse.ArgumentParser(description="Slack VAPT Notifier")
    p.add_argument("--path",     type=Path, default=DEFAULT_PATH)
    p.add_argument("--webhook",  default=os.getenv("VAPT_SLACK_WEBHOOK"),
                   help="Slack Incoming Webhook URL")
    p.add_argument("--channel",  default="#security-alerts",
                   help="Slack channel (overrides webhook default)")
    p.add_argument("--severity", help="Filter by severity for notification")
    p.add_argument("--ids",      help="Specific risk IDs to notify about")
    p.add_argument("--summary",  action="store_true",
                   help="Send a full catalogue summary digest")
    p.add_argument("--dry-run",  action="store_true",
                   help="Print payload without sending")
    return p.parse_args()


def build_finding_block(entry: dict) -> dict:
    sev       = entry.get("severity", "Info")
    emoji     = SEVERITY_EMOJI.get(sev, ":white_circle:")
    sla_days  = SLA_DAYS.get(sev, 0)
    sla_str   = f"{sla_days}d SLA" if sla_days else "Best effort"

    return {
        "type": "section",
        "text": {
            "type": "mrkdwn",
            "text": (
                f"{emoji} *{entry['risk_id']}* — {entry['title']}\n"
                f">Severity: *{sev}* (CVSS {entry['cvss_score']})  |  "
                f"Asset: `{entry['affected_asset']}`  |  "
                f"Status: {entry['status']}  |  {sla_str}"
            ),
        },
    }


def build_summary_payload(catalogue: list[dict], channel: str) -> dict:
    sev_counts    = Counter(e.get("severity") for e in catalogue)
    status_counts = Counter(e.get("status")   for e in catalogue)
    open_critical = sum(1 for e in catalogue
                        if e.get("severity") == "Critical" and e.get("status") == "Open")

    text_lines = ["*VAPT Risk Catalogue — Digest*\n"]
    for sev in ["Critical", "High", "Medium", "Low", "Info"]:
        emoji = SEVERITY_EMOJI.get(sev, "")
        text_lines.append(f"{emoji} {sev}: {sev_counts.get(sev, 0)}")

    text_lines.append(f"\n*Status Breakdown*")
    for st, count in status_counts.most_common():
        text_lines.append(f"• {st}: {count}")

    if open_critical:
        text_lines.append(f"\n:rotating_light: *{open_critical} Critical finding(s) still OPEN!*")

    return {
        "channel": channel,
        "text":    "VAPT Risk Catalogue Digest",
        "blocks":  [
            {"type": "header", "text": {"type": "plain_text", "text": "VAPT Risk Catalogue Digest"}},
            {"type": "section", "text": {"type": "mrkdwn", "text": "\n".join(text_lines)}},
            {"type": "divider"},
        ],
    }


def build_findings_payload(entries: list[dict], channel: str, title: str) -> dict:
    blocks = [
        {
            "type": "header",
            "text": {"type": "plain_text", "text": f":rotating_light: {title}"},
        },
        {"type": "divider"},
    ]

    for entry in entries[:20]:   # Slack has a 50-block limit; cap at 20 findings
        blocks.append(build_finding_block(entry))

    if len(entries) > 20:
        blocks.append({
            "type": "context",
            "elements": [{"type": "mrkdwn", "text": f"_... and {len(entries) - 20} more findings_"}]
        })

    return {"channel": channel, "text": title, "blocks": blocks}


def post_to_slack(webhook: str, payload: dict) -> bool:
    data = json.dumps(payload).encode("utf-8")
    req  = urllib.request.Request(
        webhook, data=data,
        headers={"Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(req) as resp:
            body = resp.read().decode()
        return body == "ok"
    except Exception as exc:
        print(f"✗ Slack API error: {exc}")
        return False


def main():
    args = parse_args()

    if not args.webhook:
        print("✗ No webhook URL. Pass --webhook or set VAPT_SLACK_WEBHOOK env var.")
        sys.exit(1)

    catalogue = load_catalogue(args.path)

    if args.summary:
        payload = build_summary_payload(catalogue, args.channel)
        title   = "VAPT Risk Catalogue Digest"
    else:
        entries = catalogue
        if args.ids:
            ids     = {i.strip().upper() for i in args.ids.split(",")}
            entries = [e for e in entries if e.get("risk_id") in ids]
        if args.severity:
            sevs    = {s.strip() for s in args.severity.split(",")}
            entries = [e for e in entries if e.get("severity") in sevs]
        if not entries:
            print("No matching findings to notify about.")
            return
        sev_label = args.severity or "Selected"
        title     = f"VAPT Alert — {sev_label} Findings ({len(entries)})"
        payload   = build_findings_payload(entries, args.channel, title)

    if args.dry_run:
        print("[DRY RUN] Slack payload:")
        print(json.dumps(payload, indent=2))
        return

    ok = post_to_slack(args.webhook, payload)
    if ok:
        print(f"✓ Slack notification sent to {args.channel}")
    else:
        print("✗ Slack notification failed")
        sys.exit(1)


if __name__ == "__main__":
    main()
