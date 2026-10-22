<script setup lang="ts">
import { statuses } from '@/lib/orders';
import type { OrderStatus } from '@/types/orders';
defineProps<{ status: OrderStatus }>();
const colors: Record<OrderStatus, string> = {
    created: 'bg-amber-100 text-amber-900',
    paid: 'bg-emerald-100 text-emerald-900',
    shipped: 'bg-blue-100 text-blue-900',
    delivered: 'bg-teal-100 text-teal-900',
    cancelled: 'bg-rose-100 text-rose-900',
};
</script>
<template>
    <span
        :class="colors[status]"
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap"
        >{{ statuses[status] }}</span
    >
</template>
