# Seguridad e integridad (versión PHP)

## Integridad de las declaraciones

- Al emitir, se congela una **instantánea** (`oride-declaration/1`) con 10 secciones: `meta`, `work`, `declarant`, `scope`, `classification`, `summary`, `process`, `article50`, `statement`, `evidence`.
- Cada sección se resume con SHA-256 sobre su JSON canónico (subconjunto de RFC 8785, solo enteros) junto con una **sal aleatoria**. La huella de la declaración es el SHA-256 del conjunto de resúmenes (`includes/integrity.php`).
- La sal permite la **divulgación selectiva**: la vista pública incluye solo las secciones publicadas y los resúmenes de las demás, y aun así la huella se puede recalcular. La sal impide adivinar por fuerza bruta el contenido de una sección no publicada.
- Los PDF (completo y resumen) se generan una sola vez en la emisión y se guardan con su SHA-256. Cualquiera puede comprobar un PDF o JSON recibido en **Verificar → Comprobar un documento** (el fichero no se almacena).
- `assets/js/verificar.js` reimplementa el mismo algoritmo con WebCrypto para que la comprobación se haga también en el navegador del visitante.
- El algoritmo produce huellas idénticas a las de la versión Next.js (comprobado con pruebas cruzadas).

## Inmutabilidad

- La aplicación nunca modifica una versión emitida: los cambios se hacen con una **nueva versión** (la anterior queda «sustituida» y enlazada) o con la **revocación** (motivo y fecha visibles). Solo cambian los campos de estado y visibilidad.
- `sql/triggers.sql` añade disparadores de MySQL que impiden alterar o borrar el contenido emitido, las herramientas, usos, aportaciones y evidencias de versiones emitidas, las aceptaciones, las publicaciones y el registro de auditoría. En alojamiento compartido pueden no estar permitidos (error 1419 con registro binario activo y usuario sin privilegio SUPER); el instalador lo avisa. Con acceso de administrador a MySQL se pueden aplicar después: `mysql -u root oride < sql/triggers.sql`.

## Cuentas y sesiones

- Contraseñas con `password_hash()` (bcrypt), mínimo 10 caracteres. Comparación en tiempo constante también para correos inexistentes.
- Sesiones PHP nativas con cookie `ORIDESESS`: `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, identificador regenerado al iniciar sesión.
- `session_version`: cambiar la contraseña, el rol o desactivar una cuenta invalida las demás sesiones abiertas.
- Restablecimiento de contraseña con token aleatorio de un solo uso, guardado como hash y con caducidad.
- Límites de frecuencia (tabla `rate_limits`) en inicio de sesión, registro, recuperación, contacto, comprobación de documentos, API, evidencias y emisión.

## Peticiones

- **CSRF**: todas las peticiones POST llevan un token de sesión comprobado con `hash_equals`.
- **CSP** estricta: `default-src 'self'`, `script-src 'self'`, `style-src 'self'`, `frame-ancestors 'none'`. No hay estilos ni scripts en línea.
- Cabeceras: `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Frame-Options`.
- Todas las consultas usan sentencias preparadas (PDO). Cada consulta del área de autor filtra por propietario (sin acceso a objetos ajenos por identificador).
- Toda salida HTML se escapa con `h()`. El distintivo SVG escapa los valores para XML y tiene su propia CSP.
- Las respuestas de la API y de las páginas públicas devuelven el mismo 404 si una declaración no existe, es privada o se retiró su publicación.

## Ficheros

- Evidencias: lista blanca por **firma de contenido** (PDF, PNG, JPEG, WebP, texto), máximo 10 MB, almacenadas por su SHA-256 en `storage/evidencias/` (inaccesible por web) y servidas solo a su propietario.
- `includes/`, `lib/`, `sql/`, `storage/`, `tests/`, `config.php` y los `.md` están bloqueados por `.htaccess`.

## Privacidad en registros

- Las IP se guardan solo como huella HMAC con el secreto de la aplicación (`fingerprint()`), no reversible.
- Los errores se registran en `storage/logs/` con una referencia que se muestra al usuario, sin detalles técnicos en pantalla.

## Lo que ORIDE no acredita

La huella acredita que el contenido no ha cambiado desde su emisión y la fecha de registro. **No** acredita la identidad del declarante, la veracidad de lo declarado, la originalidad de la obra, la titularidad de derechos ni el cumplimiento legal. No es una firma electrónica cualificada ni un sellado de tiempo cualificado.
