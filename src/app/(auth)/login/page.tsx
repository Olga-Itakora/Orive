import Link from "next/link";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/auth/auth-shell";
import { LoginForm } from "@/components/auth/auth-forms";
import { Alert } from "@/components/ui/alert";
import { getCurrentUser } from "@/lib/auth/session";

export const metadata = { title: "Iniciar sesión" };

export default async function LoginPage({ searchParams }: { searchParams: Promise<{ next?: string; reset?: string }> }) {
  if (await getCurrentUser()) redirect("/app");
  const sp = await searchParams;
  return (
    <AuthShell
      title="Iniciar sesión"
      subtitle="Accede a tus obras, borradores y declaraciones."
      footer={
        <>
          ¿Aún no tienes cuenta?{" "}
          <Link href="/register" className="inline-flex min-h-11 items-center font-medium text-petrol-700 underline underline-offset-4">
            Crear cuenta
          </Link>
        </>
      }
    >
      {sp.reset && <Alert tone="ok" className="mb-4">Contraseña restablecida. Ya puedes iniciar sesión.</Alert>}
      <LoginForm next={sp.next} />
    </AuthShell>
  );
}
