export function AdminTable({ caption, head, children }: { caption: string; head: string[]; children: React.ReactNode }) {
  return (
    <div className="relative overflow-x-auto rounded-[var(--radius-lg)] border border-line bg-surface">
      <table className="w-full min-w-[640px] text-sm">
        <caption className="sr-only">{caption}</caption>
        <thead className="border-b border-line text-left text-xs text-muted">
          <tr>
            {head.map((h) => (
              <th key={h} scope="col" className="px-4 py-3 font-medium">
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-line">{children}</tbody>
      </table>
    </div>
  );
}
export const td = "px-4 py-3 align-top";
