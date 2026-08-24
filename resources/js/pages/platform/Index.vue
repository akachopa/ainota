<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    users: Array<Record<string, any>>;
    workspaces: Array<Record<string, any>>;
    plans: Array<Record<string, any>>;
    ai_usage: Record<string, any>;
    documents: number;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Platform admin', href: '/platform' }] },
});
</script>

<template>
    <Head title="Platform admin" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Platform</h1>
        <p class="text-sm text-muted-foreground">
            Ringkasan sistem. Konten dokumen klien tidak ditampilkan di sini.
        </p>
        <div class="grid gap-4 text-sm md:grid-cols-4">
            <div class="rounded-xl border p-4">Dokumen: {{ documents }}</div>
            <div class="rounded-xl border p-4">
                AI sukses: {{ ai_usage.success }}
            </div>
            <div class="rounded-xl border p-4">
                AI gagal: {{ ai_usage.failed }}
            </div>
            <div class="rounded-xl border p-4">Biaya: {{ ai_usage.cost }}</div>
        </div>
        <h2 class="font-medium">Workspace terbaru</h2>
        <div class="divide-y rounded-xl border text-sm">
            <div v-for="ws in workspaces" :key="ws.id" class="p-3">
                {{ ws.name }} · {{ ws.slug }}
            </div>
        </div>
        <h2 class="font-medium">Paket</h2>
        <div class="divide-y rounded-xl border text-sm">
            <div v-for="plan in plans" :key="plan.id" class="p-3">
                {{ plan.name }} · {{ plan.pages_per_month }} halaman/bulan
            </div>
        </div>
    </div>
</template>
