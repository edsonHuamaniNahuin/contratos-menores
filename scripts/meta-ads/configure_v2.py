#!/usr/bin/env python3
"""
Configuración total v2 — Meta Ads Vigilante SEACE.

1. Borra los 6 anuncios v1 (2 por ad set).
2. Crea 9 anuncios (3 versiones por embudo: V1 Dato · V2 Dolor · V3 Alivio) con UTMs (url_tags).
3. Crea audiencias de exclusión (clientes y leads web, emails SHA-256) y las excluye en los 3 ad sets.
4. Intenta registrar la conversión personalizada "Lead" (pixel).
5. Con --activate deja campaña, ad sets y anuncios ACTIVE.

Uso:
  python configure_v2.py --token T --ad-account act_X --page-id X --pixel-id X \
      --campaign-id 120247999030430108 --emails-file emails.json --activate
"""

import argparse
import hashlib
import json
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.resolve()))
import create_campaigns as cc  # noqa: E402

OLD_ADS = [
    "120247999031740108", "120247999032370108",   # E1 v1 4:5 / 1:1
    "120247999033910108", "120247999034210108",   # E2
    "120247999035080108", "120247999035650108",   # E3
]

EMBUDOS = [
    {
        "key": "MA-Alertas",
        "adset_id": "120247999030690108",
        "landing": "https://licitacionesmype.pe/alertas-licitaciones",
        "images": {
            "V1 Dato": "public/images/ads/ma-alertas-1350.png",
            "V2 Dolor": "public/images/ads/e1-v2-1350.png",
            "V3 Alivio": "public/images/ads/e1c-v3-1350.png",
        },
    },
    {
        "key": "MA-Software",
        "adset_id": "120247999032700108",
        "landing": "https://licitacionesmype.pe/software-licitaciones",
        "images": {
            "V1 Dato": "public/images/ads/ma-software-1350.png",
            "V2 Dolor": "public/images/ads/e2-v2-1350.png",
            "V3 Alivio": "public/images/ads/e2c-v3-1350.png",
        },
    },
    {
        "key": "MA-Vigentes",
        "adset_id": "120247999034400108",
        "landing": "https://licitacionesmype.pe/licitaciones-vigentes",
        "images": {
            "V1 Dato": "public/images/ads/ma-vigentes-1350.png",
            "V2 Dolor": "public/images/ads/e3-v2-1350.png",
            "V3 Alivio": "public/images/ads/e3c-v3-1350.png",
        },
    },
]

URL_TAGS = ("utm_source=meta&utm_medium=paid&utm_campaign={{campaign.name}}"
            "&utm_content={{ad.name}}&utm_term={{adset.name}}")


def sha256_email(email: str) -> str:
    return hashlib.sha256(email.strip().lower().encode("utf-8")).hexdigest()


def crear_audiencia(token, ad_account, nombre, emails, descripcion):
    aud = cc.api("POST", f"{ad_account}/customaudiences", token, {
        "name": nombre,
        "subtype": "CUSTOM",
        "customer_file_source": "USER_PROVIDED_ONLY",
        "description": descripcion,
    })
    aud_id = aud["id"]
    hashed = [[sha256_email(e)] for e in emails if e]
    if hashed:
        cc.api("POST", f"{aud_id}/users", token, {
            "payload": json.dumps({"schema": ["EMAIL_SHA256"], "data": hashed}),
        })
    print(f"  audiencia OK: {nombre} -> {aud_id} ({len(hashed)} emails)")
    return aud_id


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--token", required=True)
    parser.add_argument("--ad-account", required=True)
    parser.add_argument("--page-id", required=True)
    parser.add_argument("--pixel-id", required=True)
    parser.add_argument("--campaign-id", required=True)
    parser.add_argument("--emails-file", required=True)
    parser.add_argument("--config", default=str(Path(__file__).parent / "campaigns_config.json"))
    parser.add_argument("--activate", action="store_true")
    args = parser.parse_args()

    textos = {c["name"]: c for c in json.loads(Path(args.config).read_text(encoding="utf-8"))["campaigns"]}
    emails = json.loads(Path(args.emails_file).read_text(encoding="utf-8"))

    # ── 1) Borrar anuncios v1 ──
    print("== 1) Eliminando anuncios v1 ==")
    for ad_id in OLD_ADS:
        try:
            cc.api("DELETE", ad_id, args.token)
            print(f"  borrado: {ad_id}")
        except Exception as e:
            print(f"  !! {ad_id}: {str(e)[:120]}")

    # ── 2) Audiencias de exclusión ──
    print("== 2) Audiencias de exclusión ==")
    audiencias = []
    try:
        audiencias.append(crear_audiencia(args.token, args.ad_account,
                                          "VS Clientes actuales (excluir)", emails.get("clientes", []),
                                          "Usuarios con suscripción paga o histórica"))
    except Exception as e:
        print(f"  !! clientes: {str(e)[:200]}")
    try:
        audiencias.append(crear_audiencia(args.token, args.ad_account,
                                          "VS Leads web (excluir)", emails.get("leads", []),
                                          "Leads capturados por formularios de landing"))
    except Exception as e:
        print(f"  !! leads: {str(e)[:200]}")

    # ── 3) Excluir en ad sets ──
    print("== 3) Aplicando exclusiones a los ad sets ==")
    if audiencias:
        excl = [{"id": a} for a in audiencias]
        for emb in EMBUDOS:
            try:
                data = cc.api("GET", f"{emb['adset_id']}?fields=targeting", args.token)
                targeting = data.get("targeting", {})
                targeting["excluded_custom_audiences"] = excl
                cc.api("POST", emb["adset_id"], args.token, {"targeting": json.dumps(targeting)})
                print(f"  exclusion aplicada: {emb['adset_id']}")
            except Exception as e:
                print(f"  !! exclusion {emb['adset_id']}: {str(e)[:150]}")
    else:
        print("  sin audiencias: no se aplicaron exclusiones")

    # ── 4) Anuncios (3 versiones por embudo) con UTMs ──
    print("== 4) Creando 9 anuncios (V1/V2/V3) con UTMs ==")
    creados = []
    for emb in EMBUDOS:
        texto = textos[emb["key"]]
        for version, imagen in emb["images"].items():
            try:
                h = cc.upload_image(args.token, args.ad_account, imagen)
                creative = cc.api("POST", f"{args.ad_account}/adcreatives", args.token, {
                    "name": f"{emb['key']} - {version}",
                    "url_tags": URL_TAGS,
                    "object_story_spec": json.dumps({
                        "page_id": args.page_id,
                        "link_data": {
                            "link": emb["landing"],
                            "message": texto["primary_text"],
                            "name": texto["headline"],
                            "description": "Vigilante SEACE",
                            "call_to_action": {"type": "LEARN_MORE"},
                            "image_hash": h,
                        },
                    }),
                })
                ad = cc.api("POST", f"{args.ad_account}/ads", args.token, {
                    "name": f"{emb['key']} - {version}",
                    "adset_id": emb["adset_id"],
                    "creative": json.dumps({"creative_id": creative["id"]}),
                    "status": "PAUSED",
                })
                creados.append({"embudo": emb["key"], "version": version, "ad": ad["id"]})
                print(f"  {emb['key']} {version}: ad {ad['id']}")
                time.sleep(1)
            except Exception as e:
                print(f"  !! {emb['key']} {version}: {str(e)[:200]}")

    # ── 5) Conversión personalizada "Lead" ──
    print("== 5) Conversión personalizada Lead ==")
    try:
        conv = cc.api("POST", f"{args.ad_account}/customconversions", args.token, {
            "name": "Lead Vigilante SEACE",
            "event_source": "pixel",
            "pixel_id": args.pixel_id,
            "rule": json.dumps({"and": [{"event": {"eq": "Lead"}}]}),
        })
        print(f"  conversión OK: {conv.get('id')}")
    except Exception as e:
        print(f"  !! conversión: {str(e)[:200]} (se puede crear en Events Manager)")

    # ── 6) Activar ──
    if args.activate:
        print("== 6) Activando campaña, ad sets y anuncios ==")
        try:
            cc.api("POST", args.campaign_id, args.token, {"status": "ACTIVE"})
            print("  campaña ACTIVE")
        except Exception as e:
            print(f"  !! campaña: {str(e)[:150]}")
        for emb in EMBUDOS:
            try:
                cc.api("POST", emb["adset_id"], args.token, {"status": "ACTIVE"})
                print(f"  adset ACTIVE: {emb['adset_id']}")
            except Exception as e:
                print(f"  !! adset {emb['adset_id']}: {str(e)[:150]}")
        for c in creados:
            try:
                cc.api("POST", c["ad"], args.token, {"status": "ACTIVE"})
            except Exception as e:
                print(f"  !! ad {c['ad']}: {str(e)[:120]}")
        print(f"  {sum(1 for _ in creados)} anuncios activados")

    print("\n=== RESUMEN ===")
    print(json.dumps({"audiencias": audiencias, "anuncios": creados, "activado": args.activate}, indent=1))


if __name__ == "__main__":
    main()
