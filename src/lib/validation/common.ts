import { z } from "zod";

export type ActionState = {
  ok?: boolean;
  error?: string;
  message?: string;
  fieldErrors?: Record<string, string>;
};

export function fieldErrors(error: z.ZodError): Record<string, string> {
  const out: Record<string, string> = {};
  for (const issue of error.issues) {
    const k = issue.path.join(".");
    if (!out[k]) out[k] = issue.message;
  }
  return out;
}

/** Cadena opcional: "" → null. */
export const optionalText = (max: number) =>
  z
    .string()
    .trim()
    .max(max, `Máximo ${max} caracteres.`)
    .optional()
    .transform((v) => (v ? v : null));

export const requiredText = (max: number, msg = "Este campo es obligatorio.") => z.string().trim().min(1, msg).max(max, `Máximo ${max} caracteres.`);

export const optionalUrl = z
  .string()
  .trim()
  .max(500)
  .optional()
  .transform((v) => (v ? v : null))
  .refine((v) => v === null || /^https?:\/\/[^\s]+$/i.test(v), "Introduce una URL completa que empiece por http:// o https://");

export function formObject(fd: FormData): Record<string, string> {
  const o: Record<string, string> = {};
  for (const [k, v] of fd.entries()) if (typeof v === "string" && !(k in o)) o[k] = v;
  return o;
}
