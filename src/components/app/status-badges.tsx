import { Badge } from "@/components/ui/badge";
import { DOC_STATUS_LABEL, VISIBILITY_LABEL } from "@/lib/labels";

export function DocStatusBadge({ status }: { status: string }) {
  const tone = status === "ISSUED" ? "ok" : status === "DRAFT" ? "warn" : status === "REVOKED" ? "danger" : "neutral";
  return <Badge tone={tone}>{DOC_STATUS_LABEL[status] ?? status}</Badge>;
}
export function VisibilityBadge({ visibility }: { visibility: string }) {
  return <Badge tone={visibility === "PUBLIC" ? "petrol" : "neutral"}>{VISIBILITY_LABEL[visibility] ?? visibility}</Badge>;
}
