import Link from "next/link";
import { AuthShell } from "@/components/auth/auth-shell";
import { ResetForm } from "@/components/auth/auth-forms";

export const metadata = { title: "Nueva contraseña", robots: { index: false } };

export default async function ResetPage({ params }: { params: Promise<{ token: string }> }) {
  const { token } = await params;
  return (
    <AuthShell title="Elige una nueva contraseña" subtitle="Al cambiarla se cerrarán todas las sesiones abiertas." footer={<Link href="/login" className="inline-flex min-h-11 items-center font-medium text-petrol-700 underline underline-offset-4">Volver a iniciar sesión</Link>}>
      <ResetForm token={token} />
    </AuthShell>
  );
}
