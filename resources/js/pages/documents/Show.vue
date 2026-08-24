<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConfidenceBadge from '@/components/ConfidenceBadge.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    document: Record<string, any>;
    accounts: Array<{ id: string; code: string; name: string }>;
    vendors: Array<{ id: string; name: string }>;
    canReview: boolean;
    canApprove: boolean;
}>();

const page = usePage();
const slug = computed(() => page.props.currentWorkspace?.slug);
const previewUrl = computed(
    () => `/w/${slug.value}/documents/${props.document.id}/preview`,
);
const tx = computed(() => props.document.transaction);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dokumen', href: '#' }] },
});
</script>

<template>
    <Head :title="document.original_filename" />
    <div class="grid flex-1 gap-4 p-4 lg:grid-cols-2">
        <div
            class="min-h-[420px] overflow-hidden rounded-xl border bg-muted/30"
        >
            <img
                :src="previewUrl"
                :alt="document.original_filename"
                class="max-h-[80vh] w-full object-contain"
            />
        </div>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold">
                    {{ document.original_filename }}
                </h1>
                <div class="flex items-center gap-2">
                    <ConfidenceBadge
                        :value="document.latest_extraction?.confidence"
                    />
                    <StatusBadge :status="document.status" />
                </div>
            </div>
            <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-muted-foreground">Vendor</dt>
                <dd>{{ document.vendor?.name ?? '-' }}</dd>
                <dt class="text-muted-foreground">Tanggal</dt>
                <dd>{{ document.transaction_date ?? '-' }}</dd>
                <dt class="text-muted-foreground">Total</dt>
                <dd>{{ document.grand_total ?? '-' }}</dd>
                <dt class="text-muted-foreground">Tipe</dt>
                <dd>{{ document.document_type ?? '-' }}</dd>
            </dl>
            <div v-if="document.flags?.length" class="flex flex-wrap gap-1">
                <span
                    v-for="flag in document.flags"
                    :key="flag"
                    class="rounded bg-amber-100 px-2 py-0.5 text-xs"
                    >{{ flag }}</span
                >
            </div>
            <div
                v-if="document.possible_duplicate"
                class="rounded-md border p-3 text-sm"
            >
                Kemungkinan duplikat dari
                <Link
                    class="underline"
                    :href="`/w/${slug}/documents/${document.possible_duplicate.id}`"
                >
                    {{ document.possible_duplicate.original_filename }}
                </Link>
                <div class="mt-2 flex gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="
                            router.post(
                                `/w/${slug}/documents/${document.id}/duplicate`,
                                { resolution: 'keep_both' },
                            )
                        "
                        >Simpan keduanya</Button
                    >
                    <Button
                        size="sm"
                        variant="outline"
                        @click="
                            router.post(
                                `/w/${slug}/documents/${document.id}/duplicate`,
                                { resolution: 'process_anyway' },
                            )
                        "
                        >Proses tetap</Button
                    >
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="
                            router.post(
                                `/w/${slug}/documents/${document.id}/duplicate`,
                                { resolution: 'mark_duplicate' },
                            )
                        "
                        >Tandai duplikat</Button
                    >
                </div>
            </div>
            <div v-if="tx">
                <h2 class="mb-2 font-medium">Draft jurnal</h2>
                <table class="w-full text-sm">
                    <tr v-for="entry in tx.entries" :key="entry.id">
                        <td class="py-1">
                            {{ entry.account?.code }} {{ entry.account?.name }}
                        </td>
                        <td class="py-1">{{ entry.entry_type }}</td>
                        <td class="py-1 text-right">{{ entry.amount }}</td>
                    </tr>
                </table>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="canReview && tx" as-child
                    ><Link :href="`/w/${slug}/review/${tx.id}`"
                        >Buka review</Link
                    ></Button
                >
                <Button
                    variant="outline"
                    @click="
                        router.post(
                            `/w/${slug}/documents/${document.id}/reprocess`,
                        )
                    "
                    >Proses ulang</Button
                >
                <Button
                    v-if="canApprove && tx?.status === 'WAITING_APPROVAL'"
                    @click="
                        router.post(`/w/${slug}/transactions/${tx.id}/approve`)
                    "
                    >Setujui</Button
                >
            </div>
        </div>
    </div>
</template>
