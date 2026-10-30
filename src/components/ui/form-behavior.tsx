"use client";
import { useEffect, useRef, useState } from "react";

/**
 * Comportamiento común de los formularios (se coloca dentro del <form>):
 *
 * - Validación al salir del campo (blur), nunca mientras se escribe en un campo intacto.
 * - El error desaparece al volver a editar el campo (input/change) — también los del servidor.
 * - El envío nunca se bloquea con un botón deshabilitado: si hay errores locales, se
 *   muestran todos y se lleva el foco al primero.
 * - Impide el reinicio automático de React 19 tras una acción, para no perder lo escrito
 *   cuando el servidor devuelve errores. `resetOnOk` vacía el formulario a propósito.
 * - Opcional: borrador local (`draftKey`) con restauración, descarte y aviso al abandonar
 *   la página con cambios sin guardar. Nunca guarda contraseñas ni ficheros.
 */

const MESSAGES = {
  valueMissing: "Este campo es obligatorio.",
  email: "Escribe un correo con el formato nombre@dominio.com.",
  url: "Escribe una dirección completa que empiece por https://.",
  tooShort: (n: number) => `Escribe al menos ${n} caracteres.`,
  tooLong: (n: number) => `Máximo ${n} caracteres.`,
  generic: "Revisa el formato de este campo.",
};

type Field = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

function isField(el: EventTarget | null): el is Field {
  return el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement;
}

function errorSlot(form: HTMLFormElement, el: Field): HTMLElement | null {
  if (el.id) {
    const slot = form.querySelector<HTMLElement>(`#${CSS.escape(el.id)}-error`);
    if (slot) return slot;
  }
  return el.closest("fieldset")?.querySelector<HTMLElement>("[data-field-error]") ?? null;
}

function messageFor(el: Field): string | null {
  if (el.disabled || el.type === "hidden" || el.type === "file") return null;
  if (el instanceof HTMLInputElement && (el.type === "radio" || el.type === "checkbox")) return null;
  const v = el.validity;
  if (v.valid) return null;
  const custom = el.getAttribute("data-error-message");
  if (v.valueMissing) return custom ?? MESSAGES.valueMissing;
  if (v.typeMismatch) return el.type === "email" ? MESSAGES.email : el.type === "url" ? MESSAGES.url : (custom ?? MESSAGES.generic);
  if (v.tooShort && "minLength" in el) return MESSAGES.tooShort(el.minLength);
  if (v.tooLong && "maxLength" in el) return MESSAGES.tooLong(el.maxLength);
  return custom ?? MESSAGES.generic;
}

function show(form: HTMLFormElement, el: Field, msg: string) {
  el.setAttribute("aria-invalid", "true");
  const slot = errorSlot(form, el);
  if (slot) {
    slot.textContent = msg;
    slot.hidden = false;
  }
}

function clear(form: HTMLFormElement, el: Field) {
  el.removeAttribute("aria-invalid");
  const slot = errorSlot(form, el);
  if (slot) slot.hidden = true;
}

function serialize(form: HTMLFormElement): Record<string, string[]> {
  const out: Record<string, string[]> = {};
  for (const el of Array.from(form.elements)) {
    if (!isField(el) || !el.name || el.name.startsWith("$") || el.type === "password" || el.type === "file" || el.type === "hidden" || el.disabled) continue;
    if (el instanceof HTMLInputElement && (el.type === "radio" || el.type === "checkbox")) {
      if (el.checked) (out[el.name] ??= []).push(el.value);
      else out[el.name] ??= [];
      continue;
    }
    (out[el.name] ??= []).push(el.value);
  }
  return out;
}

function restore(form: HTMLFormElement, data: Record<string, string[]>) {
  const seen: Record<string, number> = {};
  for (const el of Array.from(form.elements)) {
    if (!isField(el) || !el.name || !(el.name in data) || el.type === "password" || el.type === "file" || el.type === "hidden") continue;
    const values = data[el.name];
    if (el instanceof HTMLInputElement && (el.type === "radio" || el.type === "checkbox")) {
      const should = values.includes(el.value);
      // click() dispara los manejadores de React en campos controlados.
      if (el.type === "radio" ? should && !el.checked : el.checked !== should) el.click();
      continue;
    }
    const i = (seen[el.name] = (seen[el.name] ?? -1) + 1);
    if (values[i] !== undefined && el.value !== values[i]) {
      const proto = el instanceof HTMLTextAreaElement ? HTMLTextAreaElement.prototype : el instanceof HTMLSelectElement ? HTMLSelectElement.prototype : HTMLInputElement.prototype;
      Object.getOwnPropertyDescriptor(proto, "value")?.set?.call(el, values[i]);
      el.dispatchEvent(new Event("input", { bubbles: true }));
      el.dispatchEvent(new Event("change", { bubbles: true }));
    }
  }
}

export function FormBehavior({ draftKey, okSignal, resetOnOk = false }: { draftKey?: string; okSignal?: unknown; resetOnOk?: boolean }) {
  const anchor = useRef<HTMLSpanElement>(null);
  const [restored, setRestored] = useState(false);
  const dirty = useRef(false);
  const submitting = useRef(false);
  const key = draftKey ? `oriva:draft:${draftKey}` : null;

  useEffect(() => {
    const form = anchor.current?.closest("form");
    if (!form) return;
    const touched = new WeakSet<Element>();

    const onBlur = (e: FocusEvent) => {
      if (!isField(e.target)) return;
      touched.add(e.target);
      const msg = messageFor(e.target);
      if (msg) show(form, e.target, msg);
    };
    const onEdit = (e: Event) => {
      if (!isField(e.target)) return;
      clear(form, e.target);
      if (e.isTrusted) dirty.current = true;
      if (key && e.isTrusted) {
        try {
          localStorage.setItem(key, JSON.stringify({ at: Date.now(), data: serialize(form) }));
        } catch {
          /* almacenamiento no disponible */
        }
      }
    };
    const onSubmit = (e: SubmitEvent) => {
      const invalid = Array.from(form.elements).filter(isField).map((el) => [el, messageFor(el)] as const).filter(([, m]) => m);
      if (invalid.length) {
        e.preventDefault();
        e.stopImmediatePropagation();
        for (const [el, m] of invalid) show(form, el, m!);
        invalid[0][0].focus();
        invalid[0][0].scrollIntoView({ block: "center" });
        return;
      }
      submitting.current = true;
      dirty.current = false;
    };
    const onReset = (e: Event) => {
      if (form.dataset.allowReset === "1") {
        delete form.dataset.allowReset;
        return;
      }
      e.preventDefault();
    };
    const onBeforeUnload = (e: BeforeUnloadEvent) => {
      if (dirty.current && key) {
        e.preventDefault();
        e.returnValue = "";
      }
    };

    form.addEventListener("focusout", onBlur);
    form.addEventListener("input", onEdit);
    form.addEventListener("change", onEdit);
    form.addEventListener("submit", onSubmit, { capture: true });
    form.addEventListener("reset", onReset);
    window.addEventListener("beforeunload", onBeforeUnload);

    if (key) {
      try {
        const raw = localStorage.getItem(key);
        if (raw) {
          const { at, data } = JSON.parse(raw) as { at: number; data: Record<string, string[]> };
          if (Date.now() - at < 7 * 86400_000) {
            const current = JSON.stringify(serialize(form));
            if (current !== JSON.stringify(data)) {
              restore(form, data);
              setRestored(true);
              dirty.current = true;
            }
          } else localStorage.removeItem(key);
        }
      } catch {
        /* borrador ilegible: se ignora */
      }
    }

    return () => {
      form.removeEventListener("focusout", onBlur);
      form.removeEventListener("input", onEdit);
      form.removeEventListener("change", onEdit);
      form.removeEventListener("submit", onSubmit, { capture: true });
      form.removeEventListener("reset", onReset);
      window.removeEventListener("beforeunload", onBeforeUnload);
      // Se desmonta tras un envío (redirección): el borrador ya está guardado en el servidor.
      if (key && submitting.current) {
        try {
          localStorage.removeItem(key);
        } catch {}
      }
    };
  }, [key]);

  // Acción completada con éxito sin navegación.
  useEffect(() => {
    if (!okSignal) return;
    submitting.current = false;
    dirty.current = false;
    setRestored(false);
    if (key) {
      try {
        localStorage.removeItem(key);
      } catch {}
    }
    const form = anchor.current?.closest("form");
    if (resetOnOk && form) {
      form.dataset.allowReset = "1";
      form.reset();
    }
  }, [okSignal, key, resetOnOk]);

  return (
    <span ref={anchor} className="contents">
      {restored && (
        <span role="status" className="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-[var(--radius-md)] border border-petrol-200 bg-petrol-50 px-4 py-2 text-sm text-petrol-900">
          Hemos recuperado cambios que no llegaste a guardar.
          <button
            type="button"
            className="inline-flex min-h-11 items-center font-medium underline underline-offset-4"
            onClick={() => {
              try {
                if (key) localStorage.removeItem(key);
              } catch {}
              dirty.current = false;
              window.location.reload();
            }}
          >
            Descartar cambios recuperados
          </button>
        </span>
      )}
    </span>
  );
}
