"use client";
import { useEffect, useMemo, useRef, useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { saveUseAction } from "@/app/actions/declarations";
import type { UseInput } from "@/lib/validation/use";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Choice, ChoiceGroup, Field, Input, Select, Textarea } from "@/components/ui/form";
import { classifyAIUse, CREATIVE_PHASES, getValue, PHASE_LABELS, type IncorporatedContributionCode, type InterventionAnswer, type UseFunctionCode } from "@/lib/oriva-code";

const FUNCTIONS: { code: UseFunctionCode; label: string; help: string }[] = [
  { code: "A1", label: "Consultar, documentarme o explorar información", help: "Buscar datos, contexto o referencias." },
  { code: "A2", label: "Obtener ideas, sugerencias o alternativas", help: "Tramas, títulos, composiciones, enfoques…" },
  { code: "A3", label: "Recibir una revisión, crítica o evaluación", help: "Una lectura crítica, comentarios sobre una versión." },
  { code: "A4", label: "Corregir, editar, traducir o transformar algo existente", help: "Ortografía, estilo, traducción, retoque, limpieza de audio…" },
  { code: "A5", label: "Generar contenido", help: "Texto, imagen, música, sonido, vídeo, código u otros elementos." },
];

const CONTRIBUTIONS: { code: IncorporatedContributionCode; label: string; help: string }[] = [
  { code: "B1", label: "Ideas, conceptos o estructuras", help: "Que después desarrollaste tú. Ej.: una trama, un enfoque, un índice." },
  { code: "B2", label: "Cambios formales", help: "Correcciones, reformulaciones, traducciones o cambios de estilo." },
  { code: "B3", label: "Contenido expresivo generado", help: "Textos, imágenes, música, sonidos, vídeos o código creativo generados con IA." },
];

const INTERVENTIONS: { value: InterventionAnswer; label: string; help: string }[] = [
  { value: "ACCEPTED", label: "Lo seleccioné o lo acepté sin cambios significativos", help: "Elegir entre varias opciones también es una decisión creativa." },
  { value: "MINOR_EDITS", label: "Hice ajustes menores", help: "Retoques puntuales: algunas palabras, colores, detalles." },
  { value: "TRANSFORMED", label: "Lo transformé", help: "Lo modifiqué considerablemente o lo usé como punto de partida." },
  { value: "OWN_EXPRESSION", label: "Solo tomé la idea o la referencia; la expresión final la realicé yo", help: "Ej.: escribí la novela a partir de una idea surgida en la conversación." },
  { value: "UNSURE", label: "No puedo precisarlo", help: "Se registrará como información insuficiente, sin inventar una clasificación." },
];

interface ContribState {
  type: IncorporatedContributionCode;
  interventionAnswer: InterventionAnswer | null;
  retainsRecognizableElements: "YES" | "NO" | "UNSURE" | null;
  interventionDescription: string;
}

export interface UseEditorProps {
  versionId: string;
  useId: string | null;
  initial: UseInput;
  components: { id: string; name: string }[];
  tools: { id: string; name: string; identified: boolean }[];
  otherUses: { id: string; title: string }[];
  phaseHints: Record<string, string>;
  evidenceCount: number;
  declarationEvidenceCount: number;
}

export function UseEditor(props: UseEditorProps) {
  const { versionId, useId, initial, components, tools, otherUses, phaseHints } = props;
  const router = useRouter();
  const [pending, start] = useTransition();
  const [s, setS] = useState<UseInput>(initial);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [msg, setMsg] = useState<{ tone: "ok" | "danger"; text: string } | null>(null);
  const [restored, setRestored] = useState(false);
  const dirty = useRef(false);
  const draftKey = `oriva:draft:use:${versionId}:${useId ?? "new"}`;

  // Borrador local: se guarda en cada cambio y se recupera al volver (sin pérdida de datos).
  useEffect(() => {
    try {
      const raw = localStorage.getItem(draftKey);
      if (raw) {
        const saved = JSON.parse(raw) as { at: number; data: UseInput };
        if (Date.now() - saved.at < 7 * 86400_000 && JSON.stringify(saved.data) !== JSON.stringify(initial)) {
          setS(saved.data);
          setRestored(true);
          dirty.current = true;
        } else localStorage.removeItem(draftKey);
      }
    } catch {
      /* almacenamiento no disponible */
    }
    const warn = (e: BeforeUnloadEvent) => {
      if (dirty.current) {
        e.preventDefault();
        e.returnValue = "";
      }
    };
    window.addEventListener("beforeunload", warn);
    return () => window.removeEventListener("beforeunload", warn);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [draftKey]);

  const set = <K extends keyof UseInput>(k: K, v: UseInput[K]) => {
    dirty.current = true;
    setS((o) => {
      const next = { ...o, [k]: v };
      try {
        localStorage.setItem(draftKey, JSON.stringify({ at: Date.now(), data: next }));
      } catch {}
      return next;
    });
    // El error de un campo desaparece en cuanto se corrige.
    setErrors((e) => {
      if (!e[k as string]) return e;
      const { [k as string]: _, ...rest } = e;
      void _;
      return rest;
    });
  };
  const toggle = <T extends string>(list: T[], v: T) => (list.includes(v) ? list.filter((x) => x !== v) : [...list, v]);
  const contribs = s.contributions as ContribState[];
  const setContrib = (type: IncorporatedContributionCode, patch: Partial<ContribState>) =>
    set("contributions", contribs.map((c) => (c.type === type ? { ...c, ...patch } : c)));
  const toggleContrib = (type: IncorporatedContributionCode) =>
    set(
      "contributions",
      contribs.some((c) => c.type === type)
        ? contribs.filter((c) => c.type !== type)
        : [...contribs, { type, interventionAnswer: null, retainsRecognizableElements: null, interventionDescription: "" }].sort((a, b) => a.type.localeCompare(b.type)),
    );

  const tool = tools.find((t) => t.id === s.toolId);
  const preview = useMemo(
    () =>
      classifyAIUse(
        {
          ref: "preview",
          componentRef: s.componentId,
          toolIdentified: tool ? tool.identified : null,
          phases: s.phases,
          functions: s.functions,
          primaryFunction: s.primaryFunction,
          objective: s.objective,
          activityDescription: s.activityDescription,
          resultDescription: s.resultDescription,
          incorporation: s.incorporation,
          contributions: s.incorporation === "INCORPORATED" ? contribs.map((c, i) => ({ ref: `c${i}`, ...c })) : [],
          evidenceCount: props.evidenceCount,
        },
        props.declarationEvidenceCount,
      ),
    [s, tool, contribs, props.evidenceCount, props.declarationEvidenceCount],
  );

  function save(then: "list" | "stay") {
    setMsg(null);
    start(async () => {
      const res = await saveUseAction(versionId, useId, s);
      if (res.fieldErrors) {
        setErrors(res.fieldErrors);
        setMsg({ tone: "danger", text: "Revisa los campos marcados." });
        return;
      }
      if (res.error) {
        setMsg({ tone: "danger", text: res.error });
        return;
      }
      setErrors({});
      dirty.current = false;
      setRestored(false);
      try {
        localStorage.removeItem(draftKey);
      } catch {}
      if (then === "list") router.push(`/app/declarations/${versionId}/edit/uses?saved=1`);
      else {
        setMsg({ tone: "ok", text: "Uso guardado." });
        if (!useId && res.useId) router.replace(`/app/declarations/${versionId}/edit/uses/${res.useId}`);
        else router.refresh();
      }
    });
  }

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1.5fr_1fr]">
      <form
        className="space-y-8"
        onSubmit={(e) => {
          e.preventDefault();
          save("list");
        }}
        noValidate
      >
        {msg && <Alert tone={msg.tone}>{msg.text}</Alert>}
        {restored && (
          <div role="status" className="flex flex-wrap items-center gap-x-4 rounded-[var(--radius-md)] border border-petrol-200 bg-petrol-50 px-4 py-2 text-sm text-petrol-900">
            Hemos recuperado cambios que no llegaste a guardar.
            <button
              type="button"
              className="inline-flex min-h-11 items-center font-medium underline underline-offset-4"
              onClick={() => {
                try {
                  localStorage.removeItem(draftKey);
                } catch {}
                dirty.current = false;
                setS(initial);
                setRestored(false);
              }}
            >
              Descartar cambios recuperados
            </button>
          </div>
        )}

        <section className="space-y-4 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <h2 className="font-semibold">1. ¿Qué uso estás describiendo?</h2>
          <Field id="title" label="Título breve del uso" required error={errors.title} hint="Ej.: «Revisión crítica del ensayo», «Ilustración de cubierta», «Traducción al inglés».">
            <Input value={s.title} onChange={(e) => set("title", e.target.value)} maxLength={160} />
          </Field>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <Field id="componentId" label="Parte de la obra" hint={components.length ? undefined : "La obra no tiene componentes definidos."}>
              <Select value={s.componentId ?? ""} onChange={(e) => set("componentId", e.target.value || null)}>
                <option value="">La obra en general</option>
                {components.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field
              id="toolId"
              label="Herramienta utilizada"
              error={errors.toolId}
              hint={
                <>
                  ¿No aparece?{" "}
                  <Link href={`/app/declarations/${versionId}/edit/tools`} className="underline">
                    Registra la herramienta
                  </Link>{" "}
                  (también puede ser «desconocida»).
                </>
              }
            >
              <Select value={s.toolId ?? ""} onChange={(e) => set("toolId", e.target.value || null)}>
                <option value="">Selecciona…</option>
                {tools.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.identified ? t.name : `${t.name} (no identificada)`}
                  </option>
                ))}
              </Select>
            </Field>
          </div>
          {otherUses.length > 0 && (
            <Field id="parentUseId" label="¿Es un subuso de otro uso?" optional hint="Divide un uso en subusos cuando produjo resultados heterogéneos (por ejemplo, varias imágenes tratadas de forma distinta).">
              <Select value={s.parentUseId ?? ""} onChange={(e) => set("parentUseId", e.target.value || null)}>
                <option value="">No</option>
                {otherUses.map((u) => (
                  <option key={u.id} value={u.id}>
                    Subuso de: {u.title}
                  </option>
                ))}
              </Select>
            </Field>
          )}
        </section>

        <section className="space-y-4 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <h2 className="font-semibold">2. ¿En qué fases del proceso?</h2>
          <fieldset>
            <legend className="sr-only">Fases creativas</legend>
            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
              {CREATIVE_PHASES.map((p) => (
                <label key={p} className="flex min-h-11 items-start gap-3 rounded-md border border-line p-3 text-sm has-[:checked]:border-petrol-600 has-[:checked]:bg-petrol-50">
                  <input type="checkbox" checked={s.phases.includes(p)} onChange={() => set("phases", toggle(s.phases, p))} className="mt-1 h-4 w-4 accent-[var(--color-petrol-800)]" />
                  <span>
                    {PHASE_LABELS[p].es}
                    {phaseHints[p] && <span className="block text-xs text-muted">Ej.: {phaseHints[p]}</span>}
                  </span>
                </label>
              ))}
            </div>
          </fieldset>
        </section>

        <section className="space-y-4 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <h2 className="font-semibold">3. ¿Para qué utilizaste la IA? (dimensión A)</h2>
          <ChoiceGroup legend="Marca todas las que correspondan" error={errors.functions}>
            {FUNCTIONS.map((f) => (
              <Choice key={f.code} type="checkbox" name="functions" value={f.code} label={<><span className="font-mono text-xs text-muted">{f.code}</span> {f.label}</>} description={f.help} checked={s.functions.includes(f.code)} onChange={() => set("functions", toggle(s.functions, f.code))} />
            ))}
          </ChoiceGroup>
          {s.functions.length > 1 && (
            <ChoiceGroup legend="¿Alguna fue la función principal?" hint="Si no hay una principal clara, el uso se mostrará como multifunción. Nunca se elige automáticamente el número más alto.">
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                {s.functions.map((f) => (
                  <Choice key={f} name="primary" value={f} label={`${f} · ${getValue(f)?.label.es}`} checked={s.primaryFunction === f} onChange={() => set("primaryFunction", f)} />
                ))}
                <Choice name="primary" value="NONE" label="No hay una función principal" checked={s.primaryFunction === "NONE" || s.primaryFunction === null} onChange={() => set("primaryFunction", "NONE")} />
              </div>
            </ChoiceGroup>
          )}
        </section>

        <section className="space-y-4 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <h2 className="font-semibold">4. Describe la actividad</h2>
          <p className="text-xs text-muted">No es necesario copiar prompts completos ni conversaciones privadas. Describe lo esencial.</p>
          <Field id="objective" label="Objetivo" optional hint="Qué querías conseguir.">
            <Input value={s.objective} onChange={(e) => set("objective", e.target.value)} maxLength={2000} />
          </Field>
          <Field id="activityDescription" label="Qué hiciste con la IA" required error={errors.activityDescription}>
            <Textarea value={s.activityDescription} onChange={(e) => set("activityDescription", e.target.value)} rows={3} maxLength={4000} />
          </Field>
          <Field id="resultDescription" label="Qué resultado obtuviste" optional>
            <Textarea value={s.resultDescription} onChange={(e) => set("resultDescription", e.target.value)} rows={3} maxLength={4000} />
          </Field>
        </section>

        <section className="space-y-4 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <h2 className="font-semibold">5. ¿Se incorporó a la obra final algo procedente de este uso? (dimensión B)</h2>
          <ChoiceGroup legend="Incorporación a la obra" error={errors.incorporation}>
            <Choice name="incorporation" value="NONE" label="No, nada" description="Por ejemplo, leíste las sugerencias pero las descartaste todas." checked={s.incorporation === "NONE"} onChange={() => set("incorporation", "NONE")} />
            <Choice name="incorporation" value="INCORPORATED" label="Sí, algo se incorporó" checked={s.incorporation === "INCORPORATED"} onChange={() => set("incorporation", "INCORPORATED")} />
            <Choice name="incorporation" value="UNKNOWN" label="No puedo precisarlo" description="Se registrará como información insuficiente." checked={s.incorporation === "UNKNOWN"} onChange={() => set("incorporation", "UNKNOWN")} />
          </ChoiceGroup>

          {s.incorporation === "INCORPORATED" && (
            <>
              <ChoiceGroup legend="¿Qué tipo de aportación procedente de la IA está en la obra?" hint="Puedes marcar varias. Para cada una indicarás después qué hiciste con ella.">
                {CONTRIBUTIONS.map((c) => (
                  <Choice key={c.code} type="checkbox" name="contributions" value={c.code} label={<><span className="font-mono text-xs text-muted">{c.code}</span> {c.label}</>} description={c.help} checked={contribs.some((x) => x.type === c.code)} onChange={() => toggleContrib(c.code)} />
                ))}
              </ChoiceGroup>

              {contribs.map((c) => (
                <div key={c.type} className="space-y-4 rounded-md border border-petrol-200 bg-petrol-50/50 p-4">
                  <h3 className="text-sm font-semibold">
                    6. ¿Qué hiciste con la aportación «{CONTRIBUTIONS.find((x) => x.code === c.type)?.label.toLowerCase()}»? (dimensión C)
                  </h3>
                  <ChoiceGroup legend="Tu intervención" error={errors[`contribution.${c.type}`]}>
                    {INTERVENTIONS.map((i) => (
                      <Choice key={i.value} name={`intervention-${c.type}`} value={i.value} label={i.label} description={i.help} checked={c.interventionAnswer === i.value} onChange={() => setContrib(c.type, { interventionAnswer: i.value })} />
                    ))}
                  </ChoiceGroup>
                  {c.interventionAnswer === "TRANSFORMED" && (
                    <ChoiceGroup
                      legend="Si alguien comparase el resultado original de la IA con tu versión final, ¿reconocería elementos del original (composición, figuras, frases, melodía…)?"
                      hint="Esto distingue una transformación sustancial (C3) de una reelaboración creativa (C4). Ninguna implica más mérito que la otra."
                    >
                      <Choice name={`recog-${c.type}`} value="YES" label="Sí, se reconocen elementos del original" checked={c.retainsRecognizableElements === "YES"} onChange={() => setContrib(c.type, { retainsRecognizableElements: "YES" })} />
                      <Choice name={`recog-${c.type}`} value="NO" label="No, el resultado es una expresión considerablemente diferente" checked={c.retainsRecognizableElements === "NO"} onChange={() => setContrib(c.type, { retainsRecognizableElements: "NO" })} />
                      <Choice name={`recog-${c.type}`} value="UNSURE" label="No sabría decirlo" checked={c.retainsRecognizableElements === "UNSURE"} onChange={() => setContrib(c.type, { retainsRecognizableElements: "UNSURE" })} />
                    </ChoiceGroup>
                  )}
                  <Field id={`desc-${c.type}`} label="Describe tu intervención" hint="Qué decidiste, qué cambiaste o qué desarrollaste a partir de la aportación.">
                    <Textarea value={c.interventionDescription} onChange={(e) => setContrib(c.type, { interventionDescription: e.target.value })} rows={2} maxLength={3000} />
                  </Field>
                </div>
              ))}
            </>
          )}
        </section>

        <div className="sticky bottom-0 z-10 flex flex-wrap items-center gap-3 border-t border-line bg-paper py-3">
          <Link href={`/app/declarations/${versionId}/edit/uses`} className="mr-auto inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">
            ← Volver a la lista de usos
          </Link>
          <Button type="button" variant="secondary" aria-busy={pending || undefined} onClick={() => !pending && save("stay")}>
            {pending ? "Guardando…" : "Guardar y seguir editando"}
          </Button>
          <Button type="submit" aria-busy={pending || undefined}>
            Guardar uso
          </Button>
        </div>
      </form>

      <aside aria-label="Vista previa de la clasificación" className="lg:sticky lg:top-6 lg:self-start">
        <div className="rounded-[var(--radius-lg)] bg-petrol-800 p-5 text-white" aria-live="polite">
          <p className="text-xs tracking-wider text-petrol-200 uppercase">Clasificación de este uso</p>
          <p className="mt-2 font-mono text-2xl">{preview.code ?? "—"}</p>
          <p className="mt-1 font-mono text-xs break-words text-petrol-100">{preview.notation}</p>
          <ul className="mt-4 space-y-2 text-sm">
            <li>
              <span className="text-petrol-200">A · </span>
              {preview.functions.length ? preview.functions.map((f) => getValue(f)?.label.es).join("; ") : "Sin indicar"}
            </li>
            {preview.pairs.map((p, i) => (
              <li key={i}>
                <span className="text-petrol-200">B · </span>
                {getValue(p.b)?.label.es}
                <br />
                <span className="text-petrol-200">C · </span>
                {p.c ? getValue(p.c)?.label.es : "Sin precisar"}
              </li>
            ))}
            <li>
              <span className="text-petrol-200">D · </span>
              {getValue(preview.documentation)?.label.es}
            </li>
          </ul>
        </div>
        {preview.issues.length > 0 && (
          <ul className="mt-3 space-y-2">
            {preview.issues.map((i, k) => (
              <li key={k} className={`rounded-md p-3 text-xs leading-relaxed ${i.severity === "error" ? "bg-warn-soft text-[#5c3d00]" : "bg-petrol-50 text-petrol-900"}`}>
                {i.severity === "error" ? "Pendiente: " : "Aviso: "}
                {i.message}
              </li>
            ))}
          </ul>
        )}
        <p className="mt-3 text-xs leading-relaxed text-muted">
          La clasificación se calcula con reglas deterministas a partir de tus respuestas. No puedes editarla directamente: si no te parece correcta, revisa las respuestas.
        </p>
      </aside>
    </div>
  );
}
