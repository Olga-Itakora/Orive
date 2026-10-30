import { z } from "zod";
import { optionalText, optionalUrl, requiredText } from "./common";

export const workSchema = z.object({
  title: requiredText(300, "La obra necesita un título."),
  subtitle: optionalText(300),
  categoryKey: requiredText(60, "Selecciona el tipo de obra."),
  description: optionalText(4000),
  creationPeriod: optionalText(120),
  status: z.enum(["IN_PROGRESS", "FINISHED", "PUBLISHED"], { error: "Selecciona el estado de la obra." }),
  publicationDate: z
    .string()
    .optional()
    .transform((v) => (v ? v : null))
    .refine((v) => v === null || /^\d{4}-\d{2}-\d{2}$/.test(v), "Fecha no válida."),
  language: optionalText(60),
  workVersion: optionalText(60),
  publisher: optionalText(200),
  identifierType: optionalText(30),
  identifier: optionalText(120),
  url: optionalUrl,
  isCollective: z
    .string()
    .optional()
    .transform((v) => v === "on"),
});

export const componentSchema = z.object({
  name: requiredText(120, "Indica el nombre del componente."),
  kind: z.enum(["TEXT", "IMAGE", "AUDIO", "VIDEO", "CODE", "INTERACTIVE", "OTHER"], { error: "Selecciona el tipo de componente." }),
  description: optionalText(1000),
});

export const contributorSchema = z.object({
  name: requiredText(120, "Indica el nombre del colaborador."),
  role: requiredText(120, "Indica su rol."),
});
