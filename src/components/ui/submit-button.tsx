"use client";
import { useEffect, useState } from "react";
import { useFormStatus } from "react-dom";
import { Button, type ButtonProps } from "./button";

/**
 * Botón de envío. Siempre pulsable (la validación muestra los errores al pulsar).
 * El indicador de progreso solo aparece si la espera supera 100 ms.
 */
export function SubmitButton({ children, pendingText, ...props }: ButtonProps & { pendingText?: string }) {
  const { pending } = useFormStatus();
  const [showPending, setShowPending] = useState(false);
  useEffect(() => {
    if (!pending) return setShowPending(false);
    const t = setTimeout(() => setShowPending(true), 100);
    return () => clearTimeout(t);
  }, [pending]);
  return (
    <Button type="submit" aria-busy={pending || undefined} {...props} onClick={(e) => (pending ? e.preventDefault() : props.onClick?.(e))}>
      {showPending ? pendingText ?? "Guardando…" : children}
    </Button>
  );
}
