<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type DocumentRow = {
    id: string;
    original_filename: string;
    status: string;
    grand_total: string | number | null;
    transaction_date: string | null;
    progress: number;
    vendor?: { name: string } | null;
    uploader?: { name: string } | null;
    updated_at: string;
    flags?: string[];
};

const props = defineProps<{
    documents: {
        data: DocumentRow[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: Record<string, string | boolean | null>;
    canUpload: boolean;
    statuses: Array<{ value: string; label: string }>;
}>();

const page = usePage();
const slug = computed(() => page.props.currentWorkspace?.slug);
const uploading = ref(false);
const message = ref('');
const dragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const q = ref(String(props.filters.q ?? ''));
const status = ref(String(props.filters.status ?? ''));
const from = ref(String(props.filters.from ?? ''));
const to = ref(String(props.filters.to ?? ''));
const duplicates = ref(Boolean(props.filters.duplicates));

function csrf(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function formatAmount(value: string | number | null): string {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function applyFilters() {
    if (!slug.value) {
        return;
    }

    router.get(
        `/w/${slug.value}/documents`,
        {
            q: q.value || undefined,
            status: status.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
            duplicates: duplicates.value ? 1 : undefined,
        },
        { preserveState: true, replace: true },
    );
}

async function uploadFiles(files: FileList | File[]) {
    if (!slug.value) {
        return;
    }

    const list = Array.from(files);

    if (!list.length) {
        return;
    }

    uploading.value = true;
    const batchRes = await fetch(`/w/${slug.value}/documents/batch`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ total: list.length }),
    });
    const batchJson = await batchRes.json();
    const batchId = batchJson.batch?.id;

    await Promise.all(
        list.map(async (file) => {
            const form = new FormData();
            form.append('file', file);

            if (batchId) {
                form.append('batch_id', batchId);
            }

            await fetch(`/w/${slug.value}/documents`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });
        }),
    );

    uploading.value = false;
    message.value = `${list.length} file diunggah. Pemrosesan berjalan di latar belakang.`;
    router.reload({ only: ['documents'] });
}

function onDrop(event: DragEvent) {
    dragging.value = false;

    if (event.dataTransfer?.files) {
        uploadFiles(event.dataTransfer.files);
    }
}

let timer: number | undefined;
onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('upload') === '1') {
        fileInput.value?.click();
    }

    timer = window.setInterval(() => {
        const processing = props.documents.data.some((doc) =>
            [
                'QUEUED',
                'DUPLICATE_CHECKING',
                'AI_PROCESSING',
                'UPLOADING',
            ].includes(doc.status),
        );

        if (processing) {
            router.reload({ only: ['documents'] });
        }
    }, 4000);
});
onUnmounted(() => timer && window.clearInterval(timer));

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dokumen', href: '#' }] },
});
</script>

<template>
    <Head title="Dokumen" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Dokumen</h1>
            <div v-if="canUpload" class="flex gap-2">
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    multiple
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    @change="
                        (e) =>
                            (e.target as HTMLInputElement).files &&
                            uploadFiles((e.target as HTMLInputElement).files!)
                    "
                />
                <Button :disabled="uploading" @click="fileInput?.click()">
                    {{ uploading ? 'Mengunggah…' : 'Unggah nota' }}
                </Button>
            </div>
        </div>
        <p v-if="message" class="text-sm text-emerald-700">{{ message }}</p>
        <div
            v-if="canUpload"
            class="rounded-xl border-2 border-dashed p-6 text-center text-sm text-muted-foreground transition"
            :class="
                dragging
                    ? 'border-primary bg-primary/5'
                    : 'border-muted-foreground/30'
            "
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            Seret foto atau PDF ke sini, atau klik Unggah nota. HTTP tidak
            menunggu AI selesai.
        </div>
        <form
            class="flex flex-wrap items-end gap-2"
            @submit.prevent="applyFilters"
        >
            <Input
                v-model="q"
                placeholder="Cari vendor/nama file"
                class="w-56"
            />
            <select
                v-model="status"
                class="h-9 rounded-md border bg-background px-2 text-sm"
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
            <Input v-model="from" type="date" class="w-40" />
            <Input v-model="to" type="date" class="w-40" />
            <label class="flex items-center gap-2 text-sm">
                <input v-model="duplicates" type="checkbox" />
                Duplikat saja
            </label>
            <Button type="submit" variant="outline">Filter</Button>
        </form>
        <div
            v-if="!documents.data.length"
            class="rounded-xl border p-8 text-center text-sm text-muted-foreground"
        >
            Belum ada dokumen. Unggah nota untuk memulai.
        </div>
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Dokumen</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Vendor</th>
                        <th class="p-3">Nilai</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Uploader</th>
                        <th class="p-3">Diperbarui</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="doc in documents.data"
                        :key="doc.id"
                        class="border-t"
                    >
                        <td class="p-3">
                            <Link
                                :href="`/w/${slug}/documents/${doc.id}`"
                                class="hover:underline"
                                >{{ doc.original_filename }}</Link
                            >
                            <div
                                v-if="
                                    [
                                        'QUEUED',
                                        'DUPLICATE_CHECKING',
                                        'AI_PROCESSING',
                                    ].includes(doc.status)
                                "
                                class="mt-1 h-1.5 w-32 overflow-hidden rounded bg-muted"
                            >
                                <div
                                    class="h-full bg-primary"
                                    :style="{ width: `${doc.progress || 10}%` }"
                                />
                            </div>
                        </td>
                        <td class="p-3">{{ doc.transaction_date ?? '-' }}</td>
                        <td class="p-3">{{ doc.vendor?.name ?? '-' }}</td>
                        <td class="p-3">{{ formatAmount(doc.grand_total) }}</td>
                        <td class="p-3">
                            <StatusBadge :status="doc.status" />
                        </td>
                        <td class="p-3">{{ doc.uploader?.name ?? '-' }}</td>
                        <td class="p-3 text-muted-foreground">
                            {{ doc.updated_at }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a
                v-for="link in documents.links"
                :key="link.label"
                :href="link.url ?? '#'"
                :class="link.active ? 'font-semibold' : 'text-muted-foreground'"
                v-html="link.label"
            />
        </div>
    </div>
</template>
