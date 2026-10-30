import { execSync } from "node:child_process";
import { TEST_DATABASE_URL } from "./env";

export default function setup() {
  const env = { ...process.env, DATABASE_URL: TEST_DATABASE_URL, SEED_ADMIN_EMAIL: "admin@test.local", SEED_ADMIN_PASSWORD: "Admin-Test-2026!" };
  execSync("npx prisma migrate deploy", { env, stdio: "inherit" });
  execSync("npx tsx prisma/seed.ts", { env, stdio: "inherit" });
}
