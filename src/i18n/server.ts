import "server-only";
import { cookies } from "next/headers";
import { DEFAULT_LOCALE, dictionaries, LOCALES, type Locale } from "./dictionaries";

export const LOCALE_COOKIE = "oriva_locale";

export async function getLocale(): Promise<Locale> {
  const v = (await cookies()).get(LOCALE_COOKIE)?.value;
  return (LOCALES as readonly string[]).includes(v ?? "") ? (v as Locale) : DEFAULT_LOCALE;
}

export async function getDictionary() {
  const locale = await getLocale();
  return { locale, t: dictionaries[locale] };
}
