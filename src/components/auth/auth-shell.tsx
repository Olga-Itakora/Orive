export function AuthShell({ title, subtitle, children, footer }: { title: string; subtitle?: string; children: React.ReactNode; footer?: React.ReactNode }) {
  return (
    <div>
      <div className="rounded-[var(--radius-lg)] border border-line bg-surface p-6 sm:p-8">
        <h1 className="text-2xl font-semibold text-petrol-900">{title}</h1>
        {subtitle && <p className="mt-2 text-sm text-muted">{subtitle}</p>}
        <div className="mt-6">{children}</div>
      </div>
      {footer && <div className="mt-6 text-center text-sm text-muted">{footer}</div>}
    </div>
  );
}
