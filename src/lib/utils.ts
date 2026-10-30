import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(d: Date | string | null | undefined, withTime = false): string {
  if (!d) return "—";
  const date = typeof d === "string" ? new Date(d) : d;
  return new Intl.DateTimeFormat("es-ES", {
    dateStyle: "long",
    ...(withTime ? { timeStyle: "short", timeZone: "UTC" } : { timeZone: "UTC" }),
  }).format(date) + (withTime ? " UTC" : "");
}
