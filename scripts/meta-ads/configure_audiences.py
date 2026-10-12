#!/usr/bin/env python3
"""
Crea las audiencias de exclusión (clientes y leads, emails SHA-256) y las aplica
como exclusión en los 3 ad sets. Requiere haber aceptado las Condiciones de
Públicos Personalizados en la cuenta publicitaria.

Uso:
  python configure_audiences.py --token T --ad-account act_X --emails-file emails.json
"""

import argparse
import hashlib
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.resolve()))
import create_campaigns as cc  # noqa: E402

ADSETS = ["120247999030690108", "120247999032700108", "120247999034400108"]


def sha256_email(email: str) -> str:
    return hashlib.sha256(email.strip().lower().encode("utf-8")).hexdigest()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--token", required=True)
    parser.add_argument("--ad-account", required=True)
    parser.add_argument("--emails-file", required=True)
    args = parser.parse_args()

    emails = json.loads(Path(args.emails_file).read_text(encoding="utf-8"))
    audiencias = []

    for nombre, lista, desc in [
        ("VS Clientes actuales (excluir)", emails.get("clientes", []), "Suscripción paga o histórica"),
        ("VS Leads web (excluir)", emails.get("leads", []), "Leads capturados en landings"),
    ]:
        try:
            aud = cc.api("POST", f"{args.ad_account}/customaudiences", args.token, {
                "name": nombre,
                "subtype": "CUSTOM",
                "customer_file_source": "USER_PROVIDED_ONLY",
                "description": desc,
            })
            aud_id = aud["id"]
            hashed = [[sha256_email(e)] for e in lista if e]
            if hashed:
                cc.api("POST", f"{aud_id}/users", args.token, {
                    "payload": json.dumps({"schema": ["EMAIL_SHA256"], "data": hashed}),
                })
            audiencias.append(aud_id)
            print(f"  audiencia OK: {nombre} -> {aud_id} ({len(hashed)} emails)")
        except Exception as e:
            print(f"  !! {nombre}: {str(e)[:250]}")

    if not audiencias:
        print("Sin audiencias creadas. No se aplicaron exclusiones.")
        return

    excl = [{"id": a} for a in audiencias]
    for adset_id in ADSETS:
        try:
            data = cc.api("GET", f"{adset_id}?fields=targeting", args.token)
            targeting = data.get("targeting", {})
            targeting["excluded_custom_audiences"] = excl
            cc.api("POST", adset_id, args.token, {"targeting": json.dumps(targeting)})
            print(f"  exclusión aplicada: {adset_id}")
        except Exception as e:
            print(f"  !! exclusión {adset_id}: {str(e)[:200]}")

    print(json.dumps({"audiencias": audiencias}))


if __name__ == "__main__":
    main()
