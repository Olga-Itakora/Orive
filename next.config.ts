import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  poweredByHeader: false,
  // pdfkit lee ficheros de métricas de fuentes desde disco: no debe empaquetarse.
  serverExternalPackages: ["pdfkit", "@node-rs/argon2", "qrcode"],
  outputFileTracingIncludes: { "/**": ["./assets/fonts/**"] },
  experimental: {
    serverActions: { bodySizeLimit: "12mb" },
  },
};

export default nextConfig;
