# Modelo de datos

Fuente: `prisma/schema.prisma`. Migraciones: `prisma/migrations/` (la segunda añade restricciones y disparadores de inmutabilidad).

## Diagrama (simplificado)

```
User 1─1 CreatorProfile         User 1─* Session / PasswordResetToken
User 1─* Work *─1 WorkCategory
Work 1─* WorkComponent, WorkContributor (→ User opcional)
Work 1─* Declaration 1─* DeclarationVersion ─(supersedes 1─1)─ DeclarationVersion
DeclarationVersion *─1 QuestionnaireVersion *─1 TransparencyScheme
DeclarationVersion 1─* AITool, AIUse, Evidence, TransparencyProfile, DeclarationPublication
DeclarationVersion 1─1 DeclarationAcceptance (→ LegalText), VerificationRecord
AIUse *─1 WorkComponent?, AITool?, AIUse? (subusos)
AIUse 1─* AIUsagePhase, CreativeContribution (pareja B–C), Evidence?, TransparencyProfile
TransparencyScheme 1─* TransparencyDimension 1─* TransparencyValue; 1─* TransparencyRule
LegalReference · Incident · AuditLog · RateLimit
```

## Entidades

| Entidad | Descripción |
|---|---|
| `User` | Cuenta (correo, hash Argon2id, rol `AUTHOR`/`ADMIN`, desactivación). |
| `Session` | Sesión; `id` = SHA-256 del token. |
| `PasswordResetToken` | Token de un solo uso (hash). |
| `CreatorProfile` | Identidad profesional (nombre, nombre artístico, organización; `orcid` reservado). |
| `WorkCategory` | Modalidades de obra gestionables, con ejemplos de fases (`phaseHints`). |
| `Work` | Obra (título, modalidad, estado, fechas, idioma, versión, editorial, ISBN/DOI, URL, colectiva). |
| `WorkComponent` | Partes de una obra híbrida (texto, imagen, audio, vídeo, código, interactivo…). |
| `WorkContributor` | Otros participantes (nombre privado, rol, componentes; `userId` para futuras declaraciones conjuntas). |
| `Declaration` | Contenedor lógico de las versiones de una declaración sobre una obra. |
| `DeclarationVersion` | Respuestas del cuestionario (borrador) y, al emitir, `publicId`, `issuedAt`, `snapshot`, `contentHash`, PDF y sus hashes. Estado documental (`DRAFT/ISSUED/SUPERSEDED/REVOKED`) y visibilidad (`PRIVATE/PUBLIC/WITHDRAWN`) independientes. |
| `AITool` | Herramienta (nombre, proveedor, modelo, tipo, finalidad, periodo; `identified=false` para desconocidas). |
| `AIUse` | Uso individual: componente, herramienta, subuso de, funciones A, función principal, objetivo, actividad, resultado, incorporación. |
| `AIUsagePhase` | Fases creativas del uso. |
| `CreativeContribution` | **Pareja B–C**: tipo de aportación, respuesta original de intervención, aclaración C3/C4, C derivado, descripción. |
| `Evidence` | Evidencia (tipo, descripción, metadatos y SHA-256 del fichero, clave de almacenamiento privada). |
| `TransparencyScheme/Dimension/Value/Rule` | Copia de gobernanza de cada versión de la taxonomía (`spec` congelada). |
| `TransparencyProfile` | Resultado de clasificación por uso, componente y obra: código, notación, funciones, parejas, D, reglas aplicadas, **entradas usadas**, versión, fecha. |
| `QuestionnaireVersion` | Definición versionada del cuestionario. |
| `DeclarationAcceptance` | Aceptación: usuario, texto legal exacto (FK + hash), fecha, huellas de sesión e IP. |
| `DeclarationPublication` | Historial de publicación (solo inserción): acción y opciones de visibilidad, consentimiento de evidencias. |
| `VerificationRecord` | Algoritmo, canonicalización, formato, `contentHash`, resúmenes de sección y hashes de PDF. |
| `LegalText` | Textos legales versionados (declaración responsable, privacidad, condiciones, aviso legal). |
| `LegalReference` | Referencias normativas con nota de verificación. |
| `Incident` | Contacto, comunicaciones de contenido, incidencias técnicas y solicitudes RGPD. |
| `AuditLog` | Operaciones críticas (solo inserción). |
| `RateLimit` | Contadores de ventana fija. |

## Invariantes

1. **Emitida ⇒ completa**: `CHECK` exige `publicId`, `contentHash`, `snapshot`, `issuedAt`, `pdfFull`, `pdfSummary` si `status ≠ DRAFT`.
2. **Borrador ⇒ privado**: `CHECK (status <> 'DRAFT' OR visibility = 'PRIVATE')`.
3. **Inmutabilidad**: tras emitir solo pueden cambiar `status` (ISSUED→SUPERSEDED|REVOKED, SUPERSEDED→REVOKED), `visibility`, `revokedAt`, `revocationReason`. No se puede borrar. Las tablas hijas no admiten inserciones, cambios ni borrados.
4. **Solo inserción**: `DeclarationAcceptance`, `VerificationRecord`, `DeclarationPublication`, `AuditLog` (salvo seudonimizar `actorId`).
5. **Esquemas publicados** solo pueden pasar a `DEPRECATED`; **textos legales publicados** no cambian de contenido.
6. **Cadena de versiones**: `supersedesId` es único (una versión tiene como mucho una sucesora); `(declarationId, versionNumber)` único.
7. **Parejas B–C**: `(useId, contributionType)` único; las relaciones se reconstruyen siempre desde `CreativeContribution` y desde la instantánea.
8. **Integridad referencial**: componentes usados por usos no pueden borrarse (`RESTRICT`); obras con declaraciones tampoco.

## No se almacena solo la cadena del código

Para cada clasificación se guardan: respuestas originales (`AIUse`, `CreativeContribution`), valores estructurados, relaciones B–C, código y notación calculados, versión del esquema, fecha, reglas aplicadas y entradas (`TransparencyProfile.inputs`), además de la instantánea congelada.
