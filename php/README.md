# ORIDE — Originality Declaration (versión PHP + MySQL)

*Toda creación tiene un proceso. Hazlo visible.*

ORIDE permite a autoras y autores documentar cómo han utilizado la inteligencia artificial en una obra, obtener un **código ORIDE** (dimensiones A/B/C/D), emitir una **declaración responsable** inmutable con PDF y huella SHA-256, y publicarla para que cualquiera pueda verificarla.

Esta carpeta es una versión **independiente y sencilla** de la plataforma:

- PHP 8.2+ y MySQL 5.7+/8.0 (o MariaDB 10.4+). Sin frameworks.
- Páginas en HTML, CSS y JavaScript simples (`assets/`).
- **No hay que instalar nada**: las librerías (PDF y códigos QR) van incluidas en `lib/`. No se usa Composer ni Node.
- Pensada para alojamiento compartido en **IONOS** (ver [docs/INSTALACION_IONOS.md](docs/INSTALACION_IONOS.md)).

## Instalación rápida

1. Sube el contenido de esta carpeta al servidor (por SFTP).
2. Crea una base de datos MySQL vacía.
3. Abre `https://tu-dominio/instalar.php` y rellena el formulario (base de datos, URL del sitio y cuenta de administración).
4. Elimina `instalar.php` del servidor.

El instalador crea `config.php` (con un secreto aleatorio), las tablas, los textos legales iniciales y la cuenta de administración. Después queda bloqueado.

## Qué incluye

| Área | Páginas |
|---|---|
| Pública | Inicio, Cómo funciona, ORIDE Code (explicador de códigos), Preguntas, Marco normativo, Declaraciones públicas, Verificar, Contacto, textos legales |
| Autor/a | Panel, Mis obras (con componentes y participantes), asistente de declaración en 8 pasos, editor de usos de IA, ficha de la declaración (publicación, distintivo, versiones, revocación), descargas PDF/JSON, perfil y exportación de datos |
| Administración | Resumen, usuarios, taxonomía (solo lectura), textos legales versionados, incidencias (incluidas solicitudes RGPD), registro de auditoría |
| API pública | `api/declaracion.php?id=ORD-…` (JSON publicado), `api/codigo.php?c=A4B2C1D2` (explicación de un código) |
| Recursos | `qr.php?id=…` (QR SVG), `distintivo.php?id=…` (distintivo SVG para webs), `documento.php?id=…&tipo=resumen|pdf` |

## Estructura

```
index.php, verificar.php, …   páginas públicas
autor/                        área de autor (requiere sesión)
admin/                        administración (requiere rol ADMIN)
api/                          API pública de solo lectura
includes/                     lógica (motor ORIDE, integridad, PDF, vistas…) — no accesible por web
lib/                          librerías incluidas (tFPDF, php-qrcode) — no accesible por web
sql/                          esquema y disparadores — no accesible por web
storage/                      evidencias y registros — no accesible por web
assets/                       CSS, JavaScript e imágenes
docs/                         documentación
tests/                        pruebas — no accesible por web
```

## Pruebas

```bash
php tests/run.php
```

Ejecuta las pruebas del motor ORIDE, de la integridad (JSON canónico y huellas), de los identificadores y de la orientación del art. 50. La huella de integridad es idéntica byte a byte a la de la versión Next.js.

Para desarrollo local:

```bash
php -S 127.0.0.1:8090 tests/router.php
```

`tests/e2e.js` es una prueba de extremo a extremo opcional (requiere Node y Playwright; solo para desarrollo).

## Documentación

- [docs/INSTALACION_IONOS.md](docs/INSTALACION_IONOS.md) — puesta en marcha paso a paso en IONOS.
- [docs/ORIDE_CODE_SPEC.md](docs/ORIDE_CODE_SPEC.md) — especificación del código ORIDE v1.0.
- [docs/SEGURIDAD.md](docs/SEGURIDAD.md) — medidas de seguridad e integridad.
- [docs/PRIVACIDAD.md](docs/PRIVACIDAD.md) — tratamiento de datos y derechos.
- [docs/MARCO_LEGAL.md](docs/MARCO_LEGAL.md) — relación del cuestionario con el Reglamento (UE) 2024/1689.
- [docs/CHANGELOG.md](docs/CHANGELOG.md)

## Limitaciones conocidas

- **Revisión jurídica pendiente**: los textos legales y la orientación del art. 50 son borradores para revisión profesional. ORIDE no certifica el cumplimiento legal.
- **Disparadores de MySQL**: en alojamientos compartidos (como IONOS) es habitual que no se permitan. Sin ellos, la inmutabilidad la garantiza la aplicación y la huella SHA-256 permite detectar cualquier alteración, pero la base de datos no la impide por sí misma.
- **Accesibilidad de los PDF**: los PDF no son documentos etiquetados (PDF/UA). La misma información está disponible en HTML accesible.
- **Correo**: la recuperación de contraseña usa la función `mail()` de PHP; en IONOS conviene que la dirección remitente pertenezca al dominio.
- **Dominio**: `oridecode.com` es un valor de ejemplo; su disponibilidad no se ha comprobado.
