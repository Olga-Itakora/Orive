import { z } from "zod";

export const emailSchema = z.string().trim().toLowerCase().email("Introduce un correo electrónico válido.").max(254);
export const passwordSchema = z
  .string()
  .min(10, "La contraseña debe tener al menos 10 caracteres.")
  .max(128, "La contraseña no puede superar 128 caracteres.");

export const registerSchema = z.object({
  displayName: z.string().trim().min(2, "Indica tu nombre profesional.").max(120),
  email: emailSchema,
  password: passwordSchema,
  acceptTerms: z.literal("on", { error: "Debes aceptar las condiciones de uso y haber leído la política de privacidad." }),
});

export const loginSchema = z.object({ email: emailSchema, password: z.string().min(1, "Introduce tu contraseña.").max(128) });
export const forgotSchema = z.object({ email: emailSchema });
export const resetSchema = z
  .object({ token: z.string().min(20).max(100), password: passwordSchema, confirm: z.string() })
  .refine((d) => d.password === d.confirm, { path: ["confirm"], message: "Las contraseñas no coinciden." });
export const changePasswordSchema = z
  .object({ current: z.string().min(1, "Introduce tu contraseña actual."), password: passwordSchema, confirm: z.string() })
  .refine((d) => d.password === d.confirm, { path: ["confirm"], message: "Las contraseñas no coinciden." });
