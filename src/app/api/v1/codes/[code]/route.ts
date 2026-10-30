import { NextResponse } from "next/server";
import { explainORIVACode } from "@/lib/oriva-code";

export async function GET(req: Request, { params }: { params: Promise<{ code: string }> }) {
  const { code } = await params;
  const locale = new URL(req.url).searchParams.get("locale") === "en" ? "en" : "es";
  const e = explainORIVACode(decodeURIComponent(code).slice(0, 40), locale);
  return NextResponse.json(e, { status: e.valid ? 200 : 400, headers: { "Access-Control-Allow-Origin": "*", "Cache-Control": "public, max-age=3600" } });
}
