<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ConfidenceBadge from '@/components/ConfidenceBadge.vue';
import StatusBadge from '@/components/StatusBadge.vue';

defineProps<{
    transactions: { data: Array<Record<string, any>>; links: any[] };
    sort: string;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;

function setSort(sort: string) {
    router.get(
        `/w/${slug}/review`,
        { sort },
        { preserveState: true, replace: true },
    );
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Antrian review', href: '#' }] },
});
</script>

<template>
    <Head title="Review" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Antrian review</h1>
            <select
                class="h-9 rounded-md border bg-background px-2 text-sm"
                :value="sort || 'oldest'"
                @change="setSort(($event.target as HTMLSelectElement).value)"
            >
                <option value="oldest">Terlama dulu</option>
                <option value="confidence">Confidence rendah dulu</option>
                <option value="duplicate">Kemungkinan duplikat dulu</option>
                <option value="amount">Nilai tertinggi</option>
            </select>
        </div>
        <div
            v-if="!transactions.data.length"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Tidak ada transaksi yang menunggu review.
        </div>
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Vendor</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Nilai</th>
                        <th class="p-3">AI</th>
                        <th class="p-3">Status</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in transactions.data"
                        :key="row.id"
                        class="border-t"
                    >
                        <td class="p-3">
                            {{ row.vendor?.name ?? '-' }}
                            <div
                                v-if="
                                    row.document?.flags?.includes(
                                        'POSSIBLE_DUPLICATE',
                                    )
                                "
                                class="text-xs text-amber-700"
                            >
                                Kemungkinan duplikat
                            </div>
                        </td>
                        <td class="p-3">{{ row.transaction_date }}</td>
                        <td class="p-3">{{ row.total_amount }}</td>
                        <td class="p-3">
                            <ConfidenceBadge
                                :value="
                                    row.document?.latest_extraction?.confidence
                                "
                            />
                        </td>
                        <td class="p-3">
                            <StatusBadge :status="row.status" />
                        </td>
                        <td class="p-3">
                            <Link
                                class="underline"
                                :href="`/w/${slug}/review/${row.id}`"
                                >Buka</Link
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
