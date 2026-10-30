"use client";
import { useEffect } from "react";

/** Atajo «/» para enfocar la búsqueda (salvo si ya se está escribiendo en un campo). */
export function SlashFocus({ targetId }: { targetId: string }) {
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      const t = e.target as HTMLElement;
      if (e.key !== "/" || e.metaKey || e.ctrlKey || e.altKey || t.closest("input,textarea,select,[contenteditable=true]")) return;
      const el = document.getElementById(targetId);
      if (el) {
        e.preventDefault();
        el.focus();
      }
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [targetId]);
  return null;
}
