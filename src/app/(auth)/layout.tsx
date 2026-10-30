import Link from "next/link";
import { Logo } from "@/components/site/logo";

/** Autenticación: tarjeta centrada, sin la navegación del sitio. */
export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <main id="main" className="flex min-h-dvh flex-col items-center px-4 py-12">
      <Link href="/" aria-label="ORIVA — volver al inicio" className="inline-flex min-h-11 items-center">
        <Logo withDescriptor />
      </Link>
      <div className="mt-8 w-full max-w-md">{children}</div>
      <Link href="/" className="mt-8 inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">
        Volver al inicio
      </Link>
    </main>
  );
}
