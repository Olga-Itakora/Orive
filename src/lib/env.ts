import "server-only";
import { z } from "zod";

const schema = z.object({
  DATABASE_URL: z.string().min(1),
  APP_URL: z.string().url().default("http://localhost:3000"),
  APP_SECRET: z.string().min(32, "APP_SECRET debe tener al menos 32 caracteres"),
  EVIDENCE_STORAGE_DIR: z.string().default("./storage/evidence"),
  EVIDENCE_MAX_BYTES: z.coerce.number().int().positive().default(10 * 1024 * 1024),
  COOKIE_SECURE: z.enum(["true", "false"]).default("false"),
  NODE_ENV: z.string().default("development"),
});

let cached: z.infer<typeof schema> | null = null;

export function env() {
  if (!cached) {
    const parsed = schema.safeParse(process.env);
    if (!parsed.success) {
      throw new Error("Configuración de entorno no válida: " + parsed.error.issues.map((i) => `${i.path.join(".")}: ${i.message}`).join("; "));
    }
    cached = parsed.data;
  }
  return cached;
}

export function appUrl(path = ""): string {
  return env().APP_URL.replace(/\/$/, "") + path;
}
