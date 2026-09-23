import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/format';

export default function MoneyDisplay({ amount, className = '', showDecimals = false }) {
    return <span className={cn('tabular-nums font-medium text-ink', className)}>{formatMoney(amount, { showDecimals })}</span>;
}
