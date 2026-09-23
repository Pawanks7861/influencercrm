import { X } from 'lucide-react';

export default function FilterChips({ chips = [], onRemove, onClear }) {
    if (!chips.length) return null;

    return (
        <div className="flex flex-wrap items-center gap-2">
            {chips.map((chip) => (
                <button
                    key={chip.key}
                    type="button"
                    onClick={() => onRemove?.(chip.key)}
                    className="inline-flex items-center gap-1.5 rounded-full border border-surface-border bg-white px-2.5 py-1 text-xs font-medium text-ink-soft hover:border-accent/40"
                >
                    <span className="text-ink-muted">{chip.label}:</span>
                    <span>{chip.value}</span>
                    <X className="h-3 w-3 text-ink-muted" />
                </button>
            ))}
            {onClear && (
                <button type="button" onClick={onClear} className="text-xs font-medium text-accent hover:text-accent-hover">
                    Clear all
                </button>
            )}
        </div>
    );
}
