<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, reactive } from 'vue';
import ConfidenceBadge from '@/components/ConfidenceBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    transaction: Record<string, any>;
    document: Record<string, any> | null;
    accounts: Array<{ id: string; code: string; name: string }>;
    vendors: Array<{ id: string; name: string }>;
    nextId?: string | null;
}>();

const page = usePage();
const slug = computed(() => page.props.currentWorkspace?.slug);
const extraction = computed(
    () => props.document?.latest_extraction ?? props.document?.latestExtraction,
);
const confidence = computed(
    () => extraction.value?.normalized_data?.confidence ?? {},
);
const form = reactive({
    vendor_id: props.transaction.vendor_id ?? '',
    transaction_date: props.transaction.transaction_date,
    reference_number: props.transaction.reference_number ?? '',
    description: props.transaction.description ?? '',
    subtotal: props.transaction.subtotal,
    discount_amount: props.transaction.discount_amount,
    tax_amount: props.transaction.tax_amount,
    service_charge: props.transaction.service_charge,
    total_amount: props.transaction.total_amount,
    note: '',
    remember_mapping: true,
    complete: false,
    merchant_name:
        props.document?.vendor?.name ??
        extraction.value?.normalized_data?.merchant?.name ??
        '',
    document_number: props.transaction.reference_number ?? '',
    entries: (props.transaction.entries ?? []).map((entry: any) => ({
        account_id: entry.account_id,
        entry_type: entry.entry_type,
        amount: entry.amount,
        description: entry.description ?? '',
    })),
});

function confidenceLabel(field: string): string {
    const value = Number(confidence.value?.[field] ?? 0);

    if (!value) {
        return '';
    }

    if (value >= 0.9) {
        return 'Tinggi';
    }

    if (value >= 0.75) {
        return 'Sedang';
    }

    return 'Rendah';
}

function addLine() {
    form.entries.push({
        account_id: '',
        entry_type: 'DEBIT',
        amount: 0,
        description: '',
    });
}
function removeLine(index: number) {
    form.entries.splice(index, 1);
}
function submit(complete: boolean) {
    form.complete = complete;
    router.post(`/w/${slug.value}/review/${props.transaction.id}`, form);
}

function onKey(event: KeyboardEvent) {
    const tag = (event.target as HTMLElement | null)?.tagName;

    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
        return;
    }

    if (event.key === 'a' || event.key === 'A') {
        submit(true);
    }

    if ((event.key === 'n' || event.key === 'N') && props.nextId) {
        router.visit(`/w/${slug.value}/review/${props.nextId}`);
    }
}

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

defineOptions({
    layout: { breadcrumbs: [{ title: 'Review', href: '#' }] },
});
</script>

<template>
    <Head title="Review transaksi" />
    <div class="grid flex-1 gap-4 p-4 lg:grid-cols-2">
        <div class="overflow-hidden rounded-xl border bg-muted/20">
            <img
                v-if="document"
                :src="`/w/${slug}/documents/${document.id}/preview`"
                class="max-h-[80vh] w-full object-contain"
            />
        </div>
        <form class="flex flex-col gap-3" @submit.prevent="submit(false)">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-lg font-semibold">Koreksi hasil AI</h1>
                <ConfidenceBadge :value="extraction?.confidence" />
            </div>
            <div v-if="document?.flags?.length" class="flex flex-wrap gap-1">
                <span
                    v-for="flag in document.flags"
                    :key="flag"
                    class="rounded bg-amber-100 px-2 py-0.5 text-xs"
                    >{{ flag }}</span
                >
            </div>
            <p class="text-xs text-muted-foreground">
                Pintasan: A selesai review, N dokumen berikutnya.
            </p>
            <div class="grid gap-2">
                <Label
                    >Vendor
                    <span class="text-xs text-muted-foreground">{{
                        confidenceLabel('merchant')
                    }}</span></Label
                >
                <select
                    v-model="form.vendor_id"
                    class="h-9 rounded-md border bg-background px-2 text-sm"
                >
                    <option value="">-</option>
                    <option
                        v-for="vendor in vendors"
                        :key="vendor.id"
                        :value="vendor.id"
                    >
                        {{ vendor.name }}
                    </option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="grid gap-2">
                    <Label
                        >Tanggal
                        <span class="text-xs text-muted-foreground">{{
                            confidenceLabel('date')
                        }}</span></Label
                    >
                    <Input v-model="form.transaction_date" type="date" />
                </div>
                <div class="grid gap-2">
                    <Label>Nomor</Label
                    ><Input v-model="form.reference_number" />
                </div>
            </div>
            <div class="grid gap-2">
                <Label>Deskripsi</Label><Input v-model="form.description" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="grid gap-2">
                    <Label>Subtotal</Label><Input v-model="form.subtotal" />
                </div>
                <div class="grid gap-2">
                    <Label>Pajak</Label>
                    <Input v-model="form.tax_amount" />
                </div>
                <div class="grid gap-2">
                    <Label>Diskon</Label
                    ><Input v-model="form.discount_amount" />
                </div>
                <div class="grid gap-2">
                    <Label
                        >Total
                        <span class="text-xs text-muted-foreground">{{
                            confidenceLabel('total')
                        }}</span></Label
                    >
                    <Input v-model="form.total_amount" />
                </div>
            </div>
            <div class="flex items-center justify-between">
                <h2 class="font-medium">Jurnal</h2>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="addLine"
                    >Tambah baris</Button
                >
            </div>
            <div
                v-for="(entry, index) in form.entries"
                :key="index"
                class="grid grid-cols-12 gap-2"
            >
                <select
                    v-model="entry.account_id"
                    class="col-span-6 h-9 rounded-md border bg-background px-2 text-sm"
                >
                    <option
                        v-for="account in accounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.code }} {{ account.name }}
                    </option>
                </select>
                <select
                    v-model="entry.entry_type"
                    class="col-span-2 h-9 rounded-md border bg-background px-2 text-sm"
                >
                    <option value="DEBIT">Debit</option>
                    <option value="CREDIT">Kredit</option>
                </select>
                <Input v-model="entry.amount" class="col-span-3" />
                <Button
                    type="button"
                    variant="ghost"
                    class="col-span-1"
                    @click="removeLine(index)"
                    >x</Button
                >
            </div>
            <label class="flex gap-2 text-sm"
                ><input v-model="form.remember_mapping" type="checkbox" /> Ingat
                pilihan akun untuk vendor ini</label
            >
            <Input v-model="form.note" placeholder="Catatan review" />
            <div class="sticky bottom-0 flex gap-2 bg-background py-3">
                <Button type="button" variant="outline" @click="submit(false)"
                    >Simpan draft</Button
                >
                <Button type="button" @click="submit(true)"
                    >Selesai review</Button
                >
                <Button
                    v-if="nextId"
                    type="button"
                    variant="ghost"
                    @click="router.visit(`/w/${slug}/review/${nextId}`)"
                    >Berikutnya</Button
                >
            </div>
        </form>
    </div>
</template>
