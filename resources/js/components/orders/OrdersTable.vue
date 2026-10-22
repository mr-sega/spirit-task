<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import OrderStatusBadge from '@/components/orders/OrderStatusBadge.vue';
import { dateTime, money, orderUrl } from '@/lib/orders';
import type { OrderSummary } from '@/types/orders';
defineProps<{ orders: OrderSummary[] }>();
</script>
<template>
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-muted/40 text-muted-foreground">
                <tr>
                    <th class="p-4 font-medium">Заказ</th>
                    <th class="p-4 font-medium">Покупатель</th>
                    <th class="p-4 font-medium">Статус</th>
                    <th class="p-4 text-right font-medium">Сумма</th>
                    <th class="p-4 font-medium">Создан · UTC</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="order in orders"
                    :key="order.id"
                    class="hover:bg-muted/30"
                >
                    <td class="p-4">
                        <Link
                            :href="orderUrl(order.id)"
                            class="font-semibold text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-400"
                            >№{{ order.number }}</Link
                        >
                    </td>
                    <td class="p-4">
                        <p>{{ order.customer.name }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ order.customer.email }}
                        </p>
                    </td>
                    <td class="p-4">
                        <OrderStatusBadge :status="order.status" />
                    </td>
                    <td class="p-4 text-right whitespace-nowrap tabular-nums">
                        {{ money(order.total, order.currency) }}
                    </td>
                    <td class="p-4 whitespace-nowrap text-muted-foreground">
                        {{ dateTime(order.created_at) }}
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-if="orders.length === 0" class="px-5 py-16 text-center">
            <p class="font-medium">Заказы не найдены</p>
            <p class="mt-2 text-sm text-muted-foreground">
                Измените фильтры или отправьте тестовый вебхук.
            </p>
        </div>
    </div>
</template>
