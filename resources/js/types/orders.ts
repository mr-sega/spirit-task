export type OrderStatus =
    | 'created'
    | 'paid'
    | 'shipped'
    | 'delivered'
    | 'cancelled';
export interface OrderSummary {
    id: string;
    number: string;
    status: OrderStatus;
    customer: { name: string; email: string; phone?: string | null };
    total: string;
    currency: string;
    created_at: string;
    updated_at: string;
}
export interface Order extends OrderSummary {
    items: {
        sku: string;
        name: string;
        quantity: number;
        price: string;
        subtotal: string;
    }[];
    delivery_price: string;
    discount: string;
    shipment: {
        carrier: string;
        tracking_number: string;
        tracking_url: string;
    } | null;
    paid_at: string | null;
    cancelled_at: string | null;
    cancel_reason: string | null;
    history: { status: OrderStatus; at: string }[];
}
export interface OrderPage {
    data: OrderSummary[];
    links: { prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; total: number };
}
