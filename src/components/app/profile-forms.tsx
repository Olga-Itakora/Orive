"use client";
import { useActionState } from "react";
import { changePasswordAction } from "@/app/actions/auth";
import { requestErasureAction, updateProfileAction } from "@/app/actions/profile";
import { Alert } from "@/components/ui/alert";
import { Field, Input, Textarea } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import { FormBehavior } from "@/components/ui/form-behavior";

export function ProfileForm({ values }: { values: { displayName: string; artisticName: string; organization: string; bio: string; website: string } }) {
  const [state, action] = useActionState(updateProfileAction, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior draftKey="profile" okSignal={state.ok ? state : undefined} />
      {state.ok && <Alert tone="ok">{state.message}</Alert>}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field id="displayName" label="Nombre profesional" required error={fe.displayName}>
          <Input name="displayName" defaultValue={values.displayName} maxLength={120} />
        </Field>
        <Field id="artisticName" label="Nombre artístico" optional error={fe.artisticName}>
          <Input name="artisticName" defaultValue={values.artisticName} maxLength={120} />
        </Field>
        <Field id="organization" label="Institución u organización" optional error={fe.organization}>
          <Input name="organization" defaultValue={values.organization} maxLength={200} />
        </Field>
        <Field id="website" label="Web" optional error={fe.website}>
          <Input name="website" type="url" defaultValue={values.website} maxLength={500} />
        </Field>
      </div>
      <Field id="bio" label="Presentación breve" optional error={fe.bio}>
        <Textarea name="bio" defaultValue={values.bio} rows={3} maxLength={2000} />
      </Field>
      <SubmitButton>Guardar perfil</SubmitButton>
    </form>
  );
}

export function PasswordForm() {
  const [state, action] = useActionState(changePasswordAction, {});
  const fe = state.fieldErrors ?? {};
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior okSignal={state.ok ? state : undefined} resetOnOk />
      {state.ok && <Alert tone="ok">{state.message}</Alert>}
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      <Field id="current" label="Contraseña actual" required error={fe.current}>
        <Input name="current" type="password" autoComplete="current-password" />
      </Field>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field id="password" label="Nueva contraseña" required error={fe.password} hint="Al menos 10 caracteres.">
          <Input name="password" type="password" autoComplete="new-password" minLength={10} />
        </Field>
        <Field id="confirm" label="Repite la nueva contraseña" required error={fe.confirm}>
          <Input name="confirm" type="password" autoComplete="new-password" />
        </Field>
      </div>
      <SubmitButton variant="secondary">Cambiar contraseña</SubmitButton>
    </form>
  );
}

export function ErasureForm() {
  const [state, action] = useActionState(requestErasureAction, {});
  if (state.ok) return <Alert tone="ok">{state.message}</Alert>;
  return (
    <form action={action} className="space-y-3">
      <Field id="details" label="Detalles" optional>
        <Textarea name="details" rows={2} maxLength={2000} />
      </Field>
      <FormBehavior />
      <label className="flex min-h-11 items-start gap-2 text-sm">
        <input type="checkbox" name="confirm" className="mt-1 h-4 w-4" /> Quiero solicitar la supresión de mi cuenta y de mis datos personales.
      </label>
      <p data-field-error role="alert" className="text-sm text-danger" hidden={!state.fieldErrors?.confirm}>
        {state.fieldErrors?.confirm}
      </p>
      <SubmitButton variant="danger" size="sm">
        Solicitar la supresión de mi cuenta
      </SubmitButton>
    </form>
  );
}
