/** Formulario para consultar un identificador de declaración o el significado de un código. */
export function LookupForm() {
  return (
    <form action="/verify" method="get" className="rounded-[var(--radius-lg)] border border-line bg-paper p-5">
      <label htmlFor="lookup-q" className="text-sm font-medium text-ink">
        Consultar un código o una declaración
      </label>
      <p id="lookup-hint" className="mt-1 text-sm text-muted">
        Introduce un identificador (ORV-XXXX-XXXX-XXXX) o un código como A5B3C4D2.
      </p>
      <div className="mt-3 flex gap-2">
        <input
          id="lookup-q"
          name="q"
          aria-describedby="lookup-hint"
          required
          maxLength={60}
          placeholder="ORV-… o A5B3C4D2"
          className="h-11 min-w-0 flex-1 rounded-[var(--radius-md)] border border-line-strong bg-surface px-3 font-mono text-sm"
        />
        <button className="h-11 rounded-[var(--radius-md)] bg-petrol-800 px-4 text-sm font-medium text-white hover:bg-petrol-700">Consultar</button>
      </div>
    </form>
  );
}
