/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                // Paleta Primaria (Teal) - Bloqueo de color
                primary: {
                    500: '#025964', // Base
                    400: '#2A737D',
                    300: '#538D97',
                    200: '#7BA8AD',
                    100: '#A4C3C6',
                },
                // Paleta Secundaria (Mint Green) - Acentos y Éxito
                secondary: {
                    500: '#00D47E', // Base
                    400: '#29DA93',
                    300: '#52E2A6',
                    200: '#79E9BC',
                    100: '#A4EFD1',
                },
                // Escala de Grises y Neutros (UI Minimalista)
                neutral: {
                    50: '#F9FAFB', // Fondo general (App Background)
                    100: '#F3F4F6', // Bordes suaves / Inputs
                    400: '#9CA3AF', // Etiquetas / Iconos desactivados
                    600: '#4B5563', // Subtítulos
                    900: '#111827', // Títulos principales
                }
            },
            borderRadius: {
                '3xl': '1.5rem',
                '4xl': '2rem', // Para el Hero Card y Sidebar
            },
            boxShadow: {
                'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)', // La sombra sutil de la imagen
            }
        },
    },
    plugins: [],
}
