# Privacidad y protección de datos (diseño)

> Documento técnico. La política de privacidad pública (`/privacy`) es un borrador pendiente de revisión jurídica.

## Principios aplicados

- **Privado por defecto.** Toda declaración se emite como privada. Publicar es una acción expresa e independiente de la emisión.
- **Minimización.** No se piden prompts ni conversaciones completas; las evidencias son opcionales; los nombres de otros participantes nunca entran en la instantánea (solo sus roles).
- **Control granular.** Visibilidad independiente de: nombre profesional, obra, código y alcance, resumen, declaración completa y metadatos de evidencias (con consentimiento específico registrado).
- **Nunca se publica:** correo electrónico, datos de autenticación, prompts o conversaciones privadas, ficheros de evidencia, datos de terceros, identificadores internos, IP.

## Inventario de datos

| Dato | Finalidad | Base jurídica (propuesta) | Visibilidad |
|---|---|---|---|
| Correo, hash de contraseña | Cuenta | Contrato (6.1.b) | Privado |
| Perfil profesional | Rellenar declaraciones | Contrato | Privado salvo publicación |
| Obras, componentes | Servicio | Contrato | Según publicación |
| Nombres de colaboradores | Gestión de la obra | Interés legítimo (6.1.f) | Privado, fuera de la instantánea |
| Declaraciones emitidas | Integridad y verificabilidad | Contrato + interés legítimo | Según publicación |
| Evidencias | Documentación (D3) | Contrato | Privado; metadatos publicables con consentimiento (6.1.a) |
| Huellas HMAC de IP y sesión | Seguridad, auditoría | Interés legítimo | Interno |
| Registro de auditoría | Seguridad y rendición de cuentas | Interés legítimo | Interno |

## Derechos de los interesados

| Derecho | Mecanismo |
|---|---|
| Acceso y portabilidad | «Perfil y datos» → «Descargar mis datos» (JSON) |
| Rectificación | Editar perfil y obras; las declaraciones emitidas se rectifican **emitiendo una nueva versión** (la anterior se conserva marcada como sustituida) o revocándolas |
| Supresión | Solicitud desde el perfil o el formulario de contacto → incidencia `DATA_ERASURE` en Administración |
| Oposición, limitación | Formulario de contacto → incidencia |
| Retirada del consentimiento de publicación | «Retirar publicación» (inmediato) |

**Retirar la publicación ≠ suprimir datos ≠ revocar la declaración.** Son tres acciones distintas en la interfaz y en el modelo.

## Procedimiento de supresión (manual en el MVP)

1. Verificar la identidad del solicitante (desde la cuenta autenticada o por correo).
2. Retirar la publicación de todas sus declaraciones.
3. Eliminar borradores, obras sin declaraciones emitidas, perfil, sesiones y evidencias no referenciadas por versiones emitidas.
4. Para versiones emitidas: valorar con asesoría jurídica la base para conservarlas (integridad de documentos que pueden haber sido presentados a terceros). Si no procede conservarlas, la eliminación de filas protegidas por disparadores requiere una migración administrativa documentada y auditada (no hay vía en la aplicación, por diseño).
5. Seudonimizar el actor en `AuditLog` (`actorId → NULL`, permitido por el disparador) y desactivar la cuenta.
6. Responder en el plazo de un mes y cerrar la incidencia con la resolución.

> Pendiente: automatizar los pasos 2, 3 y 5; definir plazos de conservación.

## Mecanismos indirectos de revelación evitados

- Las secciones ocultas solo exponen un resumen salado (128 bits de sal): no pueden adivinarse.
- Las consultas públicas devuelven la misma respuesta para «no existe», «privada» y «retirada».
- La comprobación de ficheros solo confirma la coincidencia de un documento que el verificador ya posee.
- Los listados públicos solo muestran campos de secciones publicadas.
- Las estadísticas de administración son agregadas.
