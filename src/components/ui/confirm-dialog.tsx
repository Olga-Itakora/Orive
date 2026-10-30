"use client";
import { useId, useRef } from "react";
import { Trash2 } from "lucide-react";
import { Button } from "./button";
import { IconButton } from "./icon-button";
import { SubmitButton } from "./submit-button";

/**
 * Confirmación de acciones destructivas:
 * - el título nombra la entidad exacta («¿Eliminar el uso "X"?»);
 * - el botón de confirmación usa un verbo destructivo y color de peligro;
 * - Cancelar recibe el foco al abrir; Escape o clic fuera cancelan;
 * - el foco vuelve al disparador al cerrar.
 */
export function ConfirmDialog({
  title,
  body,
  confirmLabel,
  action,
  trigger,
  triggerLabel,
  children,
}: {
  title: string;
  body: React.ReactNode;
  confirmLabel: string;
  action: (formData: FormData) => void | Promise<void>;
  /** "icon" (papelera con tooltip) o "text" (botón de texto en color de peligro). */
  trigger: "icon" | "text";
  triggerLabel: string;
  /** Campos adicionales dentro del diálogo (p. ej. un motivo). */
  children?: React.ReactNode;
}) {
  const titleId = useId();
  const dialog = useRef<HTMLDialogElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const cancelRef = useRef<HTMLButtonElement>(null);

  const open = () => {
    dialog.current?.showModal();
    cancelRef.current?.focus();
  };
  const close = () => {
    dialog.current?.close();
  };

  return (
    <>
      {trigger === "icon" ? (
        <IconButton ref={triggerRef} label={triggerLabel} tone="danger" onClick={open}>
          <Trash2 className="h-4 w-4" aria-hidden />
        </IconButton>
      ) : (
        <Button ref={triggerRef} type="button" variant="link" className="text-danger" onClick={open}>
          {triggerLabel}
        </Button>
      )}
      <dialog
        ref={dialog}
        aria-labelledby={titleId}
        onClose={() => triggerRef.current?.focus()}
        onClick={(e) => {
          if (e.target === dialog.current) close();
        }}
        className="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[var(--radius-lg)] border border-line bg-surface p-0 text-ink backdrop:bg-ink/40"
      >
        <form action={action} className="space-y-4 p-6">
          <h2 id={titleId} className="text-lg font-semibold text-ink">
            {title}
          </h2>
          <div className="text-sm text-muted">{body}</div>
          {children}
          <div className="flex flex-wrap justify-end gap-3 pt-2">
            <Button ref={cancelRef} type="button" variant="secondary" onClick={close} autoFocus>
              Cancelar
            </Button>
            <SubmitButton variant="danger" pendingText="Procesando…">
              {confirmLabel}
            </SubmitButton>
          </div>
        </form>
      </dialog>
    </>
  );
}
