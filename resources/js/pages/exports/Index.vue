<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    exports: { data: Array<Record<string, any>> };
    allowUnapproved: boolean;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const form = useForm({
    type: 'transaction_table',
    format: 'xlsx',
    from: '',
    to: '',
    status: '',
});
defineOptions({ layout: { breadcrumbs: [{ title: 'Export', href: '#' }] } });
</script>

<template>
    <Head title="Export" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Export</h1>
        <p v-if="allowUnapproved" class="text-sm text-amber-700">
            Workspace ini mengizinkan export data yang belum approved.
        </p>
        <form
            class="grid gap-2 rounded-xl border p-4 md:grid-cols-5"
            @submit.prevent="form.post(`/w/${slug}/exports`)"
        >
            <select
                v-model="form.type"
                class="h-9 rounded-md border bg-background px-2 text-sm"
            >
                <option value="transaction_table">Tabel transaksi</option>
                <option value="accounting_journal">Jurnal akuntansi</option>
            </select>
            <select
                v-model="form.format"
                class="h-9 rounded-md border bg-background px-2 text-sm"
            >
                <option value="xlsx">XLSX</option>
                <option value="csv">CSV</option>
            </select>
            <input
                v-model="form.from"
                type="date"
                class="h-9 rounded-md border px-2 text-sm"
            />
            <input
                v-model="form.to"
                type="date"
                class="h-9 rounded-md border px-2 text-sm"
            />
            <Button :disabled="form.processing">Buat export</Button>
        </form>
        <div class="divide-y rounded-xl border">
            <div
                v-for="row in exports.data"
                :key="row.id"
                class="flex items-center justify-between p-3 text-sm"
            >
                <div>
                    {{ row.type }} · {{ row.format }} ·
                    {{ row.row_count ?? 0 }} baris
                </div>
                <div class="flex items-center gap-3">
                    <StatusBadge :status="row.status" />
                    <a
                        v-if="row.status === 'READY'"
                        class="underline"
                        :href="`/w/${slug}/exports/${row.id}/download`"
                        >Unduh</a
                    >
                </div>
            </div>
        </div>
    </div>
</template>
