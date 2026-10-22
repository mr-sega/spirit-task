<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrderFilters from '@/components/orders/OrderFilters.vue';
import OrdersTable from '@/components/orders/OrdersTable.vue';
import type { OrderPage } from '@/types/orders';
defineProps<{
    orders: OrderPage;
    filters: {
        status?: string | null;
        email?: string | null;
        per_page?: number;
    };
}>();
</script>
<template>
    <Head title="Заказы" />
    <div>
        <p
            class="mb-2 text-xs font-medium tracking-widest text-muted-foreground uppercase"
        >
            Маркетплейс
        </p>
        <h1 class="text-3xl font-semibold tracking-tight">Заказы</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Состояние заказов, покупатели и история изменений.
        </p>
    </div>
    <OrderFilters :filters="filters" />
    <div class="flex items-center justify-between text-sm">
        <span
            >Найдено: <strong>{{ orders.meta.total }}</strong></span
        ><span class="text-muted-foreground"
            >Страница {{ orders.meta.current_page }} из
            {{ orders.meta.last_page }}</span
        >
    </div>
    <OrdersTable :orders="orders.data" />
    <nav aria-label="Страницы заказов" class="flex justify-end gap-3">
        <Link
            v-if="orders.links.prev"
            :href="orders.links.prev"
            class="rounded-md border px-4 py-2 text-sm"
            >← Назад</Link
        ><span
            v-else
            aria-disabled="true"
            class="rounded-md border px-4 py-2 text-sm text-muted-foreground opacity-50"
            >← Назад</span
        >
        <Link
            v-if="orders.links.next"
            :href="orders.links.next"
            class="rounded-md border px-4 py-2 text-sm"
            >Далее →</Link
        ><span
            v-else
            aria-disabled="true"
            class="rounded-md border px-4 py-2 text-sm text-muted-foreground opacity-50"
            >Далее →</span
        >
    </nav>
</template>
