<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    cards: Array<{
        id: string;
        name: string;
        slug: string;
        role: string;
        documents: number;
        need_review: number;
        waiting_approval: number;
        processing: number;
    }>;
    stats: {
        workspaces: number;
        documents_today: number;
        need_review: number;
        waiting_approval: number;
        processing: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
    },
});
</script>

<template>
    <Head title="Dashboard akun" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="grid gap-4 md:grid-cols-5">
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm"
                        >Workspace</CardTitle
                    ></CardHeader
                ><CardContent class="text-2xl font-semibold">{{
                    stats.workspaces
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm"
                        >Dokumen hari ini</CardTitle
                    ></CardHeader
                ><CardContent class="text-2xl font-semibold">{{
                    stats.documents_today
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm"
                        >Perlu ditinjau</CardTitle
                    ></CardHeader
                ><CardContent class="text-2xl font-semibold">{{
                    stats.need_review
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm"
                        >Menunggu persetujuan</CardTitle
                    ></CardHeader
                ><CardContent class="text-2xl font-semibold">{{
                    stats.waiting_approval
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm"
                        >Sedang diproses</CardTitle
                    ></CardHeader
                ><CardContent class="text-2xl font-semibold">{{
                    stats.processing
                }}</CardContent></Card
            >
        </div>

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium">Workspace Anda</h2>
            <Link href="/workspaces/create" class="text-sm underline"
                >Buat workspace</Link
            >
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="card in cards"
                :key="card.id"
                :href="`/w/${card.slug}`"
                class="rounded-xl border p-4 hover:bg-accent"
            >
                <div class="font-medium">{{ card.name }}</div>
                <div class="mt-1 text-xs text-muted-foreground uppercase">
                    {{ card.role }}
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                    <div>{{ card.documents }} dokumen</div>
                    <div>{{ card.need_review }} review</div>
                    <div>{{ card.waiting_approval }} approval</div>
                </div>
            </Link>
        </div>
    </div>
</template>
