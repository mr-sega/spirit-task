<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Package } from '@lucide/vue';
</script>
<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b bg-card">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5"
            >
                <Link
                    href="/orders"
                    class="flex items-center gap-3 font-semibold"
                    ><Package class="size-6 text-emerald-600" /> MarketHub
                    <span class="text-muted-foreground">/ Заказы</span></Link
                >
                <span class="text-xs text-muted-foreground"
                    >Кабинет магазина</span
                >
            </div>
        </header>
        <main class="mx-auto max-w-6xl space-y-6 px-5 py-8"><slot /></main>
        <footer
            class="mx-auto max-w-6xl px-5 pb-6 text-xs text-muted-foreground"
        >
            Данные маркетплейса · Только просмотр · Время UTC
        </footer>
    </div>
</template>
