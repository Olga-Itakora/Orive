import { createWorkAction } from "@/app/actions/works";
import { PageHeader } from "@/components/app/page-header";
import { WorkForm } from "@/components/app/work-form";
import { Card, CardBody } from "@/components/ui/card";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";

export const metadata = { title: "Nueva obra" };

export default async function NewWorkPage() {
  await requireUser();
  const categories = await prisma.workCategory.findMany({ where: { active: true }, orderBy: { sortOrder: "asc" } });
  return (
    <>
      <PageHeader
        title="Paso 1 · Identifica tu obra"
        description="Solo el título y el tipo de obra son obligatorios. Después podrás añadir componentes (por ejemplo, texto e ilustraciones) y empezar la declaración."
        back={{ href: "/app/works", label: "Mis obras" }}
      />
      <Card>
        <CardBody className="py-6">
          <WorkForm action={createWorkAction} categories={categories} draftKey="work:new" submitLabel="Guardar obra" extraSubmit={{ name: "then", value: "declare", label: "Guardar y empezar la declaración" }} />
        </CardBody>
      </Card>
    </>
  );
}
