import { getPublicDeclaration } from "@/lib/declarations/public";

const esc = (s: string) => s.replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]!);

/** Distintivo SVG incrustable. Describe; no certifica. */
export async function GET(_: Request, { params }: { params: Promise<{ file: string }> }) {
  const { file } = await params;
  const id = file.replace(/\.svg$/i, "");
  const d = await getPublicDeclaration(id);
  const cls = d?.view.sections.classification?.data as { code?: string | null; status?: string } | undefined;
  let label = "ORIVA";
  let value = "no disponible";
  if (d) {
    value = d.status === "REVOKED" ? "revocada" : cls?.code ? cls.code : cls ? "perfil compuesto" : d.publicId;
    if (d.status === "SUPERSEDED") value += " · sustituida";
  }
  label = esc(label);
  value = esc(value);
  const lw = 62;
  const vw = Math.max(70, value.length * 7.4 + 20);
  const w = lw + vw;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="28" viewBox="0 0 ${w} 28" role="img" aria-label="${label}: ${value}"><title>${label}: ${value}</title><rect width="${w}" height="28" rx="6" fill="#0e3b3c"/><rect x="${lw}" width="${vw}" height="28" rx="6" fill="#faf8f4"/><rect x="${lw}" width="8" height="28" fill="#faf8f4"/><circle cx="14" cy="14" r="4" fill="#2ba39a"/><text x="23" y="18.5" fill="#fff" font-family="Inter,Segoe UI,Helvetica,Arial,sans-serif" font-size="11" font-weight="700" letter-spacing="1.5">${label}</text><text x="${lw + vw / 2}" y="18.5" text-anchor="middle" fill="#0e3b3c" font-family="JetBrains Mono,Menlo,Consolas,monospace" font-size="12">${value}</text><rect x="0.5" y="0.5" width="${w - 1}" height="27" rx="5.5" fill="none" stroke="#0e3b3c"/></svg>`;
  return new Response(svg, {
    status: d ? 200 : 404,
    headers: { "Content-Type": "image/svg+xml; charset=utf-8", "Cache-Control": "public, max-age=300", "X-Content-Type-Options": "nosniff", "Content-Security-Policy": "default-src 'none'; style-src 'unsafe-inline'", "Cross-Origin-Resource-Policy": "cross-origin" },
  });
}
