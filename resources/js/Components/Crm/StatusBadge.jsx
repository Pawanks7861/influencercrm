import { cn, enumValue, labelFromMap } from '@/lib/format';

const STATUS_TONES = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    draft: 'bg-slate-100 text-slate-700 ring-slate-500/15',
    completed: 'bg-teal-50 text-teal-800 ring-teal-600/15',
    cancelled: 'bg-red-50 text-red-700 ring-red-600/15',
    inactive: 'bg-slate-100 text-slate-600 ring-slate-500/15',
    archived: 'bg-slate-100 text-slate-500 ring-slate-400/15',
    pending: 'bg-amber-50 text-amber-800 ring-amber-600/15',
    overdue: 'bg-red-50 text-red-700 ring-red-600/15',
    new_lead: 'bg-sky-50 text-sky-800 ring-sky-600/15',
    contacted: 'bg-sky-50 text-sky-700 ring-sky-600/15',
    negotiating: 'bg-amber-50 text-amber-800 ring-amber-600/15',
    posted: 'bg-indigo-50 text-indigo-700 ring-indigo-600/15',
    payment_pending: 'bg-orange-50 text-orange-800 ring-orange-600/15',
    payment_completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
};

export default function StatusBadge({ status, labels, className = '' }) {
    const key = enumValue(status);
    const label = labels ? labelFromMap(labels, key) : labelFromMap({}, key);
    const tone = STATUS_TONES[key] || 'bg-slate-100 text-slate-700 ring-slate-500/15';

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                tone,
                className
            )}
        >
            {label}
        </span>
    );
}
