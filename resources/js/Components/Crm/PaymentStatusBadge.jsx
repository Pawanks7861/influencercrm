import { cn, enumValue, labelFromMap } from '@/lib/format';

const TONES = {
    not_paid: 'bg-slate-100 text-slate-700 ring-slate-500/15',
    partially_paid: 'bg-amber-50 text-amber-800 ring-amber-600/15',
    paid: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    payment_pending: 'bg-orange-50 text-orange-800 ring-orange-600/15',
};

const DEFAULT_LABELS = {
    not_paid: 'Not Paid',
    partially_paid: 'Partially Paid',
    paid: 'Paid',
    payment_pending: 'Payment Pending',
};

export default function PaymentStatusBadge({ status, labels = DEFAULT_LABELS, className = '' }) {
    const key = enumValue(status);
    const tone = TONES[key] || TONES.not_paid;

    return (
        <span className={cn('inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset', tone, className)}>
            {labelFromMap(labels, key)}
        </span>
    );
}
