import { cn } from '@/lib/format';

export default function StatCard({ label, value, hint, icon: Icon, tone = 'default', className = '' }) {
    const tones = {
        default: 'border-surface-border',
        accent: 'border-accent/30 bg-accent-soft/40',
        warning: 'border-amber-200 bg-amber-50',
        danger: 'border-red-200 bg-red-50',
    };

    return (
        <div className={cn('crm-card p-4', tones[tone], className)}>
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">{label}</p>
                    <p className="mt-2 font-display text-2xl font-semibold text-ink tabular-nums">{value}</p>
                    {hint && <p className="mt-1 text-xs text-ink-muted">{hint}</p>}
                </div>
                {Icon && (
                    <div className="rounded-lg bg-surface p-2 text-accent">
                        <Icon className="h-4 w-4" strokeWidth={1.75} />
                    </div>
                )}
            </div>
        </div>
    );
}
