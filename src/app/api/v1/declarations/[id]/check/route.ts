import { NextResponse } from "next/server";
import { checkFileAgainstRegistry } from "@/lib/declarations/public";
import { rateLimit } from "@/lib/security/rate-limit";
import { clientIp } from "@/lib/security/request";

const MAX = 15 * 1024 * 1024;

/** Comprueba un fichero (PDF o JSON) contra el registro. Acepta el cuerpo binario directamente. */
export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  if (!(await rateLimit("api-check", await clientIp(), 30, 600))) return NextResponse.json({ error: "rate_limited" }, { status: 429 });
  const len = Number(req.headers.get("content-length") ?? 0);
  if (len > MAX) return NextResponse.json({ error: "too_large" }, { status: 413 });
  const { id } = await params;
  const buf = new Uint8Array(await req.arrayBuffer());
  if (buf.byteLength === 0 || buf.byteLength > MAX) return NextResponse.json({ error: "invalid_body" }, { status: 400 });
  const res = await checkFileAgainstRegistry(id, buf);
  return NextResponse.json(res);
}
