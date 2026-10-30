# Seguridad

## Medidas implementadas

| Riesgo | Medida | Ubicación |
|---|---|---|
| Robo o fijación de sesión | Token aleatorio de 256 bits; en BD solo su SHA-256; cookie `HttpOnly`, `SameSite=Lax`, `Secure` en producción; caducidad deslizante de 30 días; revocación al cambiar/restablecer contraseña y al desactivar la cuenta | `src/lib/auth/session.ts` |
| Contraseñas | Argon2id (m=19 MiB, t=2, p=1); mínimo 10 caracteres; tiempo constante ante usuarios inexistentes; mensajes genéricos | `src/lib/auth/password.ts`, `src/app/actions/auth.ts` |
| Recuperación de acceso | Token de un solo uso (hash en BD), 1 hora, invalida sesiones; respuesta que no revela si el correo existe | `forgotPasswordAction`, `resetPasswordAction` |
| IDOR | Todas las consultas de recursos privados filtran por propietario en el servidor; 404 indistinguible | `src/lib/declarations/service.ts`, route handlers |
| Autorización administrativa | `requireAdmin()` en el layout **y** en cada Server Action | `src/app/admin`, `src/app/actions/admin.ts` |
| CSRF | Server Actions de Next.js (comparación Origin/Host); cookies `SameSite=Lax`; los route handlers mutables no usan la sesión | — |
| XSS | React escapa por defecto; sin `dangerouslySetInnerHTML`; CSP con nonce y `strict-dynamic`; SVG del distintivo con escape y CSP propia | `src/middleware.ts`, `src/app/badge` |
| Clickjacking | `frame-ancestors 'none'`, `X-Frame-Options: DENY` | middleware |
| Cabeceras | HSTS (producción), `nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP; `Cache-Control: no-store` en zonas privadas | middleware |
| Validación | Zod en servidor para toda entrada; límites de longitud; la UI valida también pero no es la barrera | `src/lib/validation` |
| Abuso | Límites de frecuencia persistidos en PostgreSQL (registro, acceso, recuperación, contacto, emisión, evidencias, API) | `src/lib/security/rate-limit.ts` |
| Ficheros | Lista blanca por firma de contenido (PDF, PNG, JPEG, WebP, texto UTF-8); sin SVG/HTML; 10 MB; fuera del directorio público; direccionado por SHA-256; descarga solo del propietario con `attachment`, `nosniff` y CSP `sandbox` | `src/lib/storage/evidence.ts` |
| Integridad de declaraciones | Disparadores de BD que impiden modificar/borrar versiones emitidas, sus hijos, aceptaciones, registros de verificación, publicaciones y auditoría; emisión en transacción `SERIALIZABLE` con bloqueo de fila | migración `*_immutability` |
| Fugas indirectas | Identificadores públicos aleatorios (60 bits, no secuenciales); identificadores internos no expuestos en instantáneas; secciones ocultas con sal; mismas respuestas para «privada» y «no existe» | `src/lib/ids.ts`, `src/lib/integrity` |
| Datos de red | IP nunca en claro: huella HMAC con `APP_SECRET` | `src/lib/security/crypto.ts` |
| Errores | Página de error genérica con referencia (`digest`); sin trazas al cliente | `src/app/error.tsx` |
| Secretos | Variables de entorno validadas al arrancar (`APP_SECRET` ≥ 32 caracteres); `.env` excluido del control de versiones | `src/lib/env.ts` |

## Verificado con pruebas

- IDOR sobre obras, borradores, PDF y JSON (E2E y de integración).
- Acceso a `/app` sin sesión y a `/admin` sin rol.
- Cabeceras de seguridad y CSP.
- Disparadores de inmutabilidad y transiciones de estado no permitidas.
- No inclusión del correo electrónico ni de nombres de terceros en las instantáneas.

## Pendiente / recomendaciones para producción

- Envío real de correos (recuperación y verificación de correo) con un proveedor europeo; hoy el enlace se escribe en el log (desactivado en producción salvo `ORIVA_LOG_RESET_LINKS=true`).
- Verificación de la dirección de correo y, opcionalmente, segundo factor (TOTP/WebAuthn) para administradores.
- Antivirus para evidencias (p. ej. ClamAV) si se amplían los tipos admitidos.
- Proxy de confianza que fije `X-Forwarded-For` (los límites de frecuencia lo usan).
- Revisión de seguridad externa / pentest antes del lanzamiento.
- Escaneo de dependencias en CI (`npm audit` muestra avisos de dependencias de desarrollo que conviene revisar).

## Copias de seguridad y recuperación

- PostgreSQL: copias diarias con `pg_dump -Fc` y WAL archiving (PITR) en el proveedor; retención de 30 días; prueba de restauración trimestral.
- Evidencias: copia del volumen `EVIDENCE_STORAGE_DIR` (ficheros inmutables direccionados por SHA-256, aptos para copias incrementales).
- Tras restaurar: `npx prisma migrate deploy` (idempotente) y comprobación de una muestra de huellas con `scripts/verify-declaration.ts`.

## Comunicar vulnerabilidades

Escribe a la dirección de seguridad que se publique en `/.well-known/security.txt` (pendiente de definir el contacto).
