#!/usr/bin/env python3
"""
Reporte diario de campañas Meta (baseline de seguimiento).
Lee insights por anuncio y resume: gasto, impresiones, alcance, clics, CTR, CPC, CPM, frecuencia y leads.

Uso:
  python daily_report.py --token-file /root/.meta-token
  python daily_report.py --token-file /root/.meta-token --date-preset yesterday
  python daily_report.py --token-file /root/.meta-token --date-preset last_7d
"""

import argparse
import json
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

CAMPAIGN_ID = "120247999030430108"
API = "https://graph.facebook.com/v26.0"
FIELDS = ("campaign_name,adset_name,ad_name,spend,impressions,reach,clicks,ctr,cpc,cpm,frequency,"
          "actions,outbound_clicks")


def fetch(token, preset, adset_only=False):
    params = {
        "fields": FIELDS if not adset_only else FIELDS.replace("ad_name,", ""),
        "date_preset": preset,
        "level": "ad" if not adset_only else "adset",
        "limit": "200",
        "access_token": token,
    }
    url = f"{API}/{CAMPAIGN_ID}/insights?" + urllib.parse.urlencode(params)
    try:
        with urllib.request.urlopen(url, timeout=90) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        return {"error": json.loads(e.read().decode())}


def action_count(row, action_type):
    for a in row.get("actions", []) or []:
        if a.get("action_type") == action_type:
            return int(float(a.get("value", 0)))
    return 0


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--token", default=None)
    parser.add_argument("--token-file", default=None)
    parser.add_argument("--date-preset", default="today",
                        choices=["today", "yesterday", "last_3d", "last_7d", "last_14d", "this_month"])
    parser.add_argument("--json", action="store_true")
    args = parser.parse_args()

    token = args.token or (Path(args.token_file).read_text(encoding="utf-8").strip() if args.token_file else None)
    if not token:
        print("Falta token (--token o --token-file)")
        sys.exit(1)

    data = fetch(token, args.date_preset)
    if "error" in data:
        print(json.dumps(data, ensure_ascii=False))
        sys.exit(1)

    rows = data.get("data", [])
    if args.json:
        print(json.dumps(rows, ensure_ascii=False, indent=2))
        return

    if not rows:
        print(f"[{args.date_preset}] Sin datos de entrega (aún no hay gasto en este periodo).")
        return

    print(f"Reporte Meta · {args.date_preset} · campaña {CAMPAIGN_ID}\n")
    header = f"{'Anuncio':<28} {'Gasto':>7} {'Impr':>8} {'Alcance':>8} {'Clics':>6} {'CTR%':>6} {'CPC':>7} {'CPM':>7} {'Frec':>5} {'Leads':>5}"
    print(header)
    print("-" * len(header))

    totals = {"spend": 0.0, "impressions": 0, "reach": 0, "clicks": 0, "leads": 0}
    for row in rows:
        spend = float(row.get("spend", 0) or 0)
        impr = int(row.get("impressions", 0) or 0)
        reach = int(row.get("reach", 0) or 0)
        clicks = int(row.get("clicks", 0) or 0)
        leads = action_count(row, "lead") + action_count(row, "offsite_conversion.fb_pixel_lead")
        name = (row.get("ad_name") or row.get("adset_name") or "?")[:28]
        ctr = row.get("ctr", "0")
        cpc = row.get("cpc", "0")
        cpm = row.get("cpm", "0")
        freq = row.get("frequency", "0")
        print(f"{name:<28} {spend:>7.2f} {impr:>8} {reach:>8} {clicks:>6} {float(ctr):>6.2f} "
              f"{float(cpc):>7.2f} {float(cpm):>7.2f} {float(freq):>5.2f} {leads:>5}")
        totals["spend"] += spend
        totals["impressions"] += impr
        totals["reach"] += reach
        totals["clicks"] += clicks
        totals["leads"] += leads

    cpl = (totals["spend"] / totals["leads"]) if totals["leads"] else 0
    print("-" * len(header))
    print(f"TOTAL: gasto S/ {totals['spend']:.2f} · impresiones {totals['impressions']} · alcance {totals['reach']} "
          f"· clics {totals['clicks']} · leads {totals['leads']} · CPL S/ {cpl:.2f}")


if __name__ == "__main__":
    main()
