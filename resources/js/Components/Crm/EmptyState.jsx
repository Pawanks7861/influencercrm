export default function EmptyState({ icon: Icon, title, description, action }) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
            {Icon && (
                <div className="mb-3 rounded-full bg-surface p-3 text-ink-muted">
                    <Icon className="h-5 w-5" strokeWidth={1.75} />
                </div>
            )}
            <h3 className="font-display text-base font-semibold text-ink">{title}</h3>
            {description && <p className="mt-1 max-w-sm text-sm text-ink-muted">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
