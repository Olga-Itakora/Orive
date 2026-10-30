import { signOutOthersAction } from "@/app/actions/profile";
import { PageHeader } from "@/components/app/page-header";
import { ErasureForm, PasswordForm, ProfileForm } from "@/components/app/profile-forms";
import { ButtonAnchor } from "@/components/ui/button";
import { Card, CardBody, CardHeader, CardTitle } from "@/components/ui/card";
import { SubmitButton } from "@/components/ui/submit-button";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Perfil y datos" };

export default async function ProfilePage() {
  const me = await requireUser();
  const [profile, sessions] = await Promise.all([prisma.creatorProfile.findUnique({ where: { userId: me.id } }), prisma.session.findMany({ where: { userId: me.id }, orderBy: { createdAt: "desc" } })]);
  return (
    <>
      <PageHeader title="Perfil y datos" description={`Cuenta: ${me.email} (privado)`} />
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Perfil profesional</CardTitle>
            <p className="mt-1 text-xs text-muted">Se usa para rellenar nuevas declaraciones. Solo se publica si lo decides en cada declaración.</p>
          </CardHeader>
          <CardBody>
            <ProfileForm values={{ displayName: profile?.displayName ?? "", artisticName: profile?.artisticName ?? "", organization: profile?.organization ?? "", bio: profile?.bio ?? "", website: profile?.website ?? "" }} />
          </CardBody>
        </Card>
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Seguridad</CardTitle>
            </CardHeader>
            <CardBody className="space-y-6">
              <PasswordForm />
              <div className="border-t border-line pt-4 text-sm">
                <p className="font-medium">Sesiones abiertas: {sessions.length}</p>
                <ul className="mt-1 space-y-1 text-xs text-muted">
                  {sessions.slice(0, 5).map((s) => (
                    <li key={s.id}>
                      {formatDate(s.createdAt, true)} · {s.userAgent?.slice(0, 60) ?? "Navegador desconocido"} {s.id === me.sessionId && "(esta sesión)"}
                    </li>
                  ))}
                </ul>
                {sessions.length > 1 && (
                  <form action={signOutOthersAction} className="mt-2">
                    <SubmitButton variant="secondary" size="sm">
                      Cerrar las demás sesiones
                    </SubmitButton>
                  </form>
                )}
              </div>
            </CardBody>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Tus datos</CardTitle>
            </CardHeader>
            <CardBody className="space-y-5 text-sm">
              <div>
                <p className="text-muted">Descarga una copia de tus datos personales, obras y declaraciones en formato JSON (derechos de acceso y portabilidad).</p>
                <ButtonAnchor href="/app/profile/export" variant="secondary" size="sm" className="mt-2" download>
                  Descargar mis datos
                </ButtonAnchor>
              </div>
            </CardBody>
          </Card>
        </div>
      </div>
      <section aria-labelledby="danger" className="mt-10 rounded-[var(--radius-lg)] border border-[#f1c4c1] bg-surface p-6">
        <h2 id="danger" className="text-base font-semibold text-danger">
          Zona de peligro: supresión de la cuenta
        </h2>
        <p className="mt-2 max-w-[70ch] text-sm text-muted">
          Retirar la publicación de una declaración no elimina tus datos. Para suprimir tu cuenta, envía una solicitud; se atenderá en el plazo legal teniendo en cuenta las obligaciones legítimas de conservación de las declaraciones emitidas.
        </p>
        <div className="mt-4 max-w-xl">
          <ErasureForm />
        </div>
      </section>
    </>
  );
}
