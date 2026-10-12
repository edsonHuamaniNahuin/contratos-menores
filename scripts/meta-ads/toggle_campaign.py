#!/usr/bin/env python3
"""
Toggle de la campaña Meta (activar/pausar). Pensado para arranques programados
en horario laboral (B2B) y operaciones manuales rápidas.

Uso:
  python toggle_campaign.py --activate --campaign-id 120247999030430108 --token-file /root/.meta-token
  python toggle_campaign.py --pause
"""

import argparse
import json
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

DEFAULT_CAMPAIGN = "120247999030430108"


def main():
    parser = argparse.ArgumentParser()
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--activate", action="store_true")
    group.add_argument("--pause", action="store_true")
    parser.add_argument("--campaign-id", default=DEFAULT_CAMPAIGN)
    parser.add_argument("--token", default=None)
    parser.add_argument("--token-file", default=None)
    args = parser.parse_args()

    token = args.token
    if not token and args.token_file:
        token = Path(args.token_file).read_text(encoding="utf-8").strip()
    if not token:
        print(json.dumps({"error": "token requerido (--token o --token-file)"}))
        sys.exit(1)

    status = "ACTIVE" if args.activate else "PAUSED"
    data = urllib.parse.urlencode({"status": status, "access_token": token}).encode()
    req = urllib.request.Request(
        f"https://graph.facebook.com/v26.0/{args.campaign_id}", data=data, method="POST")

    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            print(json.dumps({"ok": True, "campaign": args.campaign_id, "status": status,
                              "response": r.read().decode()[:200]}))
    except urllib.error.HTTPError as e:
        print(json.dumps({"ok": False, "status": status, "error": e.read().decode()[:300]}))
        sys.exit(1)


if __name__ == "__main__":
    main()
