# ORIVA — Creative AI Transparency

> **Every creation has a process. Make it visible.**
> *Toda creación tiene un proceso. Hazlo visible.*

ORIVA es una plataforma web de transparencia creativa. Permite a autoras y autores de obras literarias, artísticas, académicas, audiovisuales, musicales y de otros ámbitos:

1. describir de forma estructurada **cada uso concreto** de inteligencia artificial en su proceso creativo;
2. obtener una clasificación descriptiva y determinista, el **ORIVA Code** (por ejemplo `A5B3C4D2`, o un perfil compuesto `A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2`);
3. emitir una **ORIVA Declaration**: declaración responsable inmutable, con identificador único (`ORV-XXXX-XXXX-XXXX`), huella SHA-256, PDF completo, resumen de una página, exportación JSON y código QR;
4. decidir qué publicar, y permitir que cualquiera **verifique su integridad**.

ORIVA **no** es una certificación de autoría, originalidad, titularidad de derechos ni cumplimiento legal, y **no** es un servicio de la Unión Europea. El ORIVA Code **no** es una puntuación ni un porcentaje de autoría.

---

## Estado del proyecto (MVP)

| Área | Estado |
|---|---|
| Zona pública (inicio, cómo funciona, ORIVA Code, declaraciones, verificar, marco normativo, FAQ, legales, contacto) | Implementado |
| Registro, inicio de sesión, recuperación de acceso, perfil, exportación de datos | Implementado (el envío de correo **no**: los enlaces de recuperación se escriben en el log del servidor) |
| Obras, componentes y participantes | Implementado |
| Cuestionario adaptativo (8 pasos, borradores, usos individuales, subusos, evidencias, art. 50) | Implementado |
| Motor ORIVA Code v1.0 (por uso, por componente, perfil compuesto, incertidumbre) | Implementado y probado |
| Emisión transaccional, inmutabilidad en BD, PDF, JSON, SHA-256, QR | Implementado y probado |
| Publicación selectiva con verificación de huella sobre secciones ocultas | Implementado y probado |
| Versiones, sustitución, revocación, retirada de publicación | Implementado y probado |
| Administración (usuarios, taxonomía, cuestionarios, modalidades, textos legales, referencias, incidencias, auditoría, estadísticas) | Implementado (alcance MVP) |
| API pública de verificación v1 | Implementado |
| Interfaz en inglés | **Parcial**: arquitectura i18n y diccionario de la interfaz común; los contenidos largos solo están en español |
| Funcionalidades futuras (§30 del encargo: ORCID, DOI, firmas avanzadas, sellado de tiempo cualificado, C2PA, declaraciones colectivas con aprobación conjunta…) | **No implementadas** (arquitectura preparada; ver ARCHITECTURE.md) |

## Requisitos

- Node.js ≥ 20.18 (probado con 22.22)
- PostgreSQL ≥ 14 (probado con 16)
- Chromium para las pruebas E2E (Playwright 1.63)

## Instalación y ejecución local

```bash
npm ci
```

```bash
cp .env.example .env   # edita DATABASE_URL, APP_SECRET y la contraseña del administrador
```

```bash
npx prisma migrate deploy
```

```bash
npm run db:seed
```

```bash
npm run dev
```

Abre http://localhost:3000. El seed crea la taxonomía ORIVA Code v1.0, el cuestionario v1.0, 22 modalidades de obra, los textos legales v1.0 (marcados como pendientes de revisión jurídica), las referencias normativas y el administrador definido en `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD`.

### Datos de prueba

Las pruebas de integración y E2E crean sus propios usuarios, obras y declaraciones. Para explorar la aplicación a mano, regístrate y sigue el asistente; en `tests/e2e/mvp-journey.spec.ts` tienes un ejemplo completo de obra híbrida con dos usos.

## Pruebas

```bash
npm run test:unit          # motor ORIVA Code, integridad, art. 50, identificadores (61 pruebas)
```

```bash
npm run test:integration   # PostgreSQL real: emisión, inmutabilidad, IDOR, versiones, verificación (9 pruebas)
```

```bash
npm run test:e2e           # Playwright: recorrido completo del MVP, seguridad y UX (8 pruebas)
```

Las pruebas de integración usan la base de datos `oriva_test` (variable `TEST_DATABASE_URL`) y aplican migraciones con `prisma migrate deploy`. Las E2E arrancan `next dev` en el puerto 3100 (o reutilizan uno existente); para probar contra el build de producción: `E2E_COMMAND="npx next start -p 3100" npm run test:e2e` tras `npm run build`.

## Verificación independiente de una declaración

```bash
npx tsx scripts/verify-declaration.ts declaracion.json [huella-esperada]
```

Funciona con la exportación JSON completa del autor o con la respuesta de `GET /api/v1/declarations/{id}`. Ver API.md.

## Documentación

- [ARCHITECTURE.md](ARCHITECTURE.md) — arquitectura y decisiones (ADR)
- [DATA_MODEL.md](DATA_MODEL.md) — modelo de datos e invariantes
- [ORIVA_CODE_SPEC.md](ORIVA_CODE_SPEC.md) — especificación de ORIVA Code v1.0
- [SECURITY.md](SECURITY.md) — seguridad
- [PRIVACY.md](PRIVACY.md) — privacidad y RGPD
- [LEGAL_MAPPING.md](LEGAL_MAPPING.md) — relación de preguntas con el marco normativo
- [API.md](API.md) — API pública y procedimiento de verificación
- [DEPLOYMENT.md](DEPLOYMENT.md) — despliegue
- [CHANGELOG.md](CHANGELOG.md)

## Avisos

- Los textos legales, la orientación del artículo 50 y LEGAL_MAPPING.md **requieren revisión jurídica profesional** antes de su uso en producción.
- No se ha comprobado la disponibilidad del dominio `orivacode.com` ni el registro de la marca ORIVA.
- Las fuentes oficiales de la UE (EUR-Lex, digital-strategy.ec.europa.eu) no eran accesibles desde el entorno de desarrollo; las referencias al Reglamento (UE) 2026/1744 proceden de fuentes secundarias y están marcadas para verificación.
