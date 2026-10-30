import { z } from "zod";
import { CREATIVE_PHASES } from "@/lib/oriva-code/taxonomy";
import { requiredText } from "./common";

const fn = z.enum(["A1", "A2", "A3", "A4", "A5"]);
const contributionInput = z.object({
  type: z.enum(["B1", "B2", "B3"]),
  interventionAnswer: z.enum(["ACCEPTED", "MINOR_EDITS", "TRANSFORMED", "OWN_EXPRESSION", "UNSURE"]).nullable(),
  retainsRecognizableElements: z.enum(["YES", "NO", "UNSURE"]).nullable(),
  interventionDescription: z.string().trim().max(3000).default(""),
});

export const useInputSchema = z.object({
  title: requiredText(160, "Pon un título breve a este uso (por ejemplo, «Revisión ortográfica»)."),
  componentId: z.string().nullable(),
  toolId: z.string().nullable(),
  parentUseId: z.string().nullable().default(null),
  phases: z.array(z.enum(CREATIVE_PHASES)).max(CREATIVE_PHASES.length),
  functions: z.array(fn).max(5),
  primaryFunction: z.union([fn, z.literal("NONE")]).nullable(),
  objective: z.string().trim().max(2000).default(""),
  activityDescription: z.string().trim().max(4000).default(""),
  resultDescription: z.string().trim().max(4000).default(""),
  incorporation: z.enum(["NONE", "INCORPORATED", "UNKNOWN"]).nullable(),
  contributions: z.array(contributionInput).max(3),
});
export type UseInput = z.infer<typeof useInputSchema>;
