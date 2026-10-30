import { notFound } from "next/navigation";
import { ProsePage } from "./prose-page";
import { Alert } from "@/components/ui/alert";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export async function LegalTextPage({ textKey }: { textKey: string }) {
  const t = await prisma.legalText.findFirst({ where: { key: textKey, locale: "es", active: true }, orderBy: { publishedAt: "desc" } });
  if (!t) notFound();
  return (
    <ProsePage eyebrow="Legal" title={t.title} lead={`Versión ${t.version} · publicada el ${formatDate(t.publishedAt)}`}>
      {t.pendingLegalReview && (
        <Alert tone="warn" className="mb-6">
          Texto provisional pendiente de revisión jurídica profesional.
        </Alert>
      )}
      {t.content.split(/\n{2,}/).map((para, i) => (
        <p key={i} className="whitespace-pre-line">
          {para}
        </p>
      ))}
    </ProsePage>
  );
}
