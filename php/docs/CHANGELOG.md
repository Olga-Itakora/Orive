# Cambios — ORIDE (versión PHP)

## 1.0.0 — 30/10/2026

Primera versión PHP + MySQL de la plataforma, con el nombre **ORIDE (Originality Declaration)**. Es una versión independiente de la de Next.js (ORIVA), que se mantiene sin cambios en la raíz del repositorio.

- Motor ORIDE Code v1.0 (dimensiones A/B/C/D, parejas B–C por uso, D objetiva, perfiles compuestos), equivalente al de la versión Next.js.
- Integridad: JSON canónico, resúmenes por sección con sal, huella raíz SHA-256 y divulgación selectiva, compatibles byte a byte con la versión Next.js. Identificadores `ORD-XXXX-XXXX-XXXX`.
- Área de autor: obras con componentes y participantes, asistente de declaración en 8 pasos con borradores, editor de usos de IA, evidencias privadas, orientación del art. 50, emisión con aceptación de la declaración responsable, PDF completo y resumen con QR, publicación selectiva, distintivo, nuevas versiones, revocación, exportación de datos.
- Parte pública: declaraciones publicadas, verificación (también en el navegador), comprobación de documentos PDF/JSON, explicador de códigos, API JSON de solo lectura.
- Administración: usuarios, taxonomía, textos legales versionados, incidencias y solicitudes RGPD, registro de auditoría.
- Instalador web de un solo uso con comprobación de requisitos. Librerías incluidas (tFPDF 1.34, chillerlan/php-qrcode 5.0.5); no requiere Composer.
- Disparadores opcionales de MySQL para la inmutabilidad.
