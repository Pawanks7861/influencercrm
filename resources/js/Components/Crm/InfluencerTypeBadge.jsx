import { enumValue } from '@/lib/format';
import { cn } from '@/lib/format';

const TYPE_TONES = {
    premium: 'bg-amber-50 text-amber-800 ring-amber-600/15',
    medium: 'bg-sky-50 text-sky-800 ring-sky-600/15',
    low: 'bg-slate-100 text-slate-700 ring-slate-500/15',
};

export default function InfluencerTypeBadge({ type, labels = {}, className = '' }) {
    const key = enumValue(type);
    const label = labels[key] || key || '—';
    const tone = TYPE_TONES[key] || 'bg-slate-100 text-slate-700 ring-slate-500/15';

    return (
        <span className={cn('inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset capitalize', tone, className)}>
            {label}
        </span>
    );
}
