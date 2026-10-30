import Link from "next/link";
import { CodeExplorer } from "@/components/oriva/oriva-code";
import { Alert } from "@/components/ui/alert";
import { DIMENSIONS, explainORIVACode, SCHEME_VERSION, type DimensionKey } from "@/lib/oriva-code";
import { D_THRESHOLDS } from "@/lib/oriva-code/engine";

export const metadata = { title: "ORIVA Code", description: "Qué significa cada dimensión y valor de ORIVA Code v1.0." };

const BC_EXAMPLES = [
  ["La IA analiza un ensayo y sus sugerencias se descartan", "B0", "C0"],
  ["La IA propone una trama y el autor escribe la novela", "B1", "C5"],
  ["La IA corrige erratas y el autor acepta los cambios", "B2", "C1"],
  ["La IA genera una ilustración que se incorpora directamente", "B3", "C1"],
  ["La IA genera una imagen que el artista transforma sustancialmente", "B3", "C3"],
  ["La IA genera un texto que el autor reelabora profundamente", "B3", "C4"],
];

export default async function CodePage({ searchParams }: { searchParams: Promise<{ c?: string }> }) {
  const { c } = await searchParams;
  const lookup = c ? explainORIVACode(c.slice(0, 40)) : null;
  return (
    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <p className="text-xs font-semibold tracking-[0.2em] text-accent uppercase">ORIVA Code v{SCHEME_VERSION}</p>
      <h1 className="mt-2 max-w-3xl text-3xl font-semibold tracking-tight text-petrol-900 sm:text-4xl">Una clasificación descriptiva en cuatro dimensiones</h1>
      <p className="mt-4 max-w-[65ch] text-lg text-muted">
        ORIVA Code resume la función de la IA, el tipo de aportación incorporada, la modalidad de intervención humana y el nivel de documentación. Formato compacto: <span className="font-mono text-ink">A#B#C#D#</span>, por ejemplo <span className="font-mono text-ink">A5B3C4D2</span>.
      </p>

      <Alert tone="warn" className="mt-6 max-w-3xl" title="Los números son categorías, no puntuaciones">
        El código no es un porcentaje de autoría ni una puntuación de originalidad, de mérito o de cumplimiento legal. No se suman ni se promedian. A5 no es «más IA» que A3; C5 no es «más autoría» que C4; D0 no es una declaración menos válida.
      </Alert>

      <section className="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-2" aria-labelledby="lookup">
        <div>
          <h2 id="lookup" className="text-xl font-semibold text-petrol-900">
            Consulta un código
          </h2>
          <form method="get" className="mt-4">
            <label htmlFor="c" className="mb-2 block text-sm font-medium">
              Código ORIVA
            </label>
            <div className="flex gap-2">
              <input id="c" name="c" defaultValue={c ?? ""} placeholder="A5B3C4D2" maxLength={40} className="h-11 min-w-0 flex-1 rounded-[var(--radius-md)] border border-line-strong bg-surface px-3 font-mono" />
              <button className="h-11 rounded-[var(--radius-md)] bg-petrol-800 px-4 text-sm font-medium text-white">Explicar código</button>
            </div>
          </form>
          {lookup && (
            <div className="mt-4 rounded-[var(--radius-lg)] border border-line bg-surface p-4" aria-live="polite">
              {lookup.valid ? (
                <ul className="space-y-3">
                  {lookup.segments.map((s) => (
                    <li key={s.code} className="text-sm">
                      <span className="font-mono font-medium text-petrol-800">{s.code}</span> — <strong>{s.label}</strong>
                      <span className="block text-xs text-muted">
                        {s.dimensionName}. {s.description}
                      </span>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-sm text-danger">{lookup.errors.join(" ")}</p>
              )}
            </div>
          )}
          <p className="mt-4 text-sm text-muted">
            Un código puede compartirse entre muchas obras: no identifica una declaración. Para consultar una declaración concreta usa su identificador en <Link href="/verify" className="underline">Verificar</Link>.
          </p>
        </div>
        <CodeExplorer code={lookup?.valid ? lookup.code : "A5B3C4D2"} />
      </section>

      {(Object.keys(DIMENSIONS) as DimensionKey[]).map((k) => {
        const dim = DIMENSIONS[k];
        return (
          <section key={k} className="mt-14" aria-labelledby={`dim-${k}`}>
            <h2 id={`dim-${k}`} className="text-xl font-semibold text-petrol-900">
              <span className="font-mono">{k}</span> · {dim.name.es}
            </h2>
            <p className="mt-1 text-muted">{dim.question.es}</p>
            <p className="mt-1 text-sm text-muted italic">{dim.notMeaning.es}</p>
            <div className="mt-4 relative overflow-x-auto rounded-[var(--radius-lg)] border border-line bg-surface">
              <table className="w-full min-w-[560px] text-sm">
                <thead className="border-b border-line text-left text-xs text-muted">
                  <tr>
                    <th scope="col" className="w-20 px-4 py-3 font-medium">Código</th>
                    <th scope="col" className="px-4 py-3 font-medium">Significado</th>
                    <th scope="col" className="px-4 py-3 font-medium">Ejemplo</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {dim.values.map((v) => (
                    <tr key={v.code} id={v.code}>
                      <td className="px-4 py-3 align-top font-mono font-medium text-petrol-800">{v.code}</td>
                      <td className="px-4 py-3 align-top">
                        <strong className="font-medium">{v.label.es}</strong>
                        <span className="block text-muted">{v.description.es}</span>
                      </td>
                      <td className="px-4 py-3 align-top text-muted">{v.example?.es ?? "—"}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        );
      })}

      <div className="prose-oriva mt-14">
        <h2>Relación entre B y C</h2>
        <p>
          B responde a «¿qué tipo de aportación de IA se incorporó a la obra?». C responde a «¿qué hizo la persona con esa aportación?». Cada uso conserva sus propias parejas B–C; nunca se empareja el B de un uso con el C de otro.
        </p>
      </div>
      <div className="mt-4 max-w-3xl relative overflow-x-auto rounded-[var(--radius-lg)] border border-line bg-surface">
        <table className="w-full text-sm">
          <thead className="border-b border-line text-left text-xs text-muted">
            <tr>
              <th scope="col" className="px-4 py-3 font-medium">Situación</th>
              <th scope="col" className="px-4 py-3 font-medium">B</th>
              <th scope="col" className="px-4 py-3 font-medium">C</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-line">
            {BC_EXAMPLES.map(([s, b, cc]) => (
              <tr key={s}>
                <td className="px-4 py-3">{s}</td>
                <td className="px-4 py-3 font-mono">{b}</td>
                <td className="px-4 py-3 font-mono">{cc}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="prose-oriva mt-12">
        <h2>Perfiles múltiples y perfil compuesto</h2>
        <p>Una obra puede contener varios usos de IA. ORIVA conserva la clasificación de cada uso, agrupa los usos por componente y genera una síntesis de la obra.</p>
        <p>
          Cuando todos los usos comparten la misma clasificación se muestra un código compacto. Cuando no, ORIVA <strong>no fabrica</strong> un código global eligiendo los números más altos: muestra un <em>perfil compuesto</em> con los valores presentes, por ejemplo <code className="font-mono">A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2</code>. Ese perfil es una síntesis, no una secuencia de parejas B–C.
        </p>
        <h2>Cómo se calcula D</h2>
        <p>D se calcula con criterios objetivos; el usuario no puede elegirla:</p>
        <ul>
          <li>D1: herramienta identificada por su nombre, al menos una función y una descripción de la actividad (≥ {D_THRESHOLDS.activityMinChars} caracteres).</li>
          <li>D2: además, al menos una fase, el resultado descrito (≥ {D_THRESHOLDS.resultMinChars}) y la intervención humana descrita en cada aportación (≥ {D_THRESHOLDS.interventionMinChars}).</li>
          <li>D3: además, al menos una evidencia registrada. Las evidencias no se auditan.</li>
          <li>La D de la obra es la menor de sus usos. Sin IA: D0, o D2 con una descripción del proceso de al menos {D_THRESHOLDS.narrativeMinChars} caracteres, y D3 si además hay evidencias.</li>
        </ul>
        <h2>Incertidumbre</h2>
        <p>Si faltan respuestas esenciales la clasificación queda «pendiente» y no puede emitirse. Si el declarante indica expresamente que no puede precisar algo, se registra como «información insuficiente» sin inventar valores. Si no puede determinar si se utilizó IA, la declaración lo refleja sin código.</p>
        <h2>Versionado</h2>
        <p>Cada clasificación registra la versión del esquema, la fecha de cálculo, las respuestas utilizadas y las reglas aplicadas. Las nuevas versiones de la taxonomía no modifican los códigos históricos.</p>
        <h2>Distintivo para webs</h2>
        <p>Las declaraciones publicadas ofrecen un distintivo incrustable que enlaza con la declaración. Describe el proceso declarado; no es un sello de certificación de originalidad.</p>
      </div>
    </div>
  );
}
