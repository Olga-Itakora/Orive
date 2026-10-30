"use client";
import { useActionState } from "react";
import { contactAction } from "@/app/actions/contact";
import { Alert } from "@/components/ui/alert";
import { Field, Input, Select, Textarea } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import { FormBehavior } from "@/components/ui/form-behavior";

export function ContactForm({ defaultEmail }: { defaultEmail?: string }) {
  const [state, action] = useActionState(contactAction, {});
  if (state.ok) return <Alert tone="ok">{state.message}</Alert>;
  const fe = state.fieldErrors ?? {};
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior draftKey="contact" />
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      <Field id="type" label="Motivo" required error={fe.type}>
        <Select name="type" defaultValue="CONTACT">
          <optgroup label="General">
            <option value="CONTACT">Consulta general</option>
            <option value="TECHNICAL">Problema técnico</option>
            <option value="CONTENT_REPORT">Comunicar una declaración pública</option>
          </optgroup>
          <optgroup label="Protección de datos (RGPD)">
            <option value="DATA_ACCESS">Derecho de acceso</option>
            <option value="DATA_RECTIFICATION">Derecho de rectificación</option>
            <option value="DATA_ERASURE">Derecho de supresión</option>
            <option value="DATA_OBJECTION">Derecho de oposición</option>
            <option value="DATA_RESTRICTION">Derecho de limitación</option>
            <option value="DATA_PORTABILITY">Derecho de portabilidad</option>
          </optgroup>
        </Select>
      </Field>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field id="name" label="Nombre" optional error={fe.name}>
          <Input name="name" maxLength={120} autoComplete="name" />
        </Field>
        <Field id="email" label="Correo electrónico" required error={fe.email}>
          <Input name="email" type="email" defaultValue={defaultEmail} autoComplete="email" />
        </Field>
      </div>
      <Field id="publicId" label="Identificador de declaración" optional error={fe.publicId} hint="Si tu mensaje se refiere a una declaración concreta.">
        <Input name="publicId" maxLength={30} placeholder="ORV-7F29-C4A8-K2M3" className="font-mono" />
      </Field>
      <Field id="message" label="Mensaje" required error={fe.message}>
        <Textarea name="message" rows={6} maxLength={5000} minLength={10} />
      </Field>
      <div className="hidden" aria-hidden>
        <label>
          No rellenar <input name="website" tabIndex={-1} autoComplete="off" />
        </label>
      </div>
      <SubmitButton pendingText="Enviando…">Enviar mensaje</SubmitButton>
    </form>
  );
}
