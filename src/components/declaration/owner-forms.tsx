"use client";
import { useActionState, useId, useRef, useState } from "react";
import { Button } from "@/components/ui/button";
import { FormBehavior } from "@/components/ui/form-behavior";
import { Alert } from "@/components/ui/alert";
import { Field, Textarea } from "@/components/ui/form";
import { SubmitButton } from "@/components/ui/submit-button";
import type { ActionState } from "@/lib/validation/common";

type Act = (s: ActionState, fd: FormData) => Promise<ActionState>;

export interface Flags {
  showName: boolean;
  showWork: boolean;
  showCode: boolean;
  showSummary: boolean;
  showFull: boolean;
  showEvidence: boolean;
}

export function PublishForm({ action, initial, isPublic, hasEvidence }: { action: Act; initial: Flags; isPublic: boolean; hasEvidence: boolean }) {
  const [state, formAction] = useActionState(action, {});
  const [f, setF] = useState<Flags>(initial);
  const [consent, setConsent] = useState(false);
  const toggle = (k: keyof Flags) => setF((o) => ({ ...o, [k]: !o[k] }));
  const opts: { k: keyof Flags; label: string; help: string; forced?: boolean }[] = [
    { k: "showCode", label: "Código o perfil ORIVA y alcance", help: "La clasificación y qué cubre la declaración.", forced: f.showFull },
    { k: "showWork", label: "Datos de la obra", help: "Título, tipo, editorial, identificadores…", forced: f.showFull },
    { k: "showSummary", label: "Resumen", help: "Descripción sintética de los usos declarados.", forced: f.showFull },
    { k: "showName", label: "Mi nombre profesional", help: "Nombre, nombre artístico, organización y rol. Nunca el correo electrónico." },
    { k: "showFull", label: "Declaración completa", help: "Herramientas, usos, intervenciones, orientación del art. 50 y texto aceptado. Incluye obra, código y resumen." },
  ];
  return (
    <form action={formAction} className="space-y-4">
      <FormBehavior />
      {state.error && <Alert tone="danger">{state.error}</Alert>}
      {state.ok && <Alert tone="ok">{state.message}</Alert>}
      <fieldset className="space-y-2">
        <legend className="mb-1 text-sm font-medium">¿Qué quieres que se pueda consultar públicamente?</legend>
        {opts.map((o) => (
          <label key={o.k} className="flex items-start gap-3 rounded-md border border-line p-3 text-sm has-[:checked]:border-petrol-600 has-[:checked]:bg-petrol-50">
            <input type="checkbox" name={o.k} checked={f[o.k] || !!o.forced} disabled={!!o.forced} onChange={() => toggle(o.k)} className="mt-1 h-4 w-4 accent-[var(--color-petrol-800)]" />
            {o.forced && <input type="hidden" name={o.k} value="on" />}
            <span>
              <span className="font-medium">{o.label}</span>
              <span className="block text-sm text-muted">{o.help}</span>
            </span>
          </label>
        ))}
        {hasEvidence && (
          <div className="rounded-md border border-[#f0dcae] bg-warn-soft p-3 text-sm">
            <label className="flex items-start gap-3">
              <input type="checkbox" name="showEvidence" checked={f.showEvidence} onChange={() => toggle("showEvidence")} className="mt-1 h-4 w-4" />
              <span>
                <span className="font-medium">Metadatos de las evidencias</span>
                <span className="block text-sm">Tipo, descripción, nombre de fichero y huella SHA-256. Los ficheros nunca se publican.</span>
              </span>
            </label>
            {f.showEvidence && (
              <label className="mt-3 flex items-start gap-3 border-t border-[#f0dcae] pt-3">
                <input type="checkbox" name="evidenceConsent" checked={consent} onChange={() => setConsent((c) => !c)} className="mt-1 h-4 w-4" />
                <span className="text-sm">Consiento expresamente la publicación de estos metadatos y confirmo que no contienen datos personales de terceros ni información confidencial.</span>
              </label>
            )}
          </div>
        )}
      </fieldset>
      <p className="text-xs text-muted">Nunca se publican tu correo, tus datos de acceso, prompts o conversaciones privadas ni datos de terceros. Puedes retirar la publicación en cualquier momento.</p>
      <SubmitButton>{isPublic ? "Actualizar publicación" : "Publicar declaración"}</SubmitButton>
    </form>
  );
}

export function RevokeDialog({ action, entity }: { action: Act; entity: string }) {
  const [state, formAction] = useActionState(action, {});
  const dialog = useRef<HTMLDialogElement>(null);
  const trigger = useRef<HTMLButtonElement>(null);
  const cancel = useRef<HTMLButtonElement>(null);
  const titleId = useId();
  if (state.ok && dialog.current?.open) dialog.current.close();
  return (
    <>
      {state.ok && <Alert tone="ok">{state.message}</Alert>}
      <Button
        ref={trigger}
        type="button"
        variant="link"
        className="text-danger"
        onClick={() => {
          dialog.current?.showModal();
          cancel.current?.focus();
        }}
      >
        Revocar esta declaración
      </Button>
      <dialog
        ref={dialog}
        aria-labelledby={titleId}
        onClose={() => trigger.current?.focus()}
        onClick={(e) => e.target === dialog.current && dialog.current?.close()}
        className="m-auto w-[min(32rem,calc(100vw-2rem))] rounded-[var(--radius-lg)] border border-line bg-surface p-0 text-ink backdrop:bg-ink/40"
      >
        <form action={formAction} className="space-y-4 p-6" noValidate>
          <FormBehavior />
          <input type="hidden" name="confirm" value="on" />
          <h2 id={titleId} className="text-lg font-semibold">
            ¿Revocar la declaración {entity}?
          </h2>
          <p className="text-sm text-muted">
            Indicará públicamente que ya no es válida. Se conservará y seguirá siendo verificable, marcada como revocada. No se puede deshacer. Si solo quieres dejar de mostrarla, retira la publicación.
          </p>
          {state.error && <Alert tone="danger">{state.error}</Alert>}
          <Field id="reason" label="Motivo de la revocación" required error={state.fieldErrors?.reason} hint="Se mostrará junto a la declaración revocada.">
            <Textarea name="reason" rows={2} maxLength={1000} minLength={5} />
          </Field>
          <div className="flex flex-wrap justify-end gap-3">
            <Button ref={cancel} type="button" variant="secondary" onClick={() => dialog.current?.close()}>
              Cancelar
            </Button>
            <SubmitButton variant="danger" pendingText="Revocando…">
              Revocar declaración
            </SubmitButton>
          </div>
        </form>
      </dialog>
    </>
  );
}
