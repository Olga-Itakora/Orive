import { activateQuestionnaireAction } from "@/app/actions/admin";
import { AdminTable, td } from "@/components/app/admin-table";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export default async function QuestionnairesPage() {
  const qs = await prisma.questionnaireVersion.findMany({ include: { scheme: true, _count: { select: { declarationVersions: true } } }, orderBy: { createdAt: "desc" } });
  return (
    <>
      <PageHeader title="Versiones del cuestionario" description="Cada versión de declaración registra la versión del cuestionario con la que se respondió. Las nuevas versiones se definen en código (prisma/seed.ts) y se activan aquí." />
      <AdminTable caption="Cuestionarios" head={["Versión", "Esquema", "Publicada", "Uso", "Estado"]}>
        {qs.map((q) => (
          <tr key={q.id}>
            <td className={`${td} font-mono`}>{q.version}</td>
            <td className={td}>{q.scheme.name}</td>
            <td className={td}>{formatDate(q.publishedAt)}</td>
            <td className={td}>{q._count.declarationVersions}</td>
            <td className={td}>
              {q.active ? (
                <Badge tone="ok">Activa</Badge>
              ) : (
                <form action={activateQuestionnaireAction.bind(null, q.id)}>
                  <Button size="sm" variant="secondary">Activar</Button>
                </form>
              )}
            </td>
          </tr>
        ))}
      </AdminTable>
      {qs[0] && (
        <details className="mt-6">
          <summary className="cursor-pointer text-sm font-medium">Definición de la versión {qs[0].version}</summary>
          <pre className="mt-2 max-h-96 overflow-auto rounded-md bg-surface p-3 text-xs">{JSON.stringify(qs[0].definition, null, 2)}</pre>
        </details>
      )}
    </>
  );
}
