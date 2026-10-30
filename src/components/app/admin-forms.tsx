"use client";
import { useActionState } from "react";
import { Alert } from "@/components/ui/alert";
import { Field, Input, Select, Textarea } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import { FormBehavior } from "@/components/ui/form-behavior";
import type { ActionState } from "@/lib/validation/common";

type Act = (s: ActionState, fd: FormData) => Promise<ActionState>;

export function CategoryForm({ action }: { action: Act }) {
  const [state, a] = useActionState(action, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={a} className="grid grid-cols-1 gap-3 sm:grid-cols-5 sm:items-end" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} resetOnOk />
      {state.ok && <Alert tone="ok" className="sm:col-span-5">{state.message}</Alert>}
      <Field id="key" label="Clave" error={fe.key}><Input name="key" /></Field>
      <Field id="labelEs" label="Etiqueta (es)" error={fe.labelEs}><Input name="labelEs" /></Field>
      <Field id="labelEn" label="Etiqueta (en)" error={fe.labelEn}><Input name="labelEn" /></Field>
      <Field id="group" label="Grupo" error={fe.group}><Input name="group" /></Field>
      <SubmitButton variant="secondary">Añadir modalidad</SubmitButton>
    </form>
  );
}

export function LegalTextForm({ action }: { action: Act }) {
  const [state, a] = useActionState(action, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={a} className="space-y-3" noValidate>
      <FormBehavior draftKey="admin-legal-text" okSignal={state.ok ? state : undefined} resetOnOk />
      {state.ok && <Alert tone="ok">{state.message}</Alert>}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Field id="lt-key" label="Texto" error={fe.key}>
          <Select name="key" defaultValue="declaration_statement">
            <option value="declaration_statement">Declaración responsable</option>
            <option value="privacy">Privacidad</option>
            <option value="terms">Condiciones de uso</option>
            <option value="legal_notice">Aviso legal</option>
          </Select>
        </Field>
        <Field id="lt-version" label="Versión" error={fe.version}><Input name="version" placeholder="1.1" /></Field>
        <Field id="lt-title" label="Título" error={fe.title}><Input name="title" /></Field>
      </div>
      <Field id="lt-content" label="Contenido" error={fe.content}><Textarea name="content" rows={10} /></Field>
      <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="pendingLegalReview" defaultChecked className="h-4 w-4" /> Pendiente de revisión jurídica</label>
      <SubmitButton variant="secondary">Crear versión (borrador)</SubmitButton>
    </form>
  );
}

export function LegalReferenceForm({ action, values }: { action: Act; values: { title: string; citation: string; url: string; summary: string; verificationNote: string; active: boolean } }) {
  const [state, a] = useActionState(action, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={a} className="space-y-3" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} />
      {state.ok && <Alert tone="ok">{state.message}</Alert>}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <Field id="r-title" label="Título" error={fe.title}><Input name="title" defaultValue={values.title} /></Field>
        <Field id="r-citation" label="Cita" error={fe.citation}><Input name="citation" defaultValue={values.citation} /></Field>
      </div>
      <Field id="r-url" label="URL oficial" error={fe.url}><Input name="url" defaultValue={values.url} /></Field>
      <Field id="r-summary" label="Resumen" error={fe.summary}><Textarea name="summary" defaultValue={values.summary} rows={3} /></Field>
      <Field id="r-note" label="Nota de verificación" error={fe.verificationNote}><Input name="verificationNote" defaultValue={values.verificationNote} /></Field>
      <label className="flex items-center gap-2 text-sm"><input type="checkbox" name="active" defaultChecked={values.active} className="h-4 w-4" /> Activa</label>
      <SubmitButton size="sm" variant="secondary">Guardar referencia</SubmitButton>
    </form>
  );
}
