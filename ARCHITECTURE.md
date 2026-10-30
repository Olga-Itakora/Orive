# Arquitectura de ORIVA

## Visión general

Aplicación monolítica modular (sin microservicios) sobre **Next.js 15 (App Router) + React 19 + TypeScript**, con **PostgreSQL** mediante **Prisma 6**. Toda la lógica sensible se ejecuta en el servidor.

```
src/
├── app/                      Rutas (App Router)
│   ├── (site)/               Zona pública: inicio, informativas, verificación, autenticación
│   ├── app/                  Zona privada de autores (requiere sesión)
│   ├── admin/                Administración (requiere rol ADMIN)
│   ├── api/v1/               API pública de verificación
│   ├── badge/, verify/[id]/  Distintivo SVG, QR y PDF públicos
│   └── actions/              Server Actions (mutaciones; validación Zod + autorización)
├── lib/
│   ├── oriva-code/           Taxonomía, motor de clasificación y explicación (puro, sin E/S)
│   ├── integrity/            JSON canónico, SHA-256, divulgación selectiva (isomórfico)
│   ├── legal/                Orientación del art. 50 y referencias normativas (puro)
│   ├── declarations/         Servicios de dominio: carga, instantánea, emisión, publicación
│   ├── pdf/                  Generación de PDF (pdfkit, servidor)
│   ├── auth/                 Contraseñas (Argon2id) y sesiones en BD
│   ├── security/             Límites de frecuencia, huellas con clave, comprobaciones de origen
│   ├── storage/              Almacenamiento privado de evidencias
│   └── validation/           Esquemas Zod compartidos
├── components/               UI (componentes al estilo shadcn/ui, ORIVACode, asistente)
├── i18n/                     Diccionarios es/en
└── middleware.ts             CSP con nonce y cabeceras de seguridad
prisma/                       Esquema, migraciones (incluye disparadores de inmutabilidad), seed
tests/                        unit/, integration/ (PostgreSQL real), e2e/ (Playwright)
```

## Capas

1. **Núcleo puro** (`lib/oriva-code`, `lib/integrity`, `lib/legal`): funciones deterministas sin acceso a red ni BD. Se usan tanto en servidor (emisión) como en cliente (vista previa de la clasificación, verificación de huella en el navegador). Son la parte mejor cubierta por pruebas.
2. **Dominio** (`lib/declarations`): servicios que reciben el `userId` autenticado y comprueban la propiedad en cada consulta (`where: { declaration: { ownerId } }`). No dependen de Next.js, lo que permite probarlos con PostgreSQL real.
3. **Entrega** (`app/`): páginas de servidor, Server Actions y route handlers. Validan con Zod, aplican límites de frecuencia y delegan en el dominio.

## Flujo de emisión (transaccional)

`issueVersion()` en una transacción `SERIALIZABLE`:

1. `SELECT … FOR UPDATE` de la versión (evita emisiones concurrentes).
2. Comprueba propiedad, estado `DRAFT` y que el hash del texto aceptado coincide con el texto activo.
3. Clasifica (`generateCompositeProfile`) y valida (`validateTransparencyProfile` + completitud).
4. Genera identificador público, fecha UTC del servidor, instantánea con secciones saladas, huella raíz, PDF completo y resumen, y sus SHA-256.
5. Escribe perfiles calculados, C derivado, versión emitida, aceptación, registro de verificación, sustitución de la versión anterior y auditoría.

Cualquier fallo revierte todo: no existen emisiones parciales. Los disparadores de BD impiden después cualquier modificación del contenido.

## Decisiones de arquitectura (ADR)

### ADR-001 · Next.js monolítico con Server Actions
Un único despliegue simplifica operación y seguridad. Las Server Actions incluyen comprobación de origen (protección CSRF); los route handlers que mutan estado comprueban el tamaño/límites y no dependen de cookies de terceros.

### ADR-002 · Sesiones propias en base de datos en lugar de Auth.js
Auth.js v5 sigue en beta y su proveedor de credenciales impone sesiones JWT sin revocación del lado del servidor y sin recuperación de contraseña. ORIVA necesita revocar sesiones (cambio de contraseña, desactivación por un administrador) y registrar la sesión usada en la aceptación. Se implementó el patrón recomendado por OWASP/Lucia: token aleatorio de 256 bits en cookie `HttpOnly; SameSite=Lax; Secure`, y en BD solo su SHA-256; contraseñas con **Argon2id** (`@node-rs/argon2`, parámetros OWASP). Integrar Auth.js/OIDC (p. ej. ORCID) es una ampliación prevista.

### ADR-003 · Clasificación determinista en código versionado
El motor de cada versión del esquema vive en código y está cubierto por pruebas; la BD guarda una copia de gobernanza (dimensiones, valores, reglas y especificación congelada). Nunca se usa un LLM para clasificar.

### ADR-004 · Instantánea congelada + huella con divulgación selectiva
Al emitir se congela una instantánea JSON con 10 secciones. Cada sección lleva una sal aleatoria de 128 bits; la huella raíz se calcula sobre los resúmenes de sección. Así la página pública puede ocultar secciones (p. ej. el nombre) sin impedir la verificación, y las secciones ocultas no pueden adivinarse por fuerza bruta. Ver API.md.

### ADR-005 · Inmutabilidad en dos niveles
Además de las comprobaciones de aplicación, una migración SQL añade disparadores que impiden modificar o borrar versiones emitidas y sus hijos (herramientas, usos, fases, aportaciones, evidencias, perfiles), y hacen de solo inserción la aceptación, el registro de verificación, la publicación y la auditoría. Los esquemas publicados y los textos legales publicados tampoco pueden cambiar.

### ADR-006 · PDF generados en el momento de emisión y almacenados
Los PDF se generan una sola vez y se guardan (con su SHA-256), de modo que un PDF recibido puede compararse byte a byte con el registro. Fuentes Inter y JetBrains Mono (OFL) incrustadas; PDF etiquetado con estructura, idioma y texto alternativo.

### ADR-007 · Límites de frecuencia en PostgreSQL
Ventana fija con `INSERT … ON CONFLICT`, válida con varias instancias sin servicios adicionales. Puede sustituirse por Redis si el tráfico lo exige.

### ADR-008 · i18n ligero
Diccionarios tipados (`src/i18n`) y cookie de idioma. Se evitó una dependencia adicional mientras solo exista el español completo. Para añadir idiomas: ampliar `LOCALES`, el diccionario y las etiquetas `Localized` de la taxonomía (ya bilingües es/en).

### ADR-009 · Componentes al estilo shadcn/ui escritos localmente
Se siguen los patrones de shadcn/ui (cva + tailwind-merge, componentes copiados en el repositorio) sin la CLI de registro, usando elementos HTML nativos accesibles en lugar de Radix para reducir dependencias.

## Extensibilidad prevista (no implementada)

- **ORCID / OIDC**: campo `CreatorProfile.orcid` y capa de sesión desacoplada.
- **DOI y sistemas editoriales**: `Work.identifierType/identifier`; API institucional sobre los servicios de dominio.
- **Firmas electrónicas avanzadas y sellado de tiempo cualificado**: la huella raíz (`contentHash`) es el valor a firmar/sellar; añadir una tabla de sellos vinculada a `VerificationRecord`.
- **Declaraciones colectivas con aprobación conjunta**: `WorkContributor.userId` ya enlaza participantes con cuentas; faltan el flujo de aprobación y un tipo de declaración institucional.
- **C2PA / metadatos de procedencia**: exportación derivada de la instantánea.
- **Nuevas versiones de ORIVA Code**: ver ORIVA_CODE_SPEC.md § Versionado.
- **Índice público desnormalizado**: `listPublicDeclarations` filtra en memoria hasta 200 declaraciones; para escala, crear una tabla de índice público actualizada al publicar/retirar.
