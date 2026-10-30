import { PageHeader } from "@/components/app/page-header";
import { Card, CardBody, CardHeader, CardTitle } from "@/components/ui/card";
import { prisma } from "@/lib/db";
import { labelOf } from "@/lib/oriva-code";

/** Estadísticas agregadas: sin datos personales ni contenido de declaraciones. */
export default async function AdminHome() {
  const [users, works, byStatus, byVisibility, openIncidents, useProfiles] = await Promise.all([
    prisma.user.count(),
    prisma.work.count(),
    prisma.declarationVersion.groupBy({ by: ["status"], _count: true }),
    prisma.declarationVersion.groupBy({ by: ["visibility"], where: { status: { not: "DRAFT" } }, _count: true }),
    prisma.incident.count({ where: { status: { in: ["OPEN", "IN_PROGRESS"] } } }),
    prisma.transparencyProfile.findMany({ where: { scope: "USE" }, select: { functions: true, pairs: true, documentation: true } }),
  ]);
  const count = (xs: string[]) => xs.reduce<Record<string, number>>((m, x) => ((m[x] = (m[x] ?? 0) + 1), m), {});
  const A = count(useProfiles.flatMap((p) => p.functions));
  const pairs = useProfiles.flatMap((p) => p.pairs as { b: string; c: string | null }[]);
  const B = count(pairs.map((p) => p.b));
  const C = count(pairs.map((p) => p.c ?? "sin precisar"));
  const D = count(useProfiles.map((p) => p.documentation));
  const stat = (label: string, value: number | string) => (
    <div className="rounded-[var(--radius-lg)] border border-line bg-surface p-4">
      <p className="text-xs text-muted">{label}</p>
      <p className="mt-1 text-2xl font-semibold text-petrol-900">{value}</p>
    </div>
  );
  const dist = (title: string, m: Record<string, number>) => (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardBody>
        {Object.keys(m).length === 0 ? (
          <p className="text-sm text-muted">Sin datos.</p>
        ) : (
          <ul className="space-y-1 text-sm">
            {Object.entries(m)
              .sort()
              .map(([k, n]) => (
                <li key={k} className="flex justify-between gap-3">
                  <span>
                    <span className="font-mono">{k}</span> <span className="text-muted">{labelOf(k)}</span>
                  </span>
                  <span className="font-mono">{n}</span>
                </li>
              ))}
          </ul>
        )}
      </CardBody>
    </Card>
  );
  return (
    <>
      <PageHeader title="Resumen" description="Estadísticas agregadas. Los recuentos de valores no son una medida de calidad ni de mérito." />
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {stat("Usuarios", users)}
        {stat("Obras", works)}
        {stat("Incidencias abiertas", openIncidents)}
        {stat("Declaraciones públicas", byVisibility.find((x) => x.visibility === "PUBLIC")?._count ?? 0)}
      </div>
      <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-4">
        {(["DRAFT", "ISSUED", "SUPERSEDED", "REVOKED"] as const).map((s) => stat({ DRAFT: "Borradores", ISSUED: "Emitidas", SUPERSEDED: "Sustituidas", REVOKED: "Revocadas" }[s], byStatus.find((x) => x.status === s)?._count ?? 0))}
      </div>
      <h2 className="mt-8 mb-3 font-semibold">Distribución de valores en usos emitidos</h2>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        {dist("A · Función", A)}
        {dist("B · Aportación", B)}
        {dist("C · Intervención", C)}
        {dist("D · Documentación", D)}
      </div>
    </>
  );
}
