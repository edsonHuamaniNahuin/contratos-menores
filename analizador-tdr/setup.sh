#!/bin/bash
# Script de instalación para Linux/Mac
# Ejecutar: chmod +x setup.sh && ./setup.sh

echo "🚀 Instalando Analizador TDR SEACE..."

# Verificar Python
echo ""
echo "1️⃣  Verificando Python..."
python3 --version || { echo "❌ Python no encontrado. Instala Python 3.10+ primero."; exit 1; }

# Crear entorno virtual
echo ""
echo "2️⃣  Creando entorno virtual..."
python3 -m venv venv

# Activar entorno virtual
echo ""
echo "3️⃣  Activando entorno virtual..."
source venv/bin/activate

# Instalar dependencias
echo ""
echo "4️⃣  Instalando dependencias..."
pip install --upgrade pip
pip install -r requirements.txt

# Copiar .env si no existe
if [ ! -f ".env" ]; then
    echo ""
    echo "5️⃣  Creando archivo .env..."
    cp .env.example .env
    echo "⚠️  IMPORTANTE: Edita el archivo .env y configura tu API key"
else
    echo ""
    echo "5️⃣  Archivo .env ya existe"
fi

echo ""
echo "✅ Instalación completada!"
echo ""
echo "📝 Próximos pasos:"
echo "1. Edita el archivo .env con tu API key"
echo "2. Ejecuta: python main.py"
echo "3. Abre: http://localhost:8001/docs"
