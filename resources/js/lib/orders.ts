import type { OrderStatus } from '@/types/orders';

export const statuses: Record<OrderStatus, string> = {
    created: 'Ожидает оплаты',
    paid: 'Оплачен',
    shipped: 'В доставке',
    delivered: 'Доставлен',
    cancelled: 'Отменён',
};
export function orderUrl(id: string): string {
    return `/orders/${encodeURIComponent(id)}`;
}
export function dateTime(value: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'UTC',
    }).format(new Date(value));
}
// Keep decimal strings exact, including amounts above Number.MAX_SAFE_INTEGER.
export function money(value: string, currency: string): string {
    const [whole, fraction] = value.split('.');
    return `${whole.replace(/\B(?=(\d{3})+(?!\d))/g, ' ')},${fraction} ${currency}`;
}
export function trackingUrl(value: string): string | undefined {
    try {
        const url = new URL(value);
        if (url.protocol === 'https:' || url.protocol === 'http:')
            return url.href;
    } catch {
        /* Invalid marketplace URL: display tracking number only. */
    }
    return undefined;
}
