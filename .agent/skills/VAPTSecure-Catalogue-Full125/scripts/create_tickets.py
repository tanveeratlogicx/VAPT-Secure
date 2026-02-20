#!/usr/bin/env python3
"""
create_tickets.py
-----------------
Map VAPT findings to JIRA or Linear tickets.

Usage:
    # JIRA
    python scripts/create_tickets.py \
        --ids "VAPT-001,VAPT-007,VAPT-019" \
        --platform jira \
        --project HERMASNET \
        --assignee security-team \
        --jira-url https://hermasnet.atlassian.net \
        --jira-token YOUR_API_TOKEN \
        --jira-email analyst@hermasnet.com

    # Linear
    python scripts/create_tickets.py \
        --ids "VAPT-001,VAPT-007" \
        --platform linear \
        --team-id YOUR_TEAM_ID \
        --linear-token YOUR_LINEAR_API_KEY

    # Dry run (preview payload without creating)
    python scripts/create_tickets.py --ids "VAPT-001" --platform jira --dry-run
"""

import argparse
import json
import sys
from datetime import datetime, timezone, timedelta
from pathlib import Path

from load_catalogue import DEFAULT_PATH, load_catalogue

SLA_DAYS = {"Critical": 1, "High": 7, "Medium": 30, "Low": 90, "Info": 0}

JIRA_PRIORITY_MAP = {
    "Critical": "Highest",
    "High":     "High",
    "Medium":   "Medium",
    "Low":      "Low",
    "Info":     "Lowest",
}

LINEAR_PRIORITY_MAP = {
    "Critical": 1,  # Urgent
    "High":     2,  # High
    "Medium":   3,  # Medium
    "Low":      4,  # Low
    "Info":     0,  # No priority
}


def parse_args():
    p = argparse.ArgumentParser(description="Create JIRA/Linear tickets from VAPT findings")
    p.add_argument("--path",         type=Path, default=DEFAULT_PATH)
    p.add_argument("--ids",          required=True,
                   help="Comma-separated risk IDs (e.g. VAPT-001,VAPT-007)")
    p.add_argument("--platform",     choices=["jira", "linear"], default="jira")
    p.add_argument("--project",      help="JIRA project key (e.g. HERMASNET)")
    p.add_argument("--assignee",     help="JIRA assignee username or Linear member ID")
    p.add_argument("--jira-url",     help="JIRA base URL")
    p.add_argument("--jira-token",   help="JIRA API token")
    p.add_argument("--jira-email",   help="JIRA account email")
    p.add_argument("--team-id",      help="Linear team ID")
    p.add_argument("--linear-token", help="Linear API key")
    p.add_argument("--dry-run",      action="store_true",
                   help="Print payloads without calling any API")
    return p.parse_args()


def build_jira_payload(entry: dict, project: str, assignee: str = None) -> dict:
    sev      = entry.get("severity", "Medium")
    sla_days = SLA_DAYS.get(sev, 30)
    due_date = (datetime.now(timezone.utc) + timedelta(days=sla_days)).strftime("%Y-%m-%d")

    description = (
        f"*{entry['risk_id']} · {entry['title']}*\n\n"
        f"*Severity:* {sev} (CVSS {entry['cvss_score']} — {entry.get('cvss_vector','')})\n"
        f"*OWASP:* {entry.get('owasp_ref','N/A')}  *CWE:* {entry.get('cwe_id','N/A')}\n"
        f"*Affected Asset:* {entry['affected_asset']}\n\n"
        f"*Description:*\n{entry['description']}\n\n"
        f"*Impact:*\n{entry['impact']}\n\n"
        f"*Evidence:*\n{{code}}\n{entry['evidence']}\n{{code}}\n\n"
        f"*Remediation:*\n{entry['remediation']}"
    )

    payload = {
        "fields": {
            "project":     {"key": project},
            "summary":     f"[VAPT] {entry['risk_id']}: {entry['title']}",
            "description": description,
            "issuetype":   {"name": "Bug"},
            "priority":    {"name": JIRA_PRIORITY_MAP.get(sev, "Medium")},
            "duedate":     due_date,
            "labels":      ["vapt", "security", sev.lower()],
        }
    }
    if assignee:
        payload["fields"]["assignee"] = {"name": assignee}

    return payload


def build_linear_payload(entry: dict, team_id: str, assignee_id: str = None) -> dict:
    sev      = entry.get("severity", "Medium")
    sla_days = SLA_DAYS.get(sev, 30)
    due_date = (datetime.now(timezone.utc) + timedelta(days=sla_days)).isoformat()

    description = (
        f"## {entry['risk_id']} · {entry['title']}\n\n"
        f"**Severity:** {sev} (CVSS {entry['cvss_score']})\n"
        f"**OWASP:** {entry.get('owasp_ref','N/A')} | "
        f"**CWE:** {entry.get('cwe_id','N/A')}\n"
        f"**Asset:** `{entry['affected_asset']}`\n\n"
        f"### Description\n{entry['description']}\n\n"
        f"### Impact\n{entry['impact']}\n\n"
        f"### Evidence\n```\n{entry['evidence']}\n```\n\n"
        f"### Remediation\n{entry['remediation']}"
    )

    payload = {
        "teamId":      team_id,
        "title":       f"[VAPT] {entry['risk_id']}: {entry['title']}",
        "description": description,
        "priority":    LINEAR_PRIORITY_MAP.get(sev, 3),
        "dueDate":     due_date,
    }
    if assignee_id:
        payload["assigneeId"] = assignee_id

    return payload


def create_jira_ticket(payload, base_url, email, token):
    """POST to JIRA REST API v3."""
    import base64
    import urllib.request

    auth = base64.b64encode(f"{email}:{token}".encode()).decode()
    url  = f"{base_url.rstrip('/')}/rest/api/3/issue"
    data = json.dumps(payload).encode("utf-8")

    req = urllib.request.Request(url, data=data, headers={
        "Authorization": f"Basic {auth}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    })

    with urllib.request.urlopen(req) as resp:
        result = json.loads(resp.read())
    return result.get("key", "?")


def create_linear_ticket(payload, token):
    """POST to Linear GraphQL API."""
    import urllib.request

    query = """
    mutation CreateIssue($input: IssueCreateInput!) {
      issueCreate(input: $input) { success issue { id identifier url } }
    }
    """
    body  = json.dumps({"query": query, "variables": {"input": payload}}).encode("utf-8")
    req   = urllib.request.Request(
        "https://api.linear.app/graphql",
        data=body,
        headers={"Authorization": token, "Content-Type": "application/json"},
    )
    with urllib.request.urlopen(req) as resp:
        result = json.loads(resp.read())
    return result["data"]["issueCreate"]["issue"]["identifier"]


def main():
    args = parse_args()
    catalogue = load_catalogue(args.path)
    ids       = {i.strip().upper() for i in args.ids.split(",")}
    entries   = [e for e in catalogue if e.get("risk_id") in ids]

    if not entries:
        print(f"✗ None of the specified IDs found: {ids}")
        sys.exit(1)

    print(f"Creating {len(entries)} ticket(s) on {args.platform.upper()}...\n")

    for entry in entries:
        if args.platform == "jira":
            payload = build_jira_payload(entry, args.project or "VAPT", args.assignee)
        else:
            payload = build_linear_payload(entry, args.team_id or "", args.assignee)

        if args.dry_run:
            print(f"[DRY RUN] {entry['risk_id']} payload:")
            print(json.dumps(payload, indent=2))
            print()
            continue

        try:
            if args.platform == "jira":
                ticket_id = create_jira_ticket(
                    payload, args.jira_url, args.jira_email, args.jira_token
                )
            else:
                ticket_id = create_linear_ticket(payload, args.linear_token)
            print(f"✓ {entry['risk_id']} → {ticket_id}")
        except Exception as exc:
            print(f"✗ {entry['risk_id']} failed: {exc}")


if __name__ == "__main__":
    main()
