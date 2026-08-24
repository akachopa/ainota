<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    transactions: { data: Array<Record<string, any>> };
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;

function reject(id: string) {
    const note = window.prompt('Alasan pengembalian ke reviewer');

    if (!note) {
        return;
    }

    router.post(`/w/${slug}/transactions/${id}/reject`, { note });
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Persetujuan', href: '#' }] },
});
</script>

<template>
    <Head title="Persetujuan" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Antrian persetujuan</h1>
        <div
            v-if="!transactions.data.length"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Tidak ada transaksi menunggu persetujuan.
        </div>
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Vendor</th>
                        <th class="p-3">Nilai</th>
                        <th class="p-3">Reviewer</th>
                        <th class="p-3">Jurnal</th>
                        <th class="p-3">Peringatan</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in transactions.data"
                        :key="row.id"
                        class="border-t"
                    >
                        <td class="p-3">{{ row.transaction_date }}</td>
                        <td class="p-3">{{ row.vendor?.name ?? '-' }}</td>
                        <td class="p-3">{{ row.total_amount }}</td>
                        <td class="p-3">{{ row.reviewer?.name ?? '-' }}</td>
                        <td class="p-3">
                            <div v-for="entry in row.entries" :key="entry.id">
                                {{ entry.entry_type }} {{ entry.account?.code }}
                                {{ entry.amount }}
                            </div>
                        </td>
                        <td class="p-3 text-xs text-amber-700">
                            {{ (row.document?.flags ?? []).join(', ') || '-' }}
                        </td>
                        <td class="flex gap-2 p-3">
                            <Button
                                size="sm"
                                @click="
                                    router.post(
                                        `/w/${slug}/transactions/${row.id}/approve`,
                                    )
                                "
                                >Setujui</Button
                            >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="reject(row.id)"
                                >Tolak</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
