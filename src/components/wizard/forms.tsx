"use client";
import { useActionState, useState } from "react";
import { usePathname } from "next/navigation";
import { FormBehavior } from "@/components/ui/form-behavior";
import { Alert } from "@/components/ui/alert";
import { Choice, ChoiceGroup, Field, Input, Select, Textarea } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import { ROLE_SUGGESTIONS, SYSTEM_TYPES } from "@/lib/labels";
import type { ActionState } from "@/lib/validation/common";

type Act = (s: ActionState, fd: FormData) => Promise<ActionState>;

/** Clave de borrador local por página y formulario. */
function useDraftKey(name: string) {
  return `${usePathname()}#${name}`;
}

function FormErrors({ state }: { state: ActionState }) {
  if (state.error) return <Alert tone="danger">{state.error}</Alert>;
  if (state.fieldErrors && Object.keys(state.fieldErrors).length) return <Alert tone="danger">Revisa los campos marcados.</Alert>;
  return null;
}

function NavButtons({ back, stayLabel = "Guardar borrador" }: { back?: string; stayLabel?: string }) {
  return (
    <div className="sticky bottom-0 z-10 -mx-5 flex flex-wrap items-center gap-3 border-t border-line bg-surface px-5 py-3">
      {back && (
        <a href={back} className="mr-auto inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4 hover:text-petrol-800">
          ← Paso anterior
        </a>
      )}
      <SubmitButton variant="secondary" name="intent" value="stay">
        {stayLabel}
      </SubmitButton>
      <SubmitButton name="intent" value="next">
        Guardar y continuar
      </SubmitButton>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Declarante y alcance
// ---------------------------------------------------------------------------

export function DeclarantForm({
  action,
  values,
  email,
  components,
  back,
  isCollective,
}: {
  action: Act;
  values: { declarantName: string; declarantArtisticName: string; declarantOrganization: string; declarantRole: string; scope: string; scopeComponentIds: string[]; responsibilityNote: string };
  email: string;
  components: { id: string; name: string }[];
  back: string;
  isCollective: boolean;
}) {
  const [state, formAction] = useActionState(action, {});
  const [scope, setScope] = useState(values.scope);
  const fe = state.fieldErrors ?? {};
  const draftKey = useDraftKey("declarant");
  return (
    <form action={formAction} className="space-y-6" noValidate>
      <FormBehavior draftKey={draftKey} />
      <FormErrors state={state} />
      <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
        <Field id="declarantName" label="Nombre" required error={fe.declarantName} hint="Tu nombre profesional. En la publicación podrás decidir si se muestra.">
          <Input name="declarantName" defaultValue={values.declarantName} maxLength={160} autoComplete="name" />
        </Field>
        <Field id="declarantArtisticName" label="Nombre artístico" optional error={fe.declarantArtisticName}>
          <Input name="declarantArtisticName" defaultValue={values.declarantArtisticName} maxLength={160} />
        </Field>
        <Field id="email" label="Correo electrónico (privado)" hint="Procede de tu cuenta. Nunca se incluye en la declaración ni se publica.">
          <Input value={email} readOnly disabled />
        </Field>
        <Field id="declarantOrganization" label="Institución u organización" optional error={fe.declarantOrganization}>
          <Input name="declarantOrganization" defaultValue={values.declarantOrganization} maxLength={200} />
        </Field>
        <Field id="declarantRole" label="Tu rol en la obra" required error={fe.declarantRole} hint="Por ejemplo: autor/a, ilustrador/a, traductor/a.">
          <Input name="declarantRole" defaultValue={values.declarantRole} maxLength={120} list="role-suggestions" />
        </Field>
        <datalist id="role-suggestions">
          {ROLE_SUGGESTIONS.map((r) => (
            <option key={r} value={r} />
          ))}
        </datalist>
      </div>

      <ChoiceGroup legend="¿Qué cubre esta declaración?" error={fe.scope} hint={isCollective ? "En una obra colectiva, declara únicamente lo que conoces. Tu declaración no se atribuirá a los demás participantes." : undefined}>
        <Choice name="scope" value="WHOLE_WORK" label="La obra completa" description="Conozco el proceso de toda la obra y asumo la responsabilidad de describirlo." checked={scope === "WHOLE_WORK"} onChange={() => setScope("WHOLE_WORK")} />
        <Choice name="scope" value="COMPONENTS" label="Componentes concretos" description="Por ejemplo, solo el texto de un libro ilustrado." checked={scope === "COMPONENTS"} onChange={() => setScope("COMPONENTS")} disabled={components.length === 0} />
        <Choice name="scope" value="OWN_CONTRIBUTION" label="Solo mi contribución" description="Declaro únicamente sobre lo que yo hice dentro de una obra con más participantes." checked={scope === "OWN_CONTRIBUTION"} onChange={() => setScope("OWN_CONTRIBUTION")} />
      </ChoiceGroup>
      {components.length === 0 && <p className="-mt-3 text-xs text-muted">Para declarar sobre componentes concretos, añádelos primero en la ficha de la obra.</p>}

      {(scope === "COMPONENTS" || scope === "OWN_CONTRIBUTION") && components.length > 0 && (
        <fieldset>
          <legend className="text-sm font-medium">{scope === "COMPONENTS" ? "Componentes cubiertos" : "Componentes en los que contribuyes (opcional)"}</legend>
          <div className="mt-2 flex flex-wrap gap-3">
            {components.map((c) => (
              <label key={c.id} className="inline-flex items-center gap-2 rounded-md border border-line bg-surface px-3 py-2 text-sm has-[:checked]:border-petrol-600 has-[:checked]:bg-petrol-50">
                <input type="checkbox" name="scopeComponentIds" value={c.id} defaultChecked={values.scopeComponentIds.includes(c.id)} className="h-4 w-4 accent-[var(--color-petrol-800)]" /> {c.name}
              </label>
            ))}
          </div>
          {fe.scopeComponentIds && (
            <p role="alert" className="mt-1 text-xs font-medium text-danger">
              {fe.scopeComponentIds}
            </p>
          )}
        </fieldset>
      )}

      <Field id="responsibilityNote" label="Ámbito de responsabilidad" optional error={fe.responsibilityNote} hint="Precisa, si lo necesitas, qué decisiones creativas y editoriales finales asumes y cuáles no.">
        <Textarea name="responsibilityNote" defaultValue={values.responsibilityNote} rows={3} maxLength={2000} />
      </Field>
      <NavButtons back={back} />
    </form>
  );
}

// ---------------------------------------------------------------------------
// Utilización general de IA
// ---------------------------------------------------------------------------

export function UsageForm({ action, values, back }: { action: Act; values: { aiUsage: string; ownContributionAiUsage: string; uncertaintyNote: string; processNarrative: string }; back: string }) {
  const [state, formAction] = useActionState(action, {});
  const [usage, setUsage] = useState(values.aiUsage);
  const [own, setOwn] = useState(values.ownContributionAiUsage);
  const fe = state.fieldErrors ?? {};
  const eff = usage === "OWN_CONTRIBUTION_ONLY" ? own : usage;
  const draftKey = useDraftKey("usage");
  return (
    <form action={formAction} className="space-y-6" noValidate>
      <FormBehavior draftKey={draftKey} />
      <FormErrors state={state} />
      <ChoiceGroup legend="¿Se han utilizado sistemas de inteligencia artificial durante la elaboración de esta obra o de la parte que estás declarando?" error={fe.aiUsage}>
        <Choice name="aiUsage" value="NO" label="No" description="No se utilizaron sistemas de IA en el ámbito que declaro." checked={usage === "NO"} onChange={() => setUsage("NO")} />
        <Choice name="aiUsage" value="YES" label="Sí" description="Después describirás cada uso concreto." checked={usage === "YES"} onChange={() => setUsage("YES")} />
        <Choice name="aiUsage" value="UNCERTAIN" label="No puedo determinarlo con certeza" description="Por ejemplo, usaste programas que pueden incorporar funciones de IA sin saber si intervinieron." checked={usage === "UNCERTAIN"} onChange={() => setUsage("UNCERTAIN")} />
        <Choice name="aiUsage" value="OWN_CONTRIBUTION_ONLY" label="Solo puedo declarar sobre mi contribución" description="No conozco el proceso de las demás partes de la obra." checked={usage === "OWN_CONTRIBUTION_ONLY"} onChange={() => setUsage("OWN_CONTRIBUTION_ONLY")} />
      </ChoiceGroup>

      {usage === "OWN_CONTRIBUTION_ONLY" && (
        <ChoiceGroup legend="En tu contribución, ¿se utilizaron sistemas de IA?" error={fe.ownContributionAiUsage}>
          <Choice name="ownContributionAiUsage" value="NO" label="No" checked={own === "NO"} onChange={() => setOwn("NO")} />
          <Choice name="ownContributionAiUsage" value="YES" label="Sí" checked={own === "YES"} onChange={() => setOwn("YES")} />
          <Choice name="ownContributionAiUsage" value="UNCERTAIN" label="No puedo determinarlo con certeza" checked={own === "UNCERTAIN"} onChange={() => setOwn("UNCERTAIN")} />
        </ChoiceGroup>
      )}

      {(eff === "UNCERTAIN" || usage === "OWN_CONTRIBUTION_ONLY" || eff === "YES") && (
        <Field
          id="uncertaintyNote"
          label="Incertidumbres o limitaciones"
          optional={eff !== "UNCERTAIN"}
          error={fe.uncertaintyNote}
          hint="Explica lo que no puedes precisar. Por ejemplo: «Usé un editor de fotos que puede aplicar ajustes automáticos con IA; no sé si intervinieron». No se inventará ningún dato."
        >
          <Textarea name="uncertaintyNote" defaultValue={values.uncertaintyNote} rows={3} maxLength={2000} />
        </Field>
      )}

      {(eff === "NO" || eff === "UNCERTAIN") && (
        <Field
          id="processNarrative"
          label="Describe tu proceso creativo"
          optional
          error={fe.processNarrative}
          hint="Opcional. Una descripción de al menos 100 caracteres documenta el proceso (dimensión D2). No incluyas datos personales de terceros ni información confidencial."
        >
          <Textarea name="processNarrative" defaultValue={values.processNarrative} rows={5} maxLength={6000} />
        </Field>
      )}
      <NavButtons back={back} stayLabel="Guardar" />
    </form>
  );
}

// ---------------------------------------------------------------------------
// Herramientas
// ---------------------------------------------------------------------------

export interface ToolValues {
  identified: boolean;
  name: string;
  provider: string;
  modelVersion: string;
  systemType: string;
  purpose: string;
  usagePeriod: string;
}

export function ToolForm({ action, values, submitLabel, onDone }: { action: Act; values?: ToolValues; submitLabel: string; onDone?: () => void }) {
  const [state, formAction] = useActionState(action, {});
  const [identified, setIdentified] = useState(values?.identified ?? true);
  const [lastOk, setLastOk] = useState<ActionState | null>(null);
  if (state.ok && state !== lastOk) {
    setLastOk(state);
    if (!values) setIdentified(true);
    onDone?.();
  }
  const fe = state.fieldErrors ?? {};
  const p = values ? "edit" : "new";
  return (
    <form action={formAction} className="space-y-4" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} resetOnOk={!values} />
      <FormErrors state={state} />
      <ChoiceGroup legend="¿Sabes qué herramienta era?">
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <Choice name="identified" value="yes" label="Sí, conozco su nombre" checked={identified} onChange={() => setIdentified(true)} />
          <Choice name="identified" value="no" label="No lo recuerdo / no lo sé" checked={!identified} onChange={() => setIdentified(false)} />
        </div>
      </ChoiceGroup>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Field id={`${p}-name`} label={identified ? "Nombre de la herramienta" : "Descripción aproximada"} optional={!identified} error={fe.name} hint={identified ? "Por ejemplo: ChatGPT, Claude, Midjourney, DeepL…" : "Por ejemplo: «un traductor en línea»."}>
          <Input name="name" defaultValue={values && values.identified ? values.name : ""} maxLength={160} />
        </Field>
        <Field id={`${p}-provider`} label="Proveedor" optional error={fe.provider}>
          <Input name="provider" defaultValue={values?.provider} maxLength={160} />
        </Field>
        <Field id={`${p}-modelVersion`} label="Modelo o versión" optional error={fe.modelVersion} hint="Solo si lo conoces.">
          <Input name="modelVersion" defaultValue={values?.modelVersion} maxLength={160} />
        </Field>
        <Field id={`${p}-systemType`} label="Tipo de sistema" optional error={fe.systemType}>
          <Select name="systemType" defaultValue={values?.systemType ?? ""}>
            <option value="">—</option>
            {SYSTEM_TYPES.map((t) => (
              <option key={t} value={t}>
                {t}
              </option>
            ))}
          </Select>
        </Field>
        <Field id={`${p}-purpose`} label="Finalidad" optional error={fe.purpose} className="md:col-span-2" hint="Para qué la utilizaste, en general.">
          <Input name="purpose" defaultValue={values?.purpose} maxLength={1000} />
        </Field>
        <Field id={`${p}-usagePeriod`} label="Periodo aproximado de utilización" optional error={fe.usagePeriod}>
          <Input name="usagePeriod" defaultValue={values?.usagePeriod} maxLength={120} placeholder="marzo–junio de 2026" />
        </Field>
      </div>
      <SubmitButton variant={values ? "primary" : "secondary"}>{submitLabel}</SubmitButton>
      {state.ok && state.message && !values && <p className="text-sm text-ok" role="status">{state.message}</p>}
    </form>
  );
}

export function EditableTool({ action, values, children }: { action: Act; values: ToolValues; children: React.ReactNode }) {
  const [editing, setEditing] = useState(false);
  return (
    <div>
      {children}
      <button type="button" onClick={() => setEditing((e) => !e)} aria-expanded={editing} className="inline-flex min-h-11 items-center text-sm text-petrol-700 underline underline-offset-4">
        {editing ? "Cerrar edición" : "Editar herramienta"}
      </button>
      {editing && (
        <div className="mt-3 rounded-md border border-line bg-paper p-4">
          <ToolForm action={action} values={values} submitLabel="Guardar herramienta" onDone={() => setEditing(false)} />
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Evidencias
// ---------------------------------------------------------------------------

export function EvidenceForm({ action, uses }: { action: Act; uses: { id: string; title: string }[] }) {
  const [state, formAction] = useActionState(action, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={formAction} className="space-y-4" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} resetOnOk />
      <FormErrors state={state} />
      {state.ok && state.message && <Alert tone="ok">{state.message}</Alert>}
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Field id="ev-kind" label="Tipo de evidencia" required error={fe.kind}>
          <Select name="kind" defaultValue="VERSION_HISTORY">
            <option value="VERSION_HISTORY">Historial de versiones</option>
            <option value="CONVERSATION_LOG">Registro de conversación con la IA</option>
            <option value="WORK_MATERIAL">Material de trabajo (bocetos, notas…)</option>
            <option value="SCREENSHOT">Captura de pantalla</option>
            <option value="OTHER">Otra</option>
          </Select>
        </Field>
        <Field id="ev-use" label="Relacionada con" optional>
          <Select name="useId" defaultValue="">
            <option value="">Toda la declaración</option>
            {uses.map((u) => (
              <option key={u.id} value={u.id}>
                {u.title}
              </option>
            ))}
          </Select>
        </Field>
        <Field id="ev-description" label="Descripción" required error={fe.description} className="md:col-span-2" hint="Qué contiene y qué muestra. Las evidencias son privadas por defecto.">
          <Textarea name="description" rows={2} maxLength={1000} />
        </Field>
        <Field id="ev-file" label="Fichero" optional error={fe.file} className="md:col-span-2" hint="PDF, PNG, JPEG, WebP o texto. Máximo 10 MB. Se guarda de forma privada y se registra su huella SHA-256.">
          <Input type="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.md,.csv,.json" className="py-2" />
        </Field>
      </div>
      <SubmitButton variant="secondary" pendingText="Subiendo…">
        Registrar evidencia
      </SubmitButton>
    </form>
  );
}

// ---------------------------------------------------------------------------
// Artículo 50
// ---------------------------------------------------------------------------

type A50 = Record<string, string | null>;

function Tri({ name, legend, hint, value, onChange }: { name: string; legend: string; hint?: string; value: string | null; onChange: (v: string) => void }) {
  return (
    <ChoiceGroup legend={legend} hint={hint}>
      <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
        {[
          ["YES", "Sí"],
          ["NO", "No"],
          ["UNSURE", "No lo sé"],
        ].map(([v, l]) => (
          <Choice key={v} name={name} value={v} label={l} checked={value === v} onChange={() => onChange(v)} />
        ))}
      </div>
    </ChoiceGroup>
  );
}

export function Article50Form({ action, values, back, showText }: { action: Act; values: A50; back: string; showText: boolean }) {
  const [state, formAction] = useActionState(action, {});
  const [v, setV] = useState<A50>(values);
  const set = (k: string) => (val: string) => setV((o) => ({ ...o, [k]: val }));
  const draftKey = useDraftKey("article50");
  return (
    <form action={formAction} className="space-y-6" noValidate>
      <FormBehavior draftKey={draftKey} />
      <FormErrors state={state} />
      <ChoiceGroup legend="¿Cuál es tu papel respecto de los sistemas de IA utilizados?" hint="Quien utiliza un sistema de IA bajo su propia autoridad puede ser «responsable del despliegue» a efectos del Reglamento.">
        <Choice name="role" value="OWN_AUTHORITY" label="Los utilicé por mi cuenta, bajo mi propia autoridad" checked={v.role === "OWN_AUTHORITY"} onChange={() => set("role")("OWN_AUTHORITY")} />
        <Choice name="role" value="FOR_ORGANIZATION" label="Los utilicé por cuenta de una organización (editorial, empresa, institución…)" checked={v.role === "FOR_ORGANIZATION"} onChange={() => set("role")("FOR_ORGANIZATION")} />
        <Choice name="role" value="PROVIDER" label="Soy proveedor de un sistema de IA (lo desarrollo o comercializo)" checked={v.role === "PROVIDER"} onChange={() => set("role")("PROVIDER")} />
        <Choice name="role" value="UNSURE" label="No lo sé" checked={v.role === "UNSURE"} onChange={() => set("role")("UNSURE")} />
      </ChoiceGroup>
      {v.role === "FOR_ORGANIZATION" && (
        <Field id="organizationName" label="Organización bajo cuya autoridad se utilizó" optional>
          <Input name="organizationName" defaultValue={v.organizationName ?? ""} maxLength={200} />
        </Field>
      )}
      <ChoiceGroup legend="¿En qué contexto se realizó la actividad?">
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
          <Choice name="context" value="PROFESSIONAL" label="Profesional" description="Incluye publicación comercial, encargos, docencia o investigación." checked={v.context === "PROFESSIONAL"} onChange={() => set("context")("PROFESSIONAL")} />
          <Choice name="context" value="PERSONAL_NON_PROFESSIONAL" label="Personal no profesional" checked={v.context === "PERSONAL_NON_PROFESSIONAL"} onChange={() => set("context")("PERSONAL_NON_PROFESSIONAL")} />
          <Choice name="context" value="UNSURE" label="No lo sé" checked={v.context === "UNSURE"} onChange={() => set("context")("UNSURE")} />
        </div>
      </ChoiceGroup>
      <Tri name="syntheticMedia" legend="¿El contenido publicado incluye imágenes, vídeo o audio generados o manipulados con IA?" value={v.syntheticMedia} onChange={set("syntheticMedia")} />
      {v.syntheticMedia === "YES" && (
        <>
          <Tri
            name="realisticResemblance"
            legend="¿Ese contenido se asemeja a personas, objetos, lugares, entidades o sucesos reales y podría hacer pensar erróneamente que es auténtico?"
            hint="No toda imagen generada con IA es una ultrasuplantación (deep fake). Una ilustración claramente fantástica normalmente no lo es."
            value={v.realisticResemblance}
            onChange={set("realisticResemblance")}
          />
          <Tri name="evidentlyCreative" legend="¿Forma parte de una obra manifiestamente artística, creativa, satírica o de ficción?" value={v.evidentlyCreative} onChange={set("evidentlyCreative")} />
        </>
      )}
      {showText && (
        <>
          <Tri name="publicInterestText" legend="¿Se publica texto generado o manipulado con IA con el fin de informar al público sobre asuntos de interés público?" hint="Por ejemplo, artículos periodísticos o informativos. Una novela o un poema normalmente no lo son." value={v.publicInterestText} onChange={set("publicInterestText")} />
          {v.publicInterestText === "YES" && (
            <>
              <Tri name="humanReview" legend="¿Ese texto ha sido sometido a revisión humana o control editorial?" value={v.humanReview} onChange={set("humanReview")} />
              <Tri name="editorialResponsibility" legend="¿Una persona física o jurídica asume la responsabilidad editorial de la publicación?" value={v.editorialResponsibility} onChange={set("editorialResponsibility")} />
            </>
          )}
        </>
      )}
      <NavButtons back={back} stayLabel="Guardar y ver orientación" />
    </form>
  );
}

// ---------------------------------------------------------------------------
// Revisión: observaciones y emisión
// ---------------------------------------------------------------------------

export function ObservationsForm({ action, value }: { action: Act; value: string }) {
  const [state, formAction] = useActionState(action, {});
  return (
    <form action={formAction} className="space-y-3" noValidate>
      <FormBehavior draftKey={useDraftKey("observations")} okSignal={state.ok ? state : undefined} />
      <Field id="observations" label="Observaciones relevantes" optional error={state.fieldErrors?.[""] ?? null} hint="Aparecerán en la declaración completa.">
        <Textarea name="observations" defaultValue={value} rows={3} maxLength={4000} />
      </Field>
      <div className="flex items-center gap-3">
        <SubmitButton variant="secondary" size="sm">
          Guardar observaciones
        </SubmitButton>
        {state.ok && <span className="text-sm text-ok" role="status">{state.message}</span>}
      </div>
    </form>
  );
}

export function IssueForm({ action, statement, statementHash, statementVersion }: { action: Act; statement: string; statementHash: string; statementVersion: string }) {
  const [state, formAction] = useActionState(action, {});
  return (
    <form action={formAction} className="space-y-4">
      <FormErrors state={state} />
      <input type="hidden" name="statementHash" value={statementHash} />
      <blockquote className="rounded-md border-l-4 border-accent bg-paper p-4 text-sm leading-relaxed whitespace-pre-line text-ink">{statement}</blockquote>
      <p className="text-xs text-muted">
        Texto de la declaración responsable, versión {statementVersion}. Se registrará la versión exacta que aceptas.
      </p>
      <label className="flex min-h-11 items-start gap-3 rounded-md border border-line bg-surface p-3 text-sm">
        <input type="checkbox" name="accept" className="mt-1 h-4 w-4 accent-[var(--color-petrol-800)]" aria-describedby={state.fieldErrors?.accept ? "accept-error" : undefined} />
        <span>He leído y acepto expresamente la declaración responsable anterior. Entiendo que, una vez emitida, esta versión no podrá modificarse; podré crear una nueva versión o revocarla.</span>
      </label>
      {state.fieldErrors?.accept && (
        <p id="accept-error" role="alert" className="text-xs font-medium text-danger">
          {state.fieldErrors.accept}
        </p>
      )}
      <SubmitButton size="lg" pendingText="Emitiendo declaración…">
        Emitir declaración
      </SubmitButton>
      <p className="text-xs text-muted">Emitir no publica nada: la declaración seguirá siendo privada hasta que decidas publicarla.</p>
    </form>
  );
}
