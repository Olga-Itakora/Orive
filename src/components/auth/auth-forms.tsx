"use client";
import { useActionState } from "react";
import Link from "next/link";
import { forgotPasswordAction, loginAction, registerAction, resetPasswordAction } from "@/app/actions/auth";
import { Alert } from "@/components/ui/alert";
import { Field, Input } from "@/components/ui/form";
import { FormBehavior } from "@/components/ui/form-behavior";
import { SubmitButton } from "@/components/ui/submit-button";
import type { ActionState } from "@/lib/validation/common";

const initial: ActionState = {};

export function LoginForm({ next }: { next?: string }) {
  const [state, action] = useActionState(loginAction, initial);
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior />
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      <input type="hidden" name="next" value={next ?? ""} />
      <Field id="email" label="Correo electrónico" required error={state.fieldErrors?.email}>
        <Input name="email" type="email" autoComplete="email" />
      </Field>
      <Field id="password" label="Contraseña" required error={state.fieldErrors?.password}>
        <Input name="password" type="password" autoComplete="current-password" />
      </Field>
      <SubmitButton className="w-full" pendingText="Accediendo…">
        Iniciar sesión
      </SubmitButton>
      <p className="text-center text-sm">
        <Link href="/forgot-password" className="inline-flex min-h-11 items-center text-petrol-700 underline underline-offset-4">
          He olvidado mi contraseña
        </Link>
      </p>
    </form>
  );
}

export function RegisterForm() {
  const [state, action] = useActionState(registerAction, initial);
  const fe = state.fieldErrors ?? {};
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior />
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      <Field id="displayName" label="Nombre profesional" required hint="Nombre con el que firmas tus obras. Podrás decidir si se publica en cada declaración." error={fe.displayName}>
        <Input name="displayName" autoComplete="name" minLength={2} />
      </Field>
      <Field id="email" label="Correo electrónico" required hint="Privado. Nunca se publica." error={fe.email}>
        <Input name="email" type="email" autoComplete="email" />
      </Field>
      <Field id="password" label="Contraseña" required hint="Al menos 10 caracteres. Una frase larga es más segura y fácil de recordar." error={fe.password}>
        <Input name="password" type="password" autoComplete="new-password" minLength={10} />
      </Field>
      <div className="space-y-1">
        <label className="flex min-h-11 items-start gap-3 text-sm">
          <input type="checkbox" name="acceptTerms" className="mt-1 h-4 w-4 accent-[var(--color-petrol-800)]" aria-describedby="acceptTerms-error" />
          <span>
            Acepto las{" "}
            <Link href="/terms" className="text-petrol-700 underline" target="_blank">
              condiciones de uso
            </Link>{" "}
            y he leído la{" "}
            <Link href="/privacy" className="text-petrol-700 underline" target="_blank">
              política de privacidad
            </Link>
            .
          </span>
        </label>
        <p id="acceptTerms-error" data-field-error role="alert" className="text-sm font-medium text-danger" hidden={!fe.acceptTerms}>
          {fe.acceptTerms}
        </p>
      </div>
      <SubmitButton className="w-full" pendingText="Creando cuenta…">
        Crear cuenta
      </SubmitButton>
    </form>
  );
}

export function ForgotForm() {
  const [state, action] = useActionState(forgotPasswordAction, initial);
  if (state.ok) return <Alert tone="ok">{state.message}</Alert>;
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior />
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      <Field id="email" label="Correo electrónico" required error={state.fieldErrors?.email}>
        <Input name="email" type="email" autoComplete="email" />
      </Field>
      <SubmitButton className="w-full" pendingText="Enviando…">
        Enviar enlace de recuperación
      </SubmitButton>
    </form>
  );
}

export function ResetForm({ token }: { token: string }) {
  const [state, action] = useActionState(resetPasswordAction, initial);
  return (
    <form action={action} className="space-y-4" noValidate>
      <FormBehavior />
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      <input type="hidden" name="token" value={token} />
      <Field id="password" label="Nueva contraseña" required hint="Al menos 10 caracteres." error={state.fieldErrors?.password}>
        <Input name="password" type="password" autoComplete="new-password" minLength={10} />
      </Field>
      <Field id="confirm" label="Repite la contraseña" required error={state.fieldErrors?.confirm}>
        <Input name="confirm" type="password" autoComplete="new-password" />
      </Field>
      <SubmitButton className="w-full">Restablecer contraseña</SubmitButton>
    </form>
  );
}
