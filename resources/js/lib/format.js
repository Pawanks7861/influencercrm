export function formatMoney(value, { showDecimals = false } = {}) {
    const num = Number(value);
    if (Number.isNaN(num)) {
        return '₹0';
    }

    const negative = num < 0;
    const abs = Math.abs(num);
    const [intRaw, decRaw] = abs.toFixed(2).split('.');
    const lastThree = intRaw.slice(-3);
    const otherNumbers = intRaw.slice(0, -3);
    const formattedInt =
        otherNumbers !== ''
            ? `${otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',')},${lastThree}`
            : lastThree;

    const decimals = showDecimals || decRaw !== '00' ? `.${decRaw}` : '';
    return `${negative ? '-' : ''}₹${formattedInt}${decimals}`;
}

export function formatDate(value, fallback = '—') {
    if (!value) return fallback;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return fallback;
    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatDateTime(value, fallback = '—') {
    if (!value) return fallback;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return fallback;
    return date.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function enumValue(value) {
    if (value && typeof value === 'object' && 'value' in value) {
        return value.value;
    }
    return value ?? null;
}

export function labelFromMap(map, key) {
    const resolved = enumValue(key);
    if (!resolved) return '—';
    return map?.[resolved] ?? String(resolved).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function cn(...parts) {
    return parts.filter(Boolean).join(' ');
}
