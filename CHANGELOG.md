# Changelog

## [0.2.0] — 2026-10-30 · Auditoría de UX

### Corregido
- Los formularios perdían lo escrito cuando el servidor devolvía errores de validación (reinicio automático de React 19).

### Cambiado
- Validación al salir del campo; el error desaparece al corregir; el envío nunca se bloquea con un botón deshabilitado.
- Borradores locales con restauración y aviso al abandonar la página con cambios sin guardar.
- Confirmación con nombre de la entidad en todas las acciones destructivas (Cancelar con foco, Escape, foco devuelto).
- Botones de icono de 44×44 con tooltip en hover y foco; objetivos táctiles de 44 px.
- Listados como tablas con búsqueda visible («/»), paginación siempre presente y estados vacíos con acción.
- Fichas de declaración (autor y pública) con pestañas; autenticación sin navegación del sitio.
- Tipografía con 3 pesos (400/500/600), mínimo 12 px, interlineados 1.5 / 1.1–1.25; un único acento interactivo (petróleo).
- Sin desbordamiento horizontal a 375 y 1280 px; sin desenfoque ni sombras duras; transiciones solo de opacidad y transformación.
- 2 pruebas E2E nuevas (comportamiento de formularios y confirmaciones destructivas).

## [0.1.0] — 2026-10-30 · MVP

### Añadido
- ORIVA Code v1.0: taxonomía (A, B, C, D), motor determinista por uso, componente y obra, perfiles compuestos, gestión de incertidumbre, validación y explicación de códigos.
- Cuestionario adaptativo de 8 pasos con borradores, usos individuales, subusos, herramientas desconocidas, evidencias privadas y vista previa de la clasificación.
- Orientación sobre el artículo 50 del Reglamento (UE) 2024/1689, separada del código.
- Emisión transaccional de ORIVA Declarations: identificador `ORV-XXXX-XXXX-XXXX`, instantánea inmutable, huella SHA-256 con divulgación selectiva, PDF completo y resumen (etiquetados, con QR), exportación JSON.
- Publicación selectiva, retirada de publicación, revocación, nuevas versiones con preservación de las anteriores.
- Verificación pública: página, recálculo de la huella en el navegador, comprobación de PDF/JSON, API v1, distintivo SVG, script CLI.
- Autenticación con sesiones en BD y Argon2id; recuperación de acceso; perfil; exportación de datos personales; solicitud de supresión.
- Administración: estadísticas agregadas, usuarios y roles, taxonomía, cuestionarios, modalidades, textos legales versionados, referencias normativas, incidencias (incl. RGPD), auditoría.
- Seguridad: CSP con nonce, cabeceras, límites de frecuencia, disparadores de inmutabilidad, protección IDOR.
- Pruebas: 61 unitarias, 9 de integración (PostgreSQL), 6 E2E (Playwright).
- Documentación técnica y jurídica preliminar.

### Pendiente
- Envío de correos; verificación de correo; 2FA para administradores.
- Contenidos largos en inglés.
- Automatización de la supresión de datos.
- Funcionalidades futuras del §30 del encargo.
