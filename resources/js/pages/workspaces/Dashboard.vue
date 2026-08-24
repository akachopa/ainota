<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    counts: Record<string, number>;
    recent: Array<Record<string, any>>;
    canUpload: boolean;
}>();

const page = usePage();
const slug = page.props.currentWorkspace?.slug;

const labels: Record<string, string> = {
    uploads_today: 'Unggahan hari ini',
    processing: 'Diproses',
    duplicates: 'Duplikat',
    need_review: 'Perlu ditinjau',
    waiting_approval: 'Menunggu persetujuan',
    approved_month: 'Disetujui bulan ini',
    failed: 'Gagal',
};

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard workspace', href: '#' }] },
});
</script>

<template>
    <Head title="Dashboard workspace" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">
                {{ page.props.currentWorkspace?.name }}
            </h1>
            <Button v-if="canUpload" as-child>
                <Link :href="`/w/${slug}/documents?upload=1`">Unggah nota</Link>
            </Button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="(value, key) in counts" :key="key">
                <CardHeader
                    ><CardTitle class="text-sm">{{
                        labels[String(key)] ?? String(key).replaceAll('_', ' ')
                    }}</CardTitle></CardHeader
                >
                <CardContent class="text-2xl font-semibold">{{
                    value
                }}</CardContent>
            </Card>
        </div>
        <div>
            <h2 class="mb-3 font-medium">Aktivitas terbaru</h2>
            <div
                v-if="!recent.length"
                class="rounded-xl border p-6 text-sm text-muted-foreground"
            >
                Belum ada aktivitas.
            </div>
            <div v-else class="divide-y rounded-xl border">
                <div
                    v-for="doc in recent"
                    :key="doc.id"
                    class="flex items-center justify-between p-3 text-sm"
                >
                    <Link
                        :href="`/w/${slug}/documents/${doc.id}`"
                        class="hover:underline"
                        >{{ doc.original_filename }}</Link
                    >
                    <StatusBadge :status="doc.status" />
                </div>
            </div>
        </div>
    </div>
</template>
