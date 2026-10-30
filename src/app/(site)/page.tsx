import Link from "next/link";
import { ArrowRight, BookOpen, Building2, Clapperboard, FlaskConical, Palette, PenLine, Trophy } from "lucide-react";
import { ButtonLink } from "@/components/ui/button";
import { CodeExplorer } from "@/components/oriva/oriva-code";
import { LookupForm } from "@/components/oriva/lookup-form";

const steps = [
  { n: "1", title: "Describe tu proceso creativo", text: "Un cuestionario guiado, sin tecnicismos, para registrar cada uso concreto de IA: para qué, en qué fase, qué se incorporó a la obra y qué hiciste tú con ello." },
  { n: "2", title: "Obtén tu código ORIVA", text: "Un motor de reglas deterministas clasifica cada uso en cuatro dimensiones. Sin puntuaciones, sin porcentajes y sin modelos generativos decidiendo por ti." },
  { n: "3", title: "Emite y comparte tu declaración", text: "Acepta la declaración responsable, descarga el PDF con su huella criptográfica y decide qué publicar. Cualquiera puede verificar su integridad." },
];

const audiences = [
  { icon: PenLine, label: "Escritores" },
  { icon: Palette, label: "Artistas" },
  { icon: FlaskConical, label: "Investigadores" },
  { icon: BookOpen, label: "Editoriales" },
  { icon: Building2, label: "Instituciones" },
  { icon: Trophy, label: "Concursos" },
  { icon: Clapperboard, label: "Creadores audiovisuales" },
];

const why = [
  { title: "La IA interviene de formas muy distintas", text: "Corregir erratas, sugerir una trama o generar una ilustración no son lo mismo. Tampoco la cantidad de material generado equivale a su importancia creativa." },
  { title: "Un sí o un no no basta", text: "«¿Has usado IA?» no describe un proceso. ORIVA distingue la función de la IA, lo que se incorporó y lo que hizo la persona." },
  { title: "Una clasificación multidimensional", text: "Cuatro dimensiones (A, B, C, D) con valores categóricos. Una obra híbrida conserva el perfil de cada uso y de cada componente." },
  { title: "El autor conserva el control", text: "Todo es privado por defecto. Tú decides si publicas tu nombre, tu obra, el código, el resumen o la declaración completa." },
  { title: "El lector puede entenderlo", text: "Cada código enlaza con su significado y con la declaración verificable. Un lenguaje común para autores, editoriales y lectores." },
];

export default function HomePage() {
  return (
    <>
      {/* Hero */}
      <section className="border-b border-line">
        <div className="mx-auto grid grid-cols-1 max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-[1.15fr_1fr]">
          <div>
            <p className="text-xs font-semibold tracking-[0.25em] text-accent uppercase">ORIVA · Creative AI Transparency</p>
            <h1 className="mt-4 text-4xl leading-[1.1] font-semibold tracking-tight text-petrol-900 sm:text-5xl">
              Every creation has a process.
              <br />
              <span className="text-petrol-600">Make it visible.</span>
            </h1>
            <p className="mt-2 text-sm text-muted italic">Toda creación tiene un proceso. Hazlo visible.</p>
            <p className="mt-6 max-w-[60ch] text-lg text-ink">
              ORIVA te permite documentar cómo has utilizado la inteligencia artificial en tu proceso creativo, generar un código de transparencia y emitir una declaración responsable que puedes compartir con lectores, editoriales e instituciones.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <ButtonLink href="/app/works/new" size="lg">
                Crear declaración <ArrowRight className="h-4 w-4" aria-hidden />
              </ButtonLink>
              <ButtonLink href="/verify" size="lg" variant="secondary">
                Consultar código ORIVA
              </ButtonLink>
              <ButtonLink href="/code" size="lg" variant="ghost">
                Descubrir ORIVA Code
              </ButtonLink>
            </div>
          </div>
          <div className="space-y-3">
            <div className="rounded-[var(--radius-lg)] bg-petrol-800 p-6 text-white">
              <p className="text-xs tracking-wider text-petrol-200 uppercase">Ejemplo de perfil de una obra híbrida</p>
              <ul className="mt-4 space-y-3 font-mono text-sm">
                <li className="flex justify-between gap-4 border-b border-white/10 pb-3">
                  <span>A4B2C1</span>
                  <span className="font-sans text-petrol-100">Revisión lingüística aceptada</span>
                </li>
                <li className="flex justify-between gap-4 border-b border-white/10 pb-3">
                  <span>A2B1C5</span>
                  <span className="font-sans text-petrol-100">Idea desarrollada por la autora</span>
                </li>
                <li className="flex justify-between gap-4">
                  <span>A5B3C3</span>
                  <span className="font-sans text-petrol-100">Ilustración generada y transformada</span>
                </li>
              </ul>
              <p className="mt-5 border-t border-white/10 pt-4 font-mono text-base">A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2</p>
              <p className="mt-1 text-xs text-petrol-200">Perfil compuesto: se conservan los tres usos y sus relaciones.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Cómo funciona */}
      <section aria-labelledby="how" className="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <h2 id="how" className="text-2xl font-semibold text-petrol-900 sm:text-3xl">
          Cómo funciona
        </h2>
        <ol className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3">
          {steps.map((s) => (
            <li key={s.n} className="rounded-[var(--radius-lg)] border border-line bg-surface p-6">
              <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-petrol-50 font-mono text-sm font-medium text-petrol-800">{s.n}</span>
              <h3 className="mt-4 font-semibold text-ink">{s.title}</h3>
              <p className="mt-2 text-sm text-muted">{s.text}</p>
            </li>
          ))}
        </ol>
      </section>

      {/* Entiende tu código */}
      <section aria-labelledby="understand" className="border-y border-line bg-surface">
        <div className="mx-auto grid grid-cols-1 max-w-6xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[1fr_1.2fr]">
          <div>
            <h2 id="understand" className="text-2xl font-semibold text-petrol-900 sm:text-3xl">
              Entiende tu código
            </h2>
            <p className="mt-4 text-muted">
              Cada letra es una dimensión; cada número, una categoría. Selecciona un segmento para ver su significado. Los números no son puntuaciones: A5 no es «más IA» que A3, y C5 no significa «más autoría» que C4.
            </p>
            <dl className="mt-6 grid grid-cols-2 gap-3 text-sm">
              {[
                ["A", "Función de la IA"],
                ["B", "Aportación incorporada"],
                ["C", "Intervención humana"],
                ["D", "Documentación"],
              ].map(([k, v]) => (
                <div key={k} className="rounded-md bg-paper p-3">
                  <dt className="font-mono text-lg text-petrol-800">{k}</dt>
                  <dd className="text-muted">{v}</dd>
                </div>
              ))}
            </dl>
          </div>
          <div className="space-y-6">
            <CodeExplorer code="A5B3C4D2" />
            <LookupForm />
          </div>
        </div>
      </section>

      {/* Para quién */}
      <section aria-labelledby="who" className="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <h2 id="who" className="text-2xl font-semibold text-petrol-900 sm:text-3xl">
          Para quién es
        </h2>
        <ul className="mt-8 grid grid-cols-2 gap-x-8 gap-y-3 sm:grid-cols-4">
          {audiences.map(({ icon: Icon, label }) => (
            <li key={label} className="flex items-center gap-2 text-base text-ink">
              <Icon className="h-4 w-4 text-muted" aria-hidden /> {label}
            </li>
          ))}
        </ul>
        <p className="mt-6 max-w-[70ch] text-sm text-muted">
          Literatura, publicaciones académicas, ilustración, fotografía, música, cine, artes escénicas, videojuegos, diseño, arquitectura y obras multimedia. Las obras híbridas pueden describir cada componente por separado.
        </p>
      </section>

      {/* Por qué */}
      <section aria-labelledby="why" className="border-y border-line bg-surface">
        <div className="mx-auto max-w-6xl px-4 py-20 sm:px-6">
          <h2 id="why" className="text-2xl font-semibold text-petrol-900 sm:text-3xl">
            Por qué ORIVA
          </h2>
          <div className="mt-10 grid grid-cols-1 gap-x-10 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
            {why.map((w) => (
              <div key={w.title}>
                <h3 className="font-semibold text-ink">{w.title}</h3>
                <p className="mt-2 text-sm text-muted">{w.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Marco normativo */}
      <section aria-labelledby="legal" className="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_1fr]">
          <div>
            <h2 id="legal" className="text-2xl font-semibold text-petrol-900 sm:text-3xl">
              Marco normativo
            </h2>
            <p className="mt-4 text-muted">
              El Reglamento (UE) 2024/1689 de Inteligencia Artificial establece obligaciones específicas de transparencia en su artículo 50, aplicable desde el 2 de agosto de 2026: por ejemplo, para determinadas ultrasuplantaciones o textos que informan sobre asuntos de interés público. No obliga a todos los autores a declarar su uso de IA.
            </p>
            <p className="mt-4 text-muted">
              ORIVA distingue entre esas obligaciones, los requisitos de editoriales, concursos o instituciones y las buenas prácticas voluntarias de transparencia. Incluye una orientación sobre el artículo 50, separada del código ORIVA.
            </p>
            <Link href="/legal-framework" className="mt-6 inline-flex min-h-11 items-center gap-1 text-sm font-medium text-petrol-700 underline underline-offset-4">
              Leer el marco normativo <ArrowRight className="h-3.5 w-3.5" aria-hidden />
            </Link>
          </div>
          <aside className="rounded-[var(--radius-lg)] border border-[#f0dcae] bg-warn-soft p-6 text-sm text-[#5c3d00]">
            <p className="font-semibold">ORIVA no es una certificación oficial</p>
            <p className="mt-2">
              ORIVA no es un servicio de la Unión Europea ni certifica el cumplimiento normativo. Las declaraciones son manifestaciones responsables de sus autores. La plataforma acredita técnicamente que una declaración se registró con un contenido determinado y que no ha sido alterada; no acredita la identidad del declarante, la veracidad de lo declarado, la originalidad ni la titularidad de derechos.
            </p>
          </aside>
        </div>
      </section>
    </>
  );
}
