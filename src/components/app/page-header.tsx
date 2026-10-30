import Link from "next/link";
import { ChevronLeft } from "lucide-react";

export function PageHeader({ title, description, back, actions }: { title: React.ReactNode; description?: React.ReactNode; back?: { href: string; label: string }; actions?: React.ReactNode }) {
  return (
    <div className="mb-8">
      {back && (
        <Link href={back.href} className="mb-2 inline-flex min-h-11 items-center gap-1 text-sm text-muted hover:text-petrol-800">
          <ChevronLeft className="h-4 w-4" aria-hidden /> {back.label}
        </Link>
      )}
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div className="min-w-0">
          <h1 className="text-2xl font-semibold text-petrol-900">{title}</h1>
          {description && <p className="mt-2 max-w-[70ch] text-sm text-muted">{description}</p>}
        </div>
        {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
      </div>
    </div>
  );
}
