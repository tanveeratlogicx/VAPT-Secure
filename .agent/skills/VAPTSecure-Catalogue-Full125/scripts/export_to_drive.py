#!/usr/bin/env python3
"""
export_to_drive.py
------------------
Export VAPT reports (PDF, DOCX, XLSX) to a Google Drive folder.

Prerequisites:
    pip install --upgrade google-auth google-auth-oauthlib google-api-python-client

    1. Create a service account in Google Cloud Console with Drive API enabled.
    2. Download the service account JSON key.
    3. Share the target Drive folder with the service account email.

Usage:
    python scripts/export_to_drive.py \
        --file "Hermasnet_VAPT_Report_2024-03-10.pdf" \
        --folder-id "1aBcDeFgHiJkLmNoPqRsTuVwXyZ" \
        --credentials credentials/service_account.json

    # Upload multiple files
    python scripts/export_to_drive.py \
        --file "report.pdf" "risk_register.xlsx" \
        --folder-id "1aBcDeFgHiJkLmNoPqRsTuVwXyZ" \
        --credentials credentials/service_account.json

    # Dry run
    python scripts/export_to_drive.py --file "report.pdf" --dry-run
"""

import argparse
import sys
from pathlib import Path

MIME_TYPES = {
    ".pdf":  "application/pdf",
    ".docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    ".xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    ".pptx": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    ".md":   "text/markdown",
    ".json": "application/json",
}


def parse_args():
    p = argparse.ArgumentParser(description="Export VAPT reports to Google Drive")
    p.add_argument("--file",        nargs="+", required=True,
                   help="File(s) to upload")
    p.add_argument("--folder-id",   help="Google Drive folder ID")
    p.add_argument("--credentials", type=Path,
                   default=Path("credentials/service_account.json"),
                   help="Path to service account JSON key")
    p.add_argument("--dry-run",     action="store_true",
                   help="Preview without uploading")
    return p.parse_args()


def upload_file(service, file_path: Path, folder_id: str) -> str:
    """Upload a single file to Drive and return its web view URL."""
    try:
        from googleapiclient.http import MediaFileUpload
    except ImportError:
        raise ImportError(
            "google-api-python-client is not installed.\n"
            "Run: pip install google-api-python-client google-auth"
        )

    mime = MIME_TYPES.get(file_path.suffix.lower(), "application/octet-stream")
    metadata = {"name": file_path.name}
    if folder_id:
        metadata["parents"] = [folder_id]

    media = MediaFileUpload(str(file_path), mimetype=mime, resumable=True)
    result = service.files().create(
        body=metadata,
        media_body=media,
        fields="id,webViewLink",
    ).execute()

    return result.get("webViewLink", f"https://drive.google.com/file/d/{result['id']}/view")


def build_service(credentials_path: Path):
    """Build an authenticated Google Drive service."""
    try:
        from google.oauth2 import service_account
        from googleapiclient.discovery import build
    except ImportError:
        raise ImportError(
            "Required packages not installed.\n"
            "Run: pip install google-auth google-auth-oauthlib google-api-python-client"
        )

    creds = service_account.Credentials.from_service_account_file(
        str(credentials_path),
        scopes=["https://www.googleapis.com/auth/drive.file"],
    )
    return build("drive", "v3", credentials=creds)


def main():
    args = parse_args()
    files = [Path(f) for f in args.file]

    # Validate files exist
    missing = [f for f in files if not f.exists()]
    if missing:
        print("✗ Files not found:")
        for m in missing:
            print(f"  {m}")
        sys.exit(1)

    if args.dry_run:
        print("[DRY RUN] Would upload:")
        for f in files:
            mime = MIME_TYPES.get(f.suffix.lower(), "application/octet-stream")
            size = f.stat().st_size
            print(f"  {f.name}  ({mime}, {size:,} bytes)")
        print(f"  → Destination folder: {args.folder_id or '(root)'}")
        return

    service = build_service(args.credentials)

    for file_path in files:
        try:
            url = upload_file(service, file_path, args.folder_id)
            print(f"✓ Uploaded: {file_path.name}")
            print(f"  → {url}")
        except Exception as exc:
            print(f"✗ Failed to upload {file_path.name}: {exc}")


if __name__ == "__main__":
    main()
