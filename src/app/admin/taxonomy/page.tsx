import Link from "next/link";
import { deprecateSchemeAction } from "@/app/actions/admin";
import { AdminTable, td } from "@/components/app/admin-table";
import { PageHeader } from "@/components/app/page-header";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { prisma } from "@/lib/db";

export default async function TaxonomyPage() {
  const schemes = await prisma.transparencyScheme.findMany({
    include: { dimensions: { include: { values: true }, orderBy: { key: "asc" } }, rules: { orderBy: { ruleKey: "asc" } }, _count: { select: { declarationVersions: true } } },
    orderBy: { createdAt: "desc" },
  });
  return (
    <>
      <PageHeader title="Taxonomía ORIVA Code" description="Los esquemas publicados son inmutables: se conservan para interpretar las declaraciones emitidas con ellos. Una nueva versión se publica siguiendo el procedimiento de ORIVA_CODE_SPEC.md (§ Versionado)." />
      <Alert tone="info" className="mb-6">
        El motor de clasificación de cada versión vive en el código fuente (<span className="font-mono">src/lib/oriva-code</span>) y está cubierto por pruebas; este panel muestra la copia de gobernanza almacenada en base de datos. No es posible editar valores ni reglas de un esquema publicado.
      </Alert>
      {schemes.map((s) => (
        <section key={s.id} className="mb-10">
          <div className="mb-3 flex flex-wrap items-center gap-3">
            <h2 className="text-lg font-semibold">{s.name}</h2>
            <Badge tone={s.status === "PUBLISHED" ? "ok" : s.status === "DEPRECATED" ? "neutral" : "warn"}>{s.status}</Badge>
            <span className="text-sm text-muted">{s._count.declarationVersions} versiones de declaración lo usan</span>
            {s.status === "PUBLISHED" && (
              <form action={deprecateSchemeAction.bind(null, s.id)}>
                <Button size="sm" variant="link">Marcar como obsoleto</Button>
              </form>
            )}
          </div>
          <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
            {s.dimensions.map((d) => (
              <AdminTable key={d.id} caption={d.nameEs} head={[`${d.key} · ${d.nameEs}`, "Etiqueta"]}>
                {d.values.sort((a, b) => a.code.localeCompare(b.code)).map((v) => (
                  <tr key={v.id}>
                    <td className={`${td} font-mono`}>{v.code}</td>
                    <td className={td}>
                      {v.labelEs}
                      <span className="block text-xs text-muted">{v.descriptionEs}</span>
                    </td>
                  </tr>
                ))}
              </AdminTable>
            ))}
          </div>
          <h3 className="mt-6 mb-2 font-semibold">Reglas de clasificación</h3>
          <AdminTable caption="Reglas" head={["Regla", "Tipo", "Descripción"]}>
            {s.rules.map((r) => (
              <tr key={r.id}>
                <td className={`${td} font-mono text-xs`}>{r.ruleKey}</td>
                <td className={`${td} text-xs`}>{r.kind}</td>
                <td className={td}>{r.description}</td>
              </tr>
            ))}
          </AdminTable>
        </section>
      ))}
      <p className="text-sm text-muted">
        Consulta pública: <Link href="/code" className="underline">/code</Link>
      </p>
    </>
  );
}
