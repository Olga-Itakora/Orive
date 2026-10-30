import Link from "next/link";
import { AuthShell } from "@/components/auth/auth-shell";
import { ForgotForm } from "@/components/auth/auth-forms";

export const metadata = { title: "Recuperar acceso" };

export default function ForgotPage() {
  return (
    <AuthShell title="Recuperar acceso" subtitle="Te enviaremos un enlace de un solo uso, válido durante una hora." footer={<Link href="/login" className="inline-flex min-h-11 items-center font-medium text-petrol-700 underline underline-offset-4">Volver a iniciar sesión</Link>}>
      <ForgotForm />
    </AuthShell>
  );
}
