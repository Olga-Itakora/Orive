"use client";
import { useActionState, useEffect, useState } from "react";
import { FormBehavior } from "@/components/ui/form-behavior";
import { Alert } from "@/components/ui/alert";
import { Field, Input, Select, Textarea } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import type { ActionState } from "@/lib/validation/common";

export interface WorkFormValues {
  title?: string;
  subtitle?: string | null;
  categoryKey?: string;
  description?: string | null;
  creationPeriod?: string | null;
  status?: string;
  publicationDate?: string | null;
  language?: string | null;
  workVersion?: string | null;
  publisher?: string | null;
  identifierType?: string | null;
  identifier?: string | null;
  url?: string | null;
  isCollective?: boolean;
}

export function WorkForm({
  action,
  categories,
  values = {},
  submitLabel,
  next,
  extraSubmit,
  draftKey,
}: {
  action: (s: ActionState, fd: FormData) => Promise<ActionState>;
  categories: { key: string; labelEs: string; group: string }[];
  values?: WorkFormValues;
  submitLabel: string;
  next?: string;
  extraSubmit?: { name: string; value: string; label: string };
  draftKey?: string;
}) {
  const [state, formAction] = useActionState(action, {});
  // Valores por defecto inferidos para obras nuevas: última modalidad usada e idioma del navegador.
  const [defaults, setDefaults] = useState<{ categoryKey?: string; language?: string }>({});
  useEffect(() => {
    if (values.title) return;
    try {
      const last = localStorage.getItem("oriva:last-category") ?? undefined;
      const lang = new Intl.DisplayNames(["es"], { type: "language" }).of(navigator.language.split("-")[0]);
      setDefaults({ categoryKey: last && categories.some((c) => c.key === last) ? last : undefined, language: lang ? lang.charAt(0).toUpperCase() + lang.slice(1) : undefined });
    } catch {
      /* sin almacenamiento */
    }
  }, [values.title, categories]);
  const fe = state.fieldErrors ?? {};
  const groups = [...new Set(categories.map((c) => c.group))];
  return (
    <form
      key={defaults.categoryKey ?? defaults.language ?? "form"}
      action={formAction}
      className="space-y-6"
      noValidate
      onSubmit={(e) => {
        const v = (e.currentTarget.elements.namedItem("categoryKey") as HTMLSelectElement | null)?.value;
        try {
          if (v) localStorage.setItem("oriva:last-category", v);
        } catch {}
      }}
    >
      <FormBehavior draftKey={draftKey} okSignal={state.ok ? state : undefined} />
      {next && <input type="hidden" name="next" value={next} />}
      {state.ok && state.message && <Alert tone="ok">{state.message}</Alert>}
      {Object.keys(fe).length > 0 && <Alert tone="danger">Revisa los campos marcados.</Alert>}
      <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
        <Field id="title" label="Título" required error={fe.title} className="md:col-span-2">
          <Input name="title" defaultValue={values.title ?? ""} maxLength={300} />
        </Field>
        <Field id="subtitle" label="Subtítulo" optional error={fe.subtitle} className="md:col-span-2">
          <Input name="subtitle" defaultValue={values.subtitle ?? ""} maxLength={300} />
        </Field>
        <Field id="categoryKey" label="Tipo de obra" required error={fe.categoryKey} hint="Si es una obra híbrida, elige la modalidad principal y describe sus partes como componentes.">
          <Select name="categoryKey" defaultValue={values.categoryKey ?? defaults.categoryKey ?? ""}>
            <option value="" disabled>
              Selecciona…
            </option>
            {groups.map((g) => (
              <optgroup key={g} label={g}>
                {categories
                  .filter((c) => c.group === g)
                  .map((c) => (
                    <option key={c.key} value={c.key}>
                      {c.labelEs}
                    </option>
                  ))}
              </optgroup>
            ))}
          </Select>
        </Field>
        <Field id="status" label="Estado de la obra" error={fe.status}>
          <Select name="status" defaultValue={values.status ?? "IN_PROGRESS"}>
            <option value="IN_PROGRESS">En proceso</option>
            <option value="FINISHED">Terminada (inédita)</option>
            <option value="PUBLISHED">Publicada</option>
          </Select>
        </Field>
        <Field id="description" label="Descripción" optional error={fe.description} className="md:col-span-2" hint="Breve descripción de la obra. Evita incluir datos personales de terceros.">
          <Textarea name="description" defaultValue={values.description ?? ""} rows={3} maxLength={4000} />
        </Field>
        <Field id="creationPeriod" label="Fecha o periodo de creación" optional error={fe.creationPeriod} hint="Por ejemplo: «2024–2026» o «primavera de 2025».">
          <Input name="creationPeriod" defaultValue={values.creationPeriod ?? ""} maxLength={120} />
        </Field>
        <Field id="publicationDate" label="Fecha de publicación" optional error={fe.publicationDate}>
          <Input type="date" name="publicationDate" defaultValue={values.publicationDate ?? ""} />
        </Field>
        <Field id="language" label="Idioma" optional error={fe.language}>
          <Input name="language" defaultValue={values.language ?? defaults.language ?? ""} maxLength={60} />
        </Field>
        <Field id="workVersion" label="Versión de la obra" optional error={fe.workVersion} hint="Por ejemplo: «2.ª edición» o «v1.3».">
          <Input name="workVersion" defaultValue={values.workVersion ?? ""} maxLength={60} />
        </Field>
        <Field id="publisher" label="Editorial o institución" optional error={fe.publisher}>
          <Input name="publisher" defaultValue={values.publisher ?? ""} maxLength={200} />
        </Field>
        <div className="grid grid-cols-[8rem_1fr] gap-3">
          <Field id="identifierType" label="Tipo" optional error={fe.identifierType}>
            <Select name="identifierType" defaultValue={values.identifierType ?? ""}>
              <option value="">—</option>
              <option value="ISBN">ISBN</option>
              <option value="DOI">DOI</option>
              <option value="ISSN">ISSN</option>
              <option value="ISRC">ISRC</option>
              <option value="ISAN">ISAN</option>
              <option value="Otro">Otro</option>
            </Select>
          </Field>
          <Field id="identifier" label="Identificador" optional error={fe.identifier}>
            <Input name="identifier" defaultValue={values.identifier ?? ""} maxLength={120} />
          </Field>
        </div>
        <Field id="url" label="URL de publicación" optional error={fe.url} className="md:col-span-2">
          <Input type="url" name="url" defaultValue={values.url ?? ""} maxLength={500} placeholder="https://editorial.example/obra" />
        </Field>
        <label className="flex min-h-11 items-start gap-3 text-sm md:col-span-2">
          <input type="checkbox" name="isCollective" defaultChecked={values.isCollective} className="mt-1 h-4 w-4 accent-[var(--color-petrol-800)]" />
          <span>
            <span className="font-medium">Es una obra colectiva o con varios participantes</span>
            <span className="block text-xs text-muted">Tu declaración solo cubrirá lo que tú conoces y declaras; no se atribuirá a los demás participantes.</span>
          </span>
        </label>
      </div>
      <div className="sticky bottom-0 z-10 -mx-5 flex flex-wrap gap-3 border-t border-line bg-surface px-5 py-3">
        <SubmitButton>{submitLabel}</SubmitButton>
        {extraSubmit && (
          <SubmitButton variant="secondary" name={extraSubmit.name} value={extraSubmit.value}>
            {extraSubmit.label}
          </SubmitButton>
        )}
      </div>
    </form>
  );
}
