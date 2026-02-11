"""
Script de prueba del microservicio.
Usa este script para probar el análisis sin necesidad de usar curl.
"""
import asyncio
import sys
from pathlib import Path

# Agregar el directorio padre al path
sys.path.insert(0, str(Path(__file__).parent))

from app.services.analyzer_service import TDRAnalyzerService


async def test_analysis():
    """
    Prueba el análisis con un PDF de ejemplo.

    Uso:
        python test_local.py ruta/al/archivo.pdf
    """
    if len(sys.argv) < 2:
        print("❌ Error: Debes proporcionar la ruta del archivo PDF")
        print("\nUso:")
        print("  python test_local.py ruta/al/archivo.pdf")
        print("\nEjemplo:")
        print("  python test_local.py temp/tdr_ejemplo.pdf")
        return

    pdf_path = sys.argv[1]

    if not Path(pdf_path).exists():
        print(f"❌ Error: El archivo '{pdf_path}' no existe")
        return

    print("🔍 Iniciando análisis de TDR...")
    print(f"📄 Archivo: {pdf_path}\n")

    try:
        # Leer el PDF
        with open(pdf_path, 'rb') as f:
            pdf_bytes = f.read()

        # Crear servicio y analizar
        analyzer = TDRAnalyzerService()
        result = await analyzer.analyze_tdr_document(
            pdf_bytes=pdf_bytes,
            llm_provider=None  # Usa el configurado por defecto
        )

        # Mostrar resultados
        print("=" * 80)
        print("✅ ANÁLISIS COMPLETADO")
        print("=" * 80)

        print(f"\n📝 Resumen Ejecutivo:")
        print(f"{result.resumen_ejecutivo}")

        print(f"\n🔧 Requisitos Técnicos ({len(result.requisitos_tecnicos)}):")
        for i, req in enumerate(result.requisitos_tecnicos, 1):
            print(f"  {i}. {req}")

        print(f"\n📋 Reglas de Negocio ({len(result.reglas_de_negocio)}):")
        for i, regla in enumerate(result.reglas_de_negocio, 1):
            print(f"  {i}. {regla}")

        if result.politicas_y_penalidades:
            print(f"\n⚠️  Políticas y Penalidades ({len(result.politicas_y_penalidades)}):")
            for i, pol in enumerate(result.politicas_y_penalidades, 1):
                print(f"  {i}. {pol}")

        if result.presupuesto_referencial:
            print(f"\n💰 Presupuesto Referencial: {result.presupuesto_referencial}")

        print("\n" + "=" * 80)

        # Guardar JSON
        import json
        output_file = Path(pdf_path).stem + "_analisis.json"
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(result.model_dump(), f, ensure_ascii=False, indent=2)

        print(f"\n💾 Resultado guardado en: {output_file}")

    except Exception as e:
        print(f"\n❌ Error durante el análisis: {str(e)}")
        import traceback
        traceback.print_exc()


if __name__ == "__main__":
    asyncio.run(test_analysis())
