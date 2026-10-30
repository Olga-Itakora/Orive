import type { FullSnapshot } from "@/lib/integrity/disclosure";
import { CANONICALIZATION, HASH_ALGORITHM } from "@/lib/integrity/disclosure";

/** Exportación JSON versionada de una declaración emitida (instantánea completa + datos de verificación). */
export function exportDocument(args: { snapshot: FullSnapshot; contentHash: string; sectionDigests: unknown; pdfFullHash: string; pdfSummaryHash: string; status: string }) {
  return {
    $schema: "https://orivacode.com/schemas/oriva-declaration-export/1.json",
    exportFormat: "oriva-declaration-export/1",
    verification: {
      algorithm: HASH_ALGORITHM,
      canonicalization: CANONICALIZATION,
      contentHash: args.contentHash,
      sectionDigests: args.sectionDigests,
      pdfFullSha256: args.pdfFullHash,
      pdfSummarySha256: args.pdfSummaryHash,
      procedure:
        "Para cada sección s: digest = SHA-256(canonical({section: s, salt, data})). contentHash = SHA-256(canonical({format, sections: {s: digest}})). canonical = JSON con claves ordenadas, sin espacios, solo enteros. Ver https://orivacode.com/code y API.md.",
      statusAtExport: args.status,
      note: "La huella acredita la integridad del contenido registrado; no acredita identidad, veracidad, originalidad, titularidad de derechos ni cumplimiento legal.",
    },
    snapshot: args.snapshot,
  };
}
