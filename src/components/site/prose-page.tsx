export function ProsePage({ eyebrow, title, lead, children, aside }: { eyebrow?: string; title: string; lead?: React.ReactNode; children: React.ReactNode; aside?: React.ReactNode }) {
  return (
    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      {eyebrow && <p className="text-xs font-semibold tracking-[0.2em] text-accent uppercase">{eyebrow}</p>}
      <h1 className="mt-2 max-w-3xl text-3xl font-semibold tracking-tight text-petrol-900 sm:text-4xl">{title}</h1>
      {lead && <p className="mt-4 max-w-[65ch] text-lg text-muted">{lead}</p>}
      <div className={aside ? "mt-10 grid gap-10 lg:grid-cols-[1fr_20rem]" : "mt-10"}>
        <div className="prose-oriva">{children}</div>
        {aside && <aside className="space-y-4">{aside}</aside>}
      </div>
    </div>
  );
}
