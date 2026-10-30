"use server";
import { cookies, headers } from "next/headers";
import { redirect } from "next/navigation";
import { LOCALES } from "@/i18n/dictionaries";
import { LOCALE_COOKIE } from "@/i18n/server";

export async function setLocale(formData: FormData) {
  const v = String(formData.get("locale") ?? "");
  if ((LOCALES as readonly string[]).includes(v)) {
    (await cookies()).set(LOCALE_COOKIE, v, { path: "/", maxAge: 31536000, sameSite: "lax" });
  }
  const ref = (await headers()).get("referer");
  let path = "/";
  try {
    if (ref) path = new URL(ref).pathname;
  } catch {}
  redirect(path);
}
