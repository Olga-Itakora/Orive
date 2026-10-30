import { defineConfig, devices } from "@playwright/test";
import { existsSync, readFileSync } from "node:fs";

// Carga .env (si existe) para que las pruebas usen las mismas credenciales del seed.
if (existsSync(".env")) {
  for (const line of readFileSync(".env", "utf8").split("\n")) {
    const m = /^([A-Z0-9_]+)=\"?(.*?)\"?$/.exec(line.trim());
    if (m && process.env[m[1]] === undefined) process.env[m[1]] = m[2];
  }
}

const PORT = Number(process.env.E2E_PORT ?? 3100);

export default defineConfig({
  testDir: "tests/e2e",
  timeout: 120_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  reporter: [["list"]],
  use: {
    baseURL: `http://localhost:${PORT}`,
    trace: "retain-on-failure",
    locale: "es-ES",
    acceptDownloads: true,
  },
  projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
  webServer: {
    command: process.env.E2E_COMMAND ?? `npx next dev -p ${PORT}`,
    url: `http://localhost:${PORT}`,
    reuseExistingServer: !process.env.CI,
    timeout: 180_000,
    env: { ORIVA_DISABLE_RATE_LIMIT: "true", APP_URL: `http://localhost:${PORT}`, NEXT_TELEMETRY_DISABLED: "1" },
  },
});
