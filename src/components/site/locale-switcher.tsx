import { setLocale } from "@/app/actions/locale";

export function LocaleSwitcher({ current, label }: { current: string; label: string }) {
  return (
    <form action={setLocale} className="flex items-center gap-1 text-sm text-muted">
      <span className="mr-1">{label}:</span>
      {[
        ["es", "Español"],
        ["en", "English"],
      ].map(([code, name]) => (
        <button key={code} name="locale" value={code} aria-pressed={current === code} className="inline-flex min-h-11 items-center rounded px-2 text-sm hover:bg-petrol-50 aria-pressed:font-semibold aria-pressed:text-petrol-800">
          {name}
        </button>
      ))}
    </form>
  );
}
