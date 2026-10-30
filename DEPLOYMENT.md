# Despliegue

## Variables de entorno

Ver `.env.example`. Obligatorias en producción:

| Variable | Descripción |
|---|---|
| `DATABASE_URL` | PostgreSQL (≥14) |
| `APP_URL` | URL pública, p. ej. `https://orivacode.com` (se imprime en PDF y QR: **definirla antes de emitir declaraciones reales**) |
| `APP_SECRET` | ≥32 caracteres aleatorios (`openssl rand -base64 48`). Cambiarlo invalida las huellas de IP y de límites de frecuencia, no las declaraciones |
| `COOKIE_SECURE` | `true` detrás de HTTPS |
| `EVIDENCE_STORAGE_DIR` | Volumen persistente y respaldado |
| `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` | Solo para el primer seed; cambia la contraseña después |

## Opción A · Docker Compose

```bash
cp .env.example .env
```

```bash
docker compose up --build -d
```

El servicio `migrate` aplica migraciones y seed antes de arrancar `app`. **El Dockerfile no se ha podido construir en el entorno de desarrollo** (sin demonio Docker): valídalo en CI.

## Opción B · Node.js directo

```bash
npm ci && npx prisma migrate deploy && npm run db:seed && npm run build
```

```bash
NODE_ENV=production node .next/standalone/server.js
```

Copia `.next/static` a `.next/standalone/.next/static` y `assets/` a `.next/standalone/assets` si ejecutas el modo standalone fuera de Docker (o usa `npm start`).

## Proveedores europeos

La aplicación es un contenedor Node estándar + PostgreSQL gestionado; es compatible con proveedores con centros de datos en el EEE (p. ej. Scaleway, OVHcloud, Hetzner, Clever Cloud, IONOS). Recomendado:

- PostgreSQL gestionado con copias diarias y PITR en la misma región.
- Volumen persistente cifrado para evidencias (o adaptar `src/lib/storage/evidence.ts` a un almacenamiento de objetos S3 compatible).
- Proxy inverso con TLS (HSTS ya se envía en producción) que fije `X-Forwarded-For`.

## Lista de comprobación previa al lanzamiento

- [ ] Revisión jurídica de textos legales, declaración responsable, orientación del art. 50 y LEGAL_MAPPING.md; crear nuevas versiones desde Administración y desmarcar «pendiente de revisión».
- [ ] Completar identidad del responsable del tratamiento y aviso legal.
- [ ] Configurar envío de correo (recuperación de contraseña) — **no implementado**.
- [ ] Comprobar disponibilidad y registrar el dominio `orivacode.com`; evaluar el registro de la marca ORIVA (no comprobados).
- [ ] Definir el contacto de seguridad (`/.well-known/security.txt`).
- [ ] Copias de seguridad y prueba de restauración.
- [ ] Ejecutar `npm run test:unit && npm run test:integration && npm run test:e2e` en CI.

## Migraciones

`npx prisma migrate deploy` es idempotente. No uses `migrate reset` en producción: destruye datos, incluidas declaraciones emitidas inmutables.
