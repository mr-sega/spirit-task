<script setup lang="ts">
import { money } from '@/lib/orders';
import type { Order } from '@/types/orders';
defineProps<{ order: Order }>();
</script>
<template>
    <section class="overflow-hidden rounded-xl border bg-card">
        <h2 class="border-b p-5 font-semibold">Состав заказа</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/40 text-muted-foreground">
                    <tr>
                        <th class="p-4 font-medium">Товар</th>
                        <th class="p-4 text-right font-medium">Цена</th>
                        <th class="p-4 text-right font-medium">Кол-во</th>
                        <th class="p-4 text-right font-medium">Сумма</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="(item, index) in order.items" :key="index">
                        <td class="p-4">
                            <p>{{ item.name }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ item.sku }}
                            </p>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap">
                            {{ money(item.price, order.currency) }}
                        </td>
                        <td class="p-4 text-right">{{ item.quantity }}</td>
                        <td class="p-4 text-right whitespace-nowrap">
                            {{ money(item.subtotal, order.currency) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <dl class="space-y-3 border-t p-5 text-sm">
            <div class="flex justify-between">
                <dt>Доставка</dt>
                <dd>{{ money(order.delivery_price, order.currency) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Скидка</dt>
                <dd>{{ money(order.discount, order.currency) }}</dd>
            </div>
            <div
                class="flex justify-between border-t pt-3 text-base font-semibold"
            >
                <dt>Итого</dt>
                <dd>{{ money(order.total, order.currency) }}</dd>
            </div>
        </dl>
    </section>
</template>
