#!/usr/bin/env python3
"""
Refactor v2 de campañas Meta — Vigilante SEACE (estructura documentada en
documents/02-marketing-adquisicion/DOCUMENTACION_CAMPANA_META_2026-10.md).

Qué hace:
  1. (Opcional --delete-old) Elimina las 3 campañas PAUSED de la v1 (MA-Alertas/Software/Vigentes).
  2. Crea 1 campaña de TESTING (OUTCOME_TRAFFIC / LINK_CLICKS — fase de aprendizaje; el pixel
     mide Lead en paralelo) con 3 ad sets (E1/E2/E3), cada uno con su landing.
  3. Sube los 6 flyers (4:5 + 1:1) y crea 2 anuncios por ad set (6 en total), PAUSED.

Uso:
  python refactor_campaigns_v2.py --token TOKEN --ad-account act_XXX --page-id XXX --pixel-id XXX --delete-old
"""

import argparse
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.resolve()))
import create_campaigns as cc  # noqa: E402  (reutiliza api/upload_image/search_interest_id)

OLD_CAMPAIGNS = [
    "120247998776920108",  # MA-Alertas v1
    "120247998778070108",  # MA-Software v1
    "120247998779450108",  # MA-Vigentes v1
]

CAMPAIGN_NAME = "META_Traffic_Construccion_E1E2E3_2026Q4"

# landing -> (adset_name, intereses, flyer 4:5, flyer 1:1)
ADSETS = [
    {
        "adset": "META_Traffic_E1_Alertas_2026Q4",
        "landing": "https://licitacionesmype.pe/alertas-licitaciones",
        "interest_names": ["Government concepts and practices", "OSCE", "Construction engineering"],
        "flyers": [("4x5", "public/images/ads/ma-alertas-1350.png"), ("1x1", "public/images/ads/ma-alertas-1080.png")],
    },
    {
        "adset": "META_Traffic_E2_Software_2026Q4",
        "landing": "https://licitacionesmype.pe/software-licitaciones",
        "interest_names": ["Government concepts and practices", "OSCE", "Software business"],
        "flyers": [("4x5", "public/images/ads/ma-software-1350.png"), ("1x1", "public/images/ads/ma-software-1080.png")],
    },
    {
        "adset": "META_Traffic_E3_Vigentes_2026Q4",
        "landing": "https://licitacionesmype.pe/licitaciones-vigentes",
        "interest_names": ["Government concepts and practices", "OSCE", "Small business"],
        "flyers": [("4x5", "public/images/ads/ma-vigentes-1350.png"), ("1x1", "public/images/ads/ma-vigentes-1080.png")],
    },
]

DAILY_BUDGET_CENTS = 1000  # S/ 10.00 por ad set -> S/ 30/día total (S/ 900/mes)


def main():
    parser = argparse.ArgumentParser(description="Refactor v2 de campañas Meta")
    parser.add_argument("--token", required=True)
    parser.add_argument("--ad-account", required=True)
    parser.add_argument("--page-id", required=True)
    parser.add_argument("--pixel-id", required=True)
    parser.add_argument("--delete-old", action="store_true", help="Elimina las 3 campañas v1 (PAUSED, sin gasto)")
    parser.add_argument("--config", default=str(Path(__file__).parent / "campaigns_config.json"))
    args = parser.parse_args()

    textos = {c["name"]: c for c in json.loads(Path(args.config).read_text(encoding="utf-8"))["campaigns"]}
    mapa_texto = {
        "META_Traffic_E1_Alertas_2026Q4": textos["MA-Alertas"],
        "META_Traffic_E2_Software_2026Q4": textos["MA-Software"],
        "META_Traffic_E3_Vigentes_2026Q4": textos["MA-Vigentes"],
    }

    if args.delete_old:
        for cid in OLD_CAMPAIGNS:
            try:
                cc.api("DELETE", cid, args.token)
                print(f"  v1 eliminada: {cid}")
            except Exception as e:
                print(f"  !! no se pudo eliminar {cid}: {e}", file=sys.stderr)

    camp = cc.api("POST", f"{args.ad_account}/campaigns", args.token, {
        "name": CAMPAIGN_NAME,
        "objective": "OUTCOME_TRAFFIC",
        "status": "PAUSED",
        "is_adset_budget_sharing_enabled": "false",
        "special_ad_categories": json.dumps([]),
    })
    campaign_id = camp["id"]
    print(f"\n=== {CAMPAIGN_NAME} ===\n  campana: {campaign_id} (PAUSED)")

    resumen = []
    for cfg in ADSETS:
        interest_ids = []
        for nombre in cfg["interest_names"]:
            iid = cc.search_interest_id(args.token, nombre)
            if iid:
                interest_ids.append({"id": iid})
                print(f"    interes OK: {nombre} -> {iid}")

        targeting = {
            "age_min": 25,
            "age_max": 60,
            "geo_locations": {"countries": ["PE"]},
            "targeting_automation": {"advantage_audience": 0},
        }
        if interest_ids:
            targeting["flexible_spec"] = [{"interests": interest_ids}]

        adset = cc.api("POST", f"{args.ad_account}/adsets", args.token, {
            "name": cfg["adset"],
            "campaign_id": campaign_id,
            "daily_budget": str(DAILY_BUDGET_CENTS),
            "billing_event": "IMPRESSIONS",
            "optimization_goal": "LINK_CLICKS",
            "bid_strategy": "LOWEST_COST_WITHOUT_CAP",
            "targeting": json.dumps(targeting),
            "status": "PAUSED",
        })
        adset_id = adset["id"]
        print(f"  adset: {cfg['adset']} -> {adset_id} (S/ 10.00/dia)")

        texto = mapa_texto[cfg["adset"]]
        ads = []
        for etiqueta, imagen in cfg["flyers"]:
            h = cc.upload_image(args.token, args.ad_account, imagen)
            creative = cc.api("POST", f"{args.ad_account}/adcreatives", args.token, {
                "name": f"{texto['name']} - Flyer {etiqueta}",
                "object_story_spec": json.dumps({
                    "page_id": args.page_id,
                    "link_data": {
                        "link": cfg["landing"],
                        "message": texto["primary_text"],
                        "name": texto["headline"],
                        "description": "Vigilante SEACE",
                        "call_to_action": {"type": "LEARN_MORE"},
                        "image_hash": h,
                    },
                }),
            })
            ad = cc.api("POST", f"{args.ad_account}/ads", args.token, {
                "name": f"{texto['name']} - {etiqueta}",
                "adset_id": adset_id,
                "creative": json.dumps({"creative_id": creative["id"]}),
                "status": "PAUSED",
            })
            ads.append(ad["id"])
            print(f"    anuncio {etiqueta}: {ad['id']} (PAUSED)")
        resumen.append({"adset": cfg["adset"], "adset_id": adset_id, "ads": ads})

    print("\n=== RESUMEN ===")
    print(json.dumps({"campaign": campaign_id, "adsets": resumen}, indent=1))


if __name__ == "__main__":
    main()
