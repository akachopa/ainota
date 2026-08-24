<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    value?: number | string | null;
}>();

const numeric = computed(() => {
    const raw = props.value;

    if (raw === null || raw === undefined || raw === '') {
        return null;
    }

    const parsed = Number(raw);

    return Number.isFinite(parsed) ? parsed : null;
});

const level = computed(() => {
    const value = numeric.value ?? 0;

    if (numeric.value === null) {
        return { label: 'Belum ada', klass: 'bg-muted text-muted-foreground' };
    }

    if (value >= 0.9) {
        return { label: 'Tinggi', klass: 'bg-emerald-100 text-emerald-800' };
    }

    if (value >= 0.75) {
        return { label: 'Sedang', klass: 'bg-amber-100 text-amber-800' };
    }

    return { label: 'Rendah', klass: 'bg-red-100 text-red-800' };
});

const percent = computed(() =>
    numeric.value === null ? null : Math.round(numeric.value * 100),
);
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
        :class="level.klass"
        :title="
            percent === null
                ? 'Confidence AI belum tersedia'
                : `Confidence ${percent}%`
        "
    >
        AI {{ level.label
        }}<template v-if="percent !== null"> {{ percent }}%</template>
    </span>
</template>
