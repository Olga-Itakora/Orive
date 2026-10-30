# ORIVA — imagen de producción (Next.js standalone).
# NOTA: este Dockerfile no se ha podido construir en el entorno de desarrollo
# (sin demonio Docker). Revísalo en tu CI antes de usarlo en producción.

FROM node:22-bookworm-slim AS deps
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends openssl ca-certificates && rm -rf /var/lib/apt/lists/*
COPY package.json package-lock.json ./
COPY prisma ./prisma
RUN npm ci

FROM deps AS builder
WORKDIR /app
COPY . .
ENV NEXT_TELEMETRY_DISABLED=1
RUN npx prisma generate && npm run build

# Imagen para tareas de base de datos (migraciones y seed): incluye todas las dependencias.
FROM builder AS migrator
CMD ["sh", "-c", "npx prisma migrate deploy && npx tsx prisma/seed.ts"]

FROM node:22-bookworm-slim AS runner
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends openssl ca-certificates && rm -rf /var/lib/apt/lists/* \
  && groupadd --system --gid 1001 oriva && useradd --system --uid 1001 --gid oriva oriva
ENV NODE_ENV=production NEXT_TELEMETRY_DISABLED=1 PORT=3000 HOSTNAME=0.0.0.0
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/assets ./assets
COPY --from=builder /app/node_modules/.prisma ./node_modules/.prisma
COPY --from=builder /app/node_modules/@prisma ./node_modules/@prisma
RUN mkdir -p /app/storage/evidence && chown -R oriva:oriva /app/storage
USER oriva
EXPOSE 3000
CMD ["node", "server.js"]
