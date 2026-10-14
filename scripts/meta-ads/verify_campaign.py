#!/usr/bin/env python3
"""
Validador de la campaña Meta E1/E2/E3 (aplicado en vivo vs. intencionado).

Corre DESPUÉS de cualquier cambio (o re-ejecución de scripts) y falla con
exit code 1 si algo no coincide. Nada se considera "aplicado" hasta que
este validador pase.

Uso:
  python verify_campaign.py --token-file /root/.meta-token
"""

import argparse
import json
import sys
import urllib.error
import urllib.parse
import urllib.request

CAMPAIGN_ID = "120247999030430108"
UTM_ESPERADO = "utm_source=meta&utm_medium=paid&utm_campaign={{campaign.name}}&utm_content={{ad.name}}&utm_term={{adset.name}}"
EXCLUIDOS_ESPERADOS = {"120247999226360108", "120247999226840108"}

ADSETS_ESPERADOS = {
    "120247999030690108": {
        "nombre": "META_Traffic_E1_Alertas_2026Q4",
        "landing": "https://licitacionesmype.pe/alertas-licitaciones",
        "intereses": {"construction engineering", "prácticas gubernamentales"},
        "estado": "ACTIVE",
    },
    "120247999032700108": {
        "nombre": "META_Traffic_E2_Software_2026Q4",
        "landing": "https://licitacionesmype.pe/software-licitaciones",
        "intereses": {"software", "prácticas gubernamentales"},
        "estado": "PAUSED",
    },
    "120247999034400108": {
        "nombre": "META_Traffic_E3_Vigentes_2026Q4",
        "landing": "https://licitacionesmype.pe/licitaciones-vigentes",
        "intereses": {"pequeñas y medianas empresas", "prácticas gubernamentales"},
        "estado": "PAUSED",
    },
    "120248057690490108": {
        "nombre": "META_T4_Plantillas_LPV",
        "landing": "https://licitacionesmype.pe/plantillas-tdr",
        "intereses": {"pequeñas y medianas empresas", "prácticas gubernamentales"},
        "estado": "ACTIVE",
    },
}

ADS_ESPERADOS = {
    "120247999155660108": ("120247999030690108", "MA-Alertas - V1 Dato"),
    "120247999156360108": ("120247999030690108", "MA-Alertas - V2 Dolor"),
    "120247999157100108": ("120247999030690108", "MA-Alertas - V3 Alivio"),
    "120247999157490108": ("120247999032700108", "MA-Software - V1 Dato"),
    "120247999158440108": ("120247999032700108", "MA-Software - V2 Dolor"),
    "120247999159430108": ("120247999032700108", "MA-Software - V3 Alivio"),
    "120247999160020108": ("120247999034400108", "MA-Vigentes - V1 Dato"),
    "120247999161460108": ("120247999034400108", "MA-Vigentes - V2 Dolor"),
    "120247999162570108": ("120247999034400108", "MA-Vigentes - V3 Alivio"),
    "120248057693040108": ("120248057690490108", "MA-T4 - V1 Plantillas"),
}


class Validador:
    def __init__(self, token):
        self.token = token
        self.checks = []

    def api(self, path, fields=None):
        params = {"access_token": self.token}
        if fields:
            params["fields"] = fields
        url = f"https://graph.facebook.com/v26.0/{path}?" + urllib.parse.urlencode(params)
        try:
            with urllib.request.urlopen(url, timeout=60) as r:
                return json.loads(r.read().decode())
        except urllib.error.HTTPError as e:
            return {"error": json.loads(e.read().decode()).get("error", {})}

    def check(self, ok, etiqueta, detalle=""):
        self.checks.append((bool(ok), etiqueta, detalle))
        icono = "OK  " if ok else "FALLA"
        print(f"[{icono}] {etiqueta}" + (f" -> {detalle}" if detalle and not ok else ""))

    def run(self):
        self.validar_campania()
        self.validar_adsets()
        self.validar_anuncios()

        fallos = [c for c in self.checks if not c[0]]
        print()
        print(f"RESULTADO: {len(self.checks) - len(fallos)}/{len(self.checks)} checks OK")
        if fallos:
            print("HAY FALLOS:")
            for _, etiqueta, detalle in fallos:
                print(f"  - {etiqueta}: {detalle}")
            return 1
        print("TODO OK")
        return 0

    def validar_campania(self):
        c = self.api(CAMPAIGN_ID, "name,status,objective,effective_status")
        self.check(c.get("status") == "ACTIVE", "Campaña ACTIVE", c.get("status"))
        self.check(c.get("name") == "META_Traffic_Construccion_E1E2E3_2026Q4",
                   "Campaña nombre correcto", c.get("name"))
        self.check(c.get("objective") == "OUTCOME_TRAFFIC", "Campaña objetivo OUTCOME_TRAFFIC", c.get("objective"))
        self.check(not c.get("error"), "Campaña legible", str(c.get("error"))[:80])

    def validar_adsets(self):
        for adset_id, esperado in ADSETS_ESPERADOS.items():
            a = self.api(adset_id, "name,status,daily_budget,optimization_goal,billing_event,targeting,promoted_object")
            t = a.get("targeting", {})
            self.check(a.get("name") == esperado["nombre"], f"{esperado['nombre']} nombre", a.get("name"))
            self.check(a.get("status") == esperado.get("estado", "ACTIVE"),
                       f"{esperado['nombre']} {esperado.get('estado', 'ACTIVE')}", a.get("status"))
            self.check(a.get("daily_budget") == "700", f"{esperado['nombre']} S/7/día",
                       f"{a.get('daily_budget')} cents")
            self.check(a.get("optimization_goal") == "LANDING_PAGE_VIEWS",
                       f"{esperado['nombre']} optimización LANDING_PAGE_VIEWS (nunca LINK_CLICKS)",
                       a.get("optimization_goal"))
            po = a.get("promoted_object") or {}
            self.check(po.get("pixel_id") == "1029237720131593" and po.get("custom_event_type") == "OTHER",
                       f"{esperado['nombre']} promoted_object pixel+CET OTHER",
                       f"{po.get('pixel_id')}/{po.get('custom_event_type')}")
            self.check(t.get("geo_locations", {}).get("countries") == ["PE"],
                       f"{esperado['nombre']} geo PE", str(t.get("geo_locations")))
            self.check(t.get("age_min") == 40 and t.get("age_max") == 60,
                       f"{esperado['nombre']} edad 40-60", f"{t.get('age_min')}-{t.get('age_max')}")
            self.check((t.get("targeting_automation") or {}).get("advantage_audience") == 0,
                       f"{esperado['nombre']} advantage_audience=0",
                       str(t.get("targeting_automation")))
            self.check(t.get("publisher_platforms") == ["facebook", "instagram"],
                       f"{esperado['nombre']} solo FB+IG (sin Audience Network)",
                       str(t.get("publisher_platforms")))

            excluidos = {e["id"] for e in t.get("excluded_custom_audiences", [])}
            self.check(excluidos == EXCLUIDOS_ESPERADOS,
                       f"{esperado['nombre']} exclusiones clientes+leads", str(excluidos))

            nombres = set()
            for spec in t.get("flexible_spec", []):
                for i in spec.get("interests", []):
                    nombres.add((i.get("name") or "").lower())
            ok_intereses = all(any(esp in n for n in nombres) for esp in esperado["intereses"])
            self.check(ok_intereses, f"{esperado['nombre']} intereses correctos", str(nombres))

    def validar_anuncios(self):
        imagenes_por_embudo = {}
        for ad_id, (adset_id, nombre_esperado) in ADS_ESPERADOS.items():
            ad = self.api(ad_id, "name,status,effective_status,adset_id,"
                                 "creative{object_story_spec,url_tags,image_hash}")
            cr = ad.get("creative", {})
            spec = cr.get("object_story_spec", {})
            link = (spec.get("link_data") or {}).get("link", "")

            landing = ADSETS_ESPERADOS[adset_id]["landing"]

            self.check(ad.get("name") == nombre_esperado, f"{nombre_esperado} nombre", ad.get("name"))
            self.check(ad.get("adset_id") == adset_id, f"{nombre_esperado} ad set correcto", ad.get("adset_id"))
            estado_adset = ADSETS_ESPERADOS[adset_id].get("estado", "ACTIVE")
            if estado_adset == "ACTIVE":
                self.check(ad.get("status") == "ACTIVE" and ad.get("effective_status") == "ACTIVE",
                           f"{nombre_esperado} ACTIVE/ACTIVE", ad.get("effective_status"))
            else:
                self.check(ad.get("effective_status") in ("ADSET_PAUSED", "PAUSED"),
                           f"{nombre_esperado} pausado con su adset", ad.get("effective_status"))
            self.check(link == landing, f"{nombre_esperado} landing", link)
            self.check(cr.get("url_tags") == UTM_ESPERADO, f"{nombre_esperado} UTMs", cr.get("url_tags"))

            imagen = (spec.get("link_data") or {}).get("image_hash") or cr.get("image_hash")
            self.check(bool(imagen), f"{nombre_esperado} tiene imagen", str(imagen))
            if imagen:
                imagenes_por_embudo.setdefault(adset_id, set()).add(imagen)

        for adset_id, hashes in imagenes_por_embudo.items():
            nombre = ADSETS_ESPERADOS[adset_id]["nombre"]
            self.check(len(hashes) == 3, f"{nombre} 3 imágenes distintas (V1/V2/V3)", f"{len(hashes)}")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--token", default=None)
    parser.add_argument("--token-file", default=None)
    args = parser.parse_args()

    token = args.token
    if not token and args.token_file:
        from pathlib import Path
        token = Path(args.token_file).read_text(encoding="utf-8").strip()
    if not token:
        print("Falta token (--token o --token-file)")
        sys.exit(1)

    sys.exit(Validador(token).run())


if __name__ == "__main__":
    main()
