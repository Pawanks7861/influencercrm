import { cn } from '@/lib/format';

export default function PageHeader({ title, description, actions, className = '' }) {
    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6', className)}>
            <div>
                <h1 className="font-display text-2xl font-semibold tracking-tight text-ink">{title}</h1>
                {description && <p className="mt-1 text-sm text-ink-muted">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
