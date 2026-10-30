# API pública y procedimiento de verificación

URL base configurable mediante `APP_URL` (producción prevista: `https://orivacode.com`). Todas las respuestas son JSON salvo indicación. Límites de frecuencia por IP.

## `GET /api/v1/declarations/{publicId}`

Devuelve **solo** lo publicado por el declarante. Responde `404` igual si la declaración no existe, es privada o se retiró su publicación. CORS abierto (`Access-Control-Allow-Origin: *`).

```json
{
  "apiVersion": "1",
  "publicId": "ORV-WAZD-XJ45-NPCF",
  "status": "ISSUED | SUPERSEDED | REVOKED",
  "issuedAt": "2026-10-30T11:57:00.000Z",
  "revokedAt": null,
  "supersededBy": "ORV-… | (no pública) | null",
  "supersedes": null,
  "verification": { "algorithm": "SHA-256", "canonicalization": "RFC8785-subset(integers-only)", "contentHash": "…", "pdfFullSha256": "…", "pdfSummarySha256": "…" },
  "disclosedSections": ["meta", "work", "scope", "classification", "summary"],
  "view": { "format": "oriva-declaration/1", "sections": { "meta": { "salt": "…", "data": { } } }, "digests": { "meta": "…", "declarant": "…" } }
}
```

## `POST /api/v1/declarations/{publicId}/check`

Cuerpo: bytes del fichero (PDF o JSON, ≤15 MB). Respuesta:

```json
{ "kind": "pdf-full | pdf-summary | json | none", "match": true, "status": "ISSUED" }
```

Funciona también para declaraciones no publicadas: solo confirma la coincidencia de un documento que el verificador ya tiene. No almacena el fichero.

## `GET /api/v1/codes/{code}?locale=es|en`

Explica un código compacto o un valor (`A5B3C4D2`, `B3`). `400` si no es válido.

## Otros recursos públicos

| Ruta | Contenido |
|---|---|
| `/verify/{publicId}` | Página pública de verificación |
| `/verify/{publicId}/qr.svg` | QR que enlaza con la página |
| `/verify/{publicId}/pdf?type=summary\|full` | PDF, solo si el autor publicó nombre y el contenido necesario |
| `/badge/{publicId}.svg` | Distintivo incrustable (descriptivo, no certificación) |

## Procedimiento de verificación (reproducible)

1. **JSON canónico** (`canonical`): claves ordenadas, sin espacios, cadenas con escapes JSON estándar, solo enteros (subconjunto de RFC 8785).
2. **Resumen de sección** para cada una de las 10 secciones `meta, work, declarant, scope, classification, summary, process, article50, statement, evidence`:
   `digest_s = SHA-256( canonical({ "section": s, "salt": salt_s, "data": data_s }) )` (hex en minúsculas).
3. **Huella raíz**: `contentHash = SHA-256( canonical({ "format": "oriva-declaration/1", "sections": { s: digest_s, … } }) )`.
4. Con una vista divulgada, las secciones ocultas aportan solo `digests[s]`; las visibles se recalculan y deben coincidir con `digests[s]`.
5. Comparar `contentHash` con el impreso en el PDF, el mostrado en `/verify/{id}` o el devuelto por la API.

Implementaciones: `src/lib/integrity/disclosure.ts` (`verifyDisclosed`), botón «Recalcular la huella en mi navegador» (WebCrypto) y `npx tsx scripts/verify-declaration.ts fichero.json [huella]`.

**Qué se verifica:** que el contenido registrado en una fecha no ha sido alterado y, para los PDF, que el fichero es idéntico al emitido.
**Qué no se verifica:** identidad del declarante, veracidad, originalidad, titularidad de derechos, cumplimiento legal. La fecha procede del reloj del servidor de ORIVA (no es un sello de tiempo cualificado).
