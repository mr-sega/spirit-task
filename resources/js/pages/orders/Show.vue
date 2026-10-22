<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrderDetails from '@/components/orders/OrderDetails.vue';
import OrderHistory from '@/components/orders/OrderHistory.vue';
import OrderItems from '@/components/orders/OrderItems.vue';
import OrderStatusBadge from '@/components/orders/OrderStatusBadge.vue';
import type { Order } from '@/types/orders';
defineProps<{ order: { data: Order } }>();
</script>
<template>
    <Head :title="`Заказ №${order.data.number}`" />
    <Link
        href="/orders"
        class="inline-block text-sm text-muted-foreground hover:text-foreground"
        >← Все заказы</Link
    >
    <div class="flex flex-wrap items-center gap-4">
        <h1 class="text-3xl font-semibold tracking-tight break-all">
            Заказ №{{ order.data.number }}
        </h1>
        <OrderStatusBadge :status="order.data.status" />
    </div>
    <div class="grid items-start gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            <OrderItems :order="order.data" /><OrderHistory
                :history="order.data.history"
            />
        </div>
        <OrderDetails :order="order.data" />
    </div>
</template>
