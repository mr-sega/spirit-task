<script setup lang="ts">
import OrderStatusBadge from '@/components/orders/OrderStatusBadge.vue';
import { dateTime } from '@/lib/orders';
import type { Order } from '@/types/orders';
defineProps<{ history: Order['history'] }>();
</script>
<template>
    <section class="rounded-xl border bg-card p-5">
        <h2 class="mb-5 font-semibold">История статусов</h2>
        <ol class="space-y-4">
            <li
                v-for="(entry, index) in history"
                :key="index"
                class="flex flex-wrap items-center gap-4"
            >
                <span
                    class="size-2 rounded-full bg-emerald-500"
                /><OrderStatusBadge :status="entry.status" /><time
                    :datetime="entry.at"
                    class="text-sm text-muted-foreground"
                    >{{ dateTime(entry.at) }}</time
                >
            </li>
        </ol>
        <p v-if="history.length === 0" class="text-sm text-muted-foreground">
            История пока пуста.
        </p>
    </section>
</template>
