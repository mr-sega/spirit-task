<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { statuses } from '@/lib/orders';
const props = defineProps<{
    filters: {
        status?: string | null;
        email?: string | null;
        per_page?: number;
    };
}>();
const form = useForm({
    status: props.filters.status ?? '',
    email: props.filters.email ?? '',
    per_page: props.filters.per_page ?? 20,
});
watch(
    () => props.filters,
    (filters) => {
        form.status = filters.status ?? '';
        form.email = filters.email ?? '';
        form.per_page = filters.per_page ?? 20;
    },
);
function submit() {
    form.get('/orders', { preserveState: true, preserveScroll: true });
}
function reset() {
    form.status = '';
    form.email = '';
    form.per_page = 20;
    submit();
}
</script>
<template>
    <form
        class="grid gap-4 rounded-xl border bg-card p-5 sm:grid-cols-[1fr_2fr_auto_auto] sm:items-end"
        @submit.prevent="submit"
    >
        <div>
            <label for="status" class="mb-2 block text-sm font-medium"
                >Статус</label
            ><select
                id="status"
                v-model="form.status"
                class="h-10 w-full rounded-md border bg-background px-3"
            >
                <option value="">Все статусы</option>
                <option
                    v-for="(label, value) in statuses"
                    :key="value"
                    :value="value"
                >
                    {{ label }}
                </option>
            </select>
            <p v-if="form.errors.status" class="mt-1 text-sm text-destructive">
                {{ form.errors.status }}
            </p>
        </div>
        <div>
            <label for="email" class="mb-2 block text-sm font-medium"
                >Email покупателя</label
            ><input
                id="email"
                v-model="form.email"
                type="text"
                maxlength="255"
                placeholder="Полный email, без учёта регистра"
                class="h-10 w-full rounded-md border bg-background px-3"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-destructive">
                {{ form.errors.email }}
            </p>
        </div>
        <button
            :disabled="form.processing"
            class="h-10 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground disabled:opacity-50"
        >
            Найти
        </button>
        <button
            type="button"
            :disabled="form.processing"
            class="h-10 rounded-md border px-4 text-sm disabled:opacity-50"
            @click="reset"
        >
            Сбросить
        </button>
    </form>
</template>
