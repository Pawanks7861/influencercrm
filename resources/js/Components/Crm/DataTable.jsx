import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/format';

function SkeletonRows({ columns }) {
    return (
        <tbody>
            {Array.from({ length: 6 }).map((_, i) => (
                <tr key={i} className="border-t border-surface-border">
                    {Array.from({ length: columns }).map((__, j) => (
                        <td key={j} className="px-4 py-3">
                            <div className="h-4 w-full max-w-[140px] animate-pulse rounded bg-slate-100" />
                        </td>
                    ))}
                </tr>
            ))}
        </tbody>
    );
}

export default function DataTable({
    columns = [],
    rows = [],
    sort,
    direction = 'asc',
    onSort,
    paginator,
    loading = false,
    empty,
    rowKey = 'id',
    selectable = false,
    selectedIds = [],
    onToggleRow,
    onToggleAll,
    className = '',
}) {
    const allIds = rows.map((r) => r[rowKey]);
    const allSelected = selectable && allIds.length > 0 && allIds.every((id) => selectedIds.includes(id));

    return (
        <div className={cn('crm-card overflow-hidden', className)}>
            <div className="overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                    <thead className="bg-surface/80 text-xs uppercase tracking-wide text-ink-muted">
                        <tr>
                            {selectable && (
                                <th className="w-10 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        checked={allSelected}
                                        onChange={(e) => onToggleAll?.(e.target.checked, allIds)}
                                        className="rounded border-surface-border text-accent focus:ring-accent/30"
                                    />
                                </th>
                            )}
                            {columns.map((col) => {
                                const sortable = Boolean(col.sortKey);
                                const active = sort === col.sortKey;
                                return (
                                    <th key={col.key} className={cn('px-4 py-3 font-medium', col.className)}>
                                        {sortable ? (
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-1 hover:text-ink"
                                                onClick={() => onSort?.(col.sortKey)}
                                            >
                                                {col.label}
                                                {active && <span className="text-accent">{direction === 'asc' ? '↑' : '↓'}</span>}
                                            </button>
                                        ) : (
                                            col.label
                                        )}
                                    </th>
                                );
                            })}
                        </tr>
                    </thead>
                    {loading ? (
                        <SkeletonRows columns={columns.length + (selectable ? 1 : 0)} />
                    ) : (
                        <tbody>
                            {rows.length === 0 ? (
                                <tr>
                                    <td colSpan={columns.length + (selectable ? 1 : 0)} className="px-4 py-10">
                                        {empty}
                                    </td>
                                </tr>
                            ) : (
                                rows.map((row) => (
                                    <tr key={row[rowKey]} className="border-t border-surface-border hover:bg-surface/60">
                                        {selectable && (
                                            <td className="px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(row[rowKey])}
                                                    onChange={() => onToggleRow?.(row[rowKey])}
                                                    className="rounded border-surface-border text-accent focus:ring-accent/30"
                                                />
                                            </td>
                                        )}
                                        {columns.map((col) => (
                                            <td key={col.key} className={cn('px-4 py-3 text-ink-soft', col.cellClassName)}>
                                                {col.render ? col.render(row) : row[col.key]}
                                            </td>
                                        ))}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    )}
                </table>
            </div>

            {paginator && (
                <div className="flex flex-col gap-3 border-t border-surface-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-xs text-ink-muted">
                        Showing {paginator.from ?? 0}–{paginator.to ?? 0} of {paginator.total ?? 0}
                    </p>
                    <div className="flex flex-wrap items-center gap-1">
                        {(paginator.links || []).map((link, idx) => {
                            if (idx === 0) {
                                return (
                                    <Link
                                        key={`prev-${idx}`}
                                        href={link.url || '#'}
                                        preserveScroll
                                        className={cn(
                                            'inline-flex h-8 w-8 items-center justify-center rounded-md border border-surface-border',
                                            link.url ? 'hover:bg-surface' : 'pointer-events-none opacity-40'
                                        )}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </Link>
                                );
                            }
                            if (idx === paginator.links.length - 1) {
                                return (
                                    <Link
                                        key={`next-${idx}`}
                                        href={link.url || '#'}
                                        preserveScroll
                                        className={cn(
                                            'inline-flex h-8 w-8 items-center justify-center rounded-md border border-surface-border',
                                            link.url ? 'hover:bg-surface' : 'pointer-events-none opacity-40'
                                        )}
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </Link>
                                );
                            }
                            return (
                                <Link
                                    key={`${link.label}-${idx}`}
                                    href={link.url || '#'}
                                    preserveScroll
                                    className={cn(
                                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-medium',
                                        link.active
                                            ? 'bg-ink text-white'
                                            : link.url
                                              ? 'border border-surface-border text-ink-soft hover:bg-surface'
                                              : 'pointer-events-none text-ink-muted'
                                    )}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
