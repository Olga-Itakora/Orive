import Link from "next/link";
import { Logo } from "@/components/site/logo";

export default function NotFound() {
  return (
    <main id="main" className="mx-auto flex min-h-dvh max-w-lg flex-col items-start justify-center gap-4 px-6">
      <Logo />
      <h1 className="text-2xl font-semibold text-petrol-900">Página no encontrada</h1>
      <p className="text-muted">La página que buscas no existe o no tienes acceso a ella.</p>
      <Link href="/" className="text-petrol-700 underline underline-offset-4">
        Volver al inicio
      </Link>
    </main>
  );
}
