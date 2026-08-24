<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

defineProps<{
    transactions: { data: Array<Record<string, any>>; links: any[] };
    statuses: Array<{ value: string; label: string }>;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const status = ref(
    typeof window === 'undefined'
        ? ''
        : (new URLSearchParams(window.location.search).get('status') ?? ''),
);

function filter() {
    router.get(
        `/w/${slug}/transactions`,
        { status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Transaksi', href: '#' }] } });
</script>

<template>
    <Head title="Transaksi" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Transaksi</h1>
            <select
                v-model="status"
                class="h-9 rounded-md border bg-background px-2 text-sm"
                @change="filter"
            >
                <option value="">Semua status</option>
                <option
                    v-for="item in statuses"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
                </option>
            </select>
        </div>
        <div
            v-if="!transactions.data.length"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada transaksi.
        </div>
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Vendor</th>
                        <th class="p-3">Nilai</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in transactions.data"
                        :key="row.id"
                        class="border-t"
                    >
                        <td class="p-3">{{ row.transaction_date }}</td>
                        <td class="p-3">
                            <Link
                                v-if="row.document_id"
                                class="underline"
                                :href="`/w/${slug}/documents/${row.document_id}`"
                                >{{ row.vendor?.name ?? row.description }}</Link
                            >
                            <span v-else>{{
                                row.vendor?.name ?? row.description
                            }}</span>
                        </td>
                        <td class="p-3">{{ row.total_amount }}</td>
                        <td class="p-3">
                            <StatusBadge :status="row.status" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
