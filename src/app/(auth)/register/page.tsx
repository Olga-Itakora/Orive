import Link from "next/link";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/auth/auth-shell";
import { RegisterForm } from "@/components/auth/auth-forms";
import { getCurrentUser } from "@/lib/auth/session";

export const metadata = { title: "Crear cuenta" };

export default async function RegisterPage() {
  if (await getCurrentUser()) redirect("/app");
  return (
    <AuthShell
      title="Crea tu cuenta ORIVA"
      subtitle="Documenta tu proceso creativo y emite declaraciones verificables. Todo es privado hasta que decidas publicarlo."
      footer={
        <>
          ¿Ya tienes cuenta?{" "}
          <Link href="/login" className="inline-flex min-h-11 items-center font-medium text-petrol-700 underline underline-offset-4">
            Iniciar sesión
          </Link>
        </>
      }
    >
      <RegisterForm />
    </AuthShell>
  );
}
