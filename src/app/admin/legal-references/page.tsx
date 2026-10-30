import { updateLegalReferenceAction } from "@/app/actions/admin";
import { LegalReferenceForm } from "@/components/app/admin-forms";
import { PageHeader } from "@/components/app/page-header";
import { prisma } from "@/lib/db";

export default async function LegalReferencesPage() {
  const refs = await prisma.legalReference.findMany({ orderBy: { key: "asc" } });
  return (
    <>
      <PageHeader title="Referencias normativas" description="Utilizadas en la orientación del artículo 50 y en la página de marco normativo. Revisa periódicamente su vigencia frente a la versión consolidada en EUR-Lex." />
      <div className="space-y-4">
        {refs.map((r) => (
          <details key={r.id} className="rounded-[var(--radius-lg)] border border-line bg-surface p-4">
            <summary className="cursor-pointer text-sm font-medium">
              <span className="font-mono text-xs text-muted">{r.key}</span> {r.title}
            </summary>
            <div className="mt-3">
              <LegalReferenceForm action={updateLegalReferenceAction.bind(null, r.id)} values={{ title: r.title, citation: r.citation, url: r.url, summary: r.summary, verificationNote: r.verificationNote ?? "", active: r.active }} />
            </div>
          </details>
        ))}
      </div>
    </>
  );
}
