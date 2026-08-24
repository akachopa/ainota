<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

const label = computed(() => {
    const map: Record<string, string> = {
        UPLOADING: 'Mengunggah',
        QUEUED: 'Antrian',
        DUPLICATE_CHECKING: 'Cek duplikasi',
        DUPLICATE_EXACT: 'Duplikat persis',
        DUPLICATE_POSSIBLE: 'Kemungkinan duplikat',
        AI_PROCESSING: 'AI memproses',
        AI_PROCESSED: 'AI selesai',
        VALIDATION_FAILED: 'Validasi gagal',
        NEEDS_REVIEW: 'Perlu ditinjau',
        REVIEWED: 'Ditinjau',
        WAITING_APPROVAL: 'Menunggu persetujuan',
        APPROVED: 'Disetujui',
        REJECTED: 'Ditolak',
        FAILED: 'Gagal',
        ARCHIVED: 'Diarsipkan',
        DRAFT: 'Draft',
        READY: 'Siap',
        PROCESSING: 'Diproses',
        EXPIRED: 'Kedaluwarsa',
    };

    return map[props.status] ?? props.status;
});

const klass = computed(() => {
    const map: Record<string, string> = {
        QUEUED: 'bg-slate-100 text-slate-700',
        AI_PROCESSING: 'bg-blue-100 text-blue-800',
        NEEDS_REVIEW: 'bg-amber-100 text-amber-800',
        WAITING_APPROVAL: 'bg-indigo-100 text-indigo-800',
        APPROVED: 'bg-emerald-100 text-emerald-800',
        FAILED: 'bg-red-100 text-red-800',
        DUPLICATE_EXACT: 'bg-orange-100 text-orange-800',
        DUPLICATE_POSSIBLE: 'bg-orange-100 text-orange-800',
        REJECTED: 'bg-red-100 text-red-800',
        READY: 'bg-emerald-100 text-emerald-800',
    };

    return map[props.status] ?? 'bg-muted text-muted-foreground';
});
</script>

<template>
    <span
        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
        :class="klass"
    >
        {{ label }}
    </span>
</template>
