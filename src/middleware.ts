import { NextResponse, type NextRequest } from "next/server";

/**
 * Cabeceras de seguridad y Content-Security-Policy con nonce por petición.
 * Next.js aplica automáticamente el nonce a sus scripts.
 */
export function middleware(request: NextRequest) {
  const nonce = btoa(crypto.randomUUID());
  const isDev = process.env.NODE_ENV !== "production";
  const csp = [
    "default-src 'self'",
    `script-src 'self' 'nonce-${nonce}' 'strict-dynamic'${isDev ? " 'unsafe-eval'" : ""}`,
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: blob:",
    "font-src 'self'",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "form-action 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    ...(isDev ? [] : ["upgrade-insecure-requests"]),
  ].join("; ");

  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-nonce", nonce);
  requestHeaders.set("Content-Security-Policy", csp);

  const response = NextResponse.next({ request: { headers: requestHeaders } });
  response.headers.set("Content-Security-Policy", csp);
  response.headers.set("X-Content-Type-Options", "nosniff");
  response.headers.set("Referrer-Policy", "strict-origin-when-cross-origin");
  response.headers.set("X-Frame-Options", "DENY");
  response.headers.set("Permissions-Policy", "camera=(), microphone=(), geolocation=(), interest-cohort=()");
  response.headers.set("Cross-Origin-Opener-Policy", "same-origin");
  if (!isDev) response.headers.set("Strict-Transport-Security", "max-age=63072000; includeSubDomains; preload");
  if (request.nextUrl.pathname.startsWith("/app") || request.nextUrl.pathname.startsWith("/admin")) {
    response.headers.set("Cache-Control", "no-store");
  }
  return response;
}

export const config = {
  matcher: [{ source: "/((?!_next/static|_next/image|favicon.ico|badge/).*)" }],
};
