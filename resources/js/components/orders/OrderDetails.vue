<script setup lang="ts">
import { dateTime, trackingUrl } from '@/lib/orders';
import type { Order } from '@/types/orders';
defineProps<{ order: Order }>();
</script>
<template>
    <div class="space-y-5">
        <section class="space-y-3 rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Покупатель</h2>
            <p class="text-sm">{{ order.customer.name }}</p>
            <p class="text-sm break-all text-muted-foreground">
                {{ order.customer.email }}
            </p>
            <p class="text-sm text-muted-foreground">
                {{ order.customer.phone || 'Телефон не указан' }}
            </p>
        </section>
        <section class="space-y-3 rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Доставка</h2>
            <template v-if="order.shipment"
                ><p class="text-sm">{{ order.shipment.carrier }}</p>
                <p class="text-sm break-all text-muted-foreground">
                    {{ order.shipment.tracking_number }}
                </p>
                <a
                    v-if="trackingUrl(order.shipment.tracking_url)"
                    :href="trackingUrl(order.shipment.tracking_url)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm text-emerald-700 underline dark:text-emerald-400"
                    >Отследить отправление ↗</a
                ></template
            >
            <p v-else class="text-sm text-muted-foreground">
                Данных об отправке пока нет.
            </p>
        </section>
        <section
            v-if="order.cancelled_at || order.status === 'cancelled'"
            class="space-y-3 rounded-xl border border-rose-200 bg-card p-5"
        >
            <h2 class="font-semibold">Отмена</h2>
            <p class="text-sm">
                {{ order.cancel_reason || 'Причина не указана' }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ dateTime(order.cancelled_at) }}
            </p>
        </section>
        <section class="space-y-3 rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Даты · UTC</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-muted-foreground">Создан</dt>
                    <dd>{{ dateTime(order.created_at) }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Обновлён</dt>
                    <dd>{{ dateTime(order.updated_at) }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Оплачен</dt>
                    <dd>{{ dateTime(order.paid_at) }}</dd>
                </div>
            </dl>
        </section>
    </div>
</template>
