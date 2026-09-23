import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import useDebouncedValue from '@/hooks/useDebouncedValue';
import { cn } from '@/lib/format';

export default function SearchInput({
    value = '',
    onChange,
    placeholder = 'Search…',
    delay = 350,
    className = '',
}) {
    const [local, setLocal] = useState(value);
    const debounced = useDebouncedValue(local, delay);

    useEffect(() => {
        setLocal(value);
    }, [value]);

    useEffect(() => {
        if (debounced !== value) {
            onChange?.(debounced);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debounced]);

    return (
        <div className={cn('relative', className)}>
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" strokeWidth={1.75} />
            <input
                type="search"
                value={local}
                onChange={(e) => setLocal(e.target.value)}
                placeholder={placeholder}
                className="crm-input pl-9"
            />
        </div>
    );
}
