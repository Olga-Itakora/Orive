import QRCode from "qrcode";
import { getPublicDeclaration } from "@/lib/declarations/public";
import { appUrl } from "@/lib/env";

export async function GET(_: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const d = await getPublicDeclaration(id);
  if (!d) return new Response("No encontrado", { status: 404 });
  const svg = await QRCode.toString(appUrl(`/verify/${d.publicId}`), { type: "svg", margin: 1, color: { dark: "#0e3b3c", light: "#ffffff" } });
  return new Response(svg, { headers: { "Content-Type": "image/svg+xml", "Cache-Control": "public, max-age=3600", "X-Content-Type-Options": "nosniff" } });
}
