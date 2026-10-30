import { LegalTextPage } from "@/components/site/legal-text-page";

export const metadata = { title: "Aviso legal" };

export default function Page() {
  return <LegalTextPage textKey="legal_notice" />;
}
