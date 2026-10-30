import type { Metadata, Viewport } from "next";
import localFont from "next/font/local";
import { getLocale } from "@/i18n/server";
import "./globals.css";

const inter = localFont({
  src: [
    { path: "./fonts/Inter-Regular.otf", weight: "400", style: "normal" },
    { path: "./fonts/Inter-Medium.otf", weight: "500", style: "normal" },
    { path: "./fonts/Inter-SemiBold.otf", weight: "600", style: "normal" },
  ],
  variable: "--font-inter",
  display: "swap",
});
const mono = localFont({ src: "./fonts/JetBrainsMono-Medium.ttf", variable: "--font-jetbrains", display: "swap" });

export const metadata: Metadata = {
  metadataBase: new URL(process.env.APP_URL ?? "http://localhost:3000"),
  title: { default: "ORIVA — Creative AI Transparency", template: "%s · ORIVA" },
  description: "ORIVA permite documentar cómo se ha utilizado la inteligencia artificial en un proceso creativo, generar un código de transparencia y emitir una declaración responsable verificable.",
  applicationName: "ORIVA",
  openGraph: { title: "ORIVA — Every creation has a process. Make it visible.", siteName: "ORIVA", type: "website" },
};

export const viewport: Viewport = { themeColor: "#0e3b3c", width: "device-width", initialScale: 1 };

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const locale = await getLocale();
  return (
    <html lang={locale} className={`${inter.variable} ${mono.variable}`}>
      <body className="min-h-dvh bg-paper font-sans antialiased">{children}</body>
    </html>
  );
}
