"use client";
import { useActionState } from "react";
import { FormBehavior } from "@/components/ui/form-behavior";
import { Alert } from "@/components/ui/alert";
import { Field, Input, Select } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import { COMPONENT_KIND_LABEL } from "@/lib/labels";
import type { ActionState } from "@/lib/validation/common";

export function AddComponentForm({ action }: { action: (s: ActionState, fd: FormData) => Promise<ActionState> }) {
  const [state, formAction] = useActionState(action, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={formAction} className="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_12rem_auto] sm:items-end" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} resetOnOk />
      {state.error && <Alert tone="danger" className="sm:col-span-3">{state.error}</Alert>}
      <Field id="comp-name" label="Nombre del componente" required error={fe.name} hint="Por ejemplo: «Texto», «Ilustraciones de cubierta», «Banda sonora».">
        <Input name="name" maxLength={120} />
      </Field>
      <Field id="comp-kind" label="Tipo" error={fe.kind}>
        <Select name="kind" defaultValue="TEXT">
          {Object.entries(COMPONENT_KIND_LABEL).map(([k, v]) => (
            <option key={k} value={k}>
              {v}
            </option>
          ))}
        </Select>
      </Field>
      <SubmitButton variant="secondary" className="sm:mb-[2.25rem]">
        Añadir componente
      </SubmitButton>
    </form>
  );
}

export function AddContributorForm({ action, components }: { action: (s: ActionState, fd: FormData) => Promise<ActionState>; components: { id: string; name: string }[] }) {
  const [state, formAction] = useActionState(action, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={formAction} className="space-y-3" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} resetOnOk />
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <Field id="contrib-name" label="Nombre" required error={fe.name} hint="Privado: los nombres de otros participantes nunca se publican ni se incluyen en la declaración.">
          <Input name="name" maxLength={120} />
        </Field>
        <Field id="contrib-role" label="Rol" required error={fe.role} hint="Por ejemplo: ilustrador/a, coautor/a.">
          <Input name="role" maxLength={120} />
        </Field>
      </div>
      {components.length > 0 && (
        <fieldset>
          <legend className="text-sm font-medium">Componentes en los que participa</legend>
          <div className="mt-2 flex flex-wrap gap-3">
            {components.map((c) => (
              <label key={c.id} className="inline-flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" name="componentIds" value={c.id} className="h-4 w-4 accent-[var(--color-petrol-800)]" /> {c.name}
              </label>
            ))}
          </div>
        </fieldset>
      )}
      <SubmitButton variant="secondary">Añadir participante</SubmitButton>
    </form>
  );
}
