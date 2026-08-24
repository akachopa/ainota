<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    links: Array<Record<string, any>>;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const origin = computed(() =>
    typeof window === 'undefined' ? '' : window.location.origin,
);
const form = useForm({
    name: 'Link klien',
    expires_at: '',
    max_uploads: 50,
    require_uploader_name: true,
    pin: '',
});
defineOptions({
    layout: { breadcrumbs: [{ title: 'Link unggah', href: '#' }] },
});
</script>

<template>
    <Head title="Link unggah" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Secure upload link</h1>
        <form
            class="grid gap-2 rounded-xl border p-4 md:grid-cols-2"
            @submit.prevent="form.post(`/w/${slug}/upload-links`)"
        >
            <Input v-model="form.name" placeholder="Nama link" />
            <Input v-model="form.max_uploads" type="number" />
            <Input v-model="form.pin" placeholder="PIN opsional" />
            <Button :disabled="form.processing">Buat link</Button>
        </form>
        <div
            v-if="!links.length"
            class="rounded-xl border p-6 text-sm text-muted-foreground"
        >
            Belum ada link unggah.
        </div>
        <div v-else class="divide-y rounded-xl border">
            <div v-for="link in links" :key="link.id" class="p-3 text-sm">
                <div class="font-medium">{{ link.name }}</div>
                <div class="break-all text-muted-foreground">
                    {{ `${origin}/u/${link.public_token}` }}
                </div>
                <div>
                    {{ link.upload_count }}/{{ link.max_uploads ?? '∞' }}
                    unggahan
                </div>
            </div>
        </div>
    </div>
</template>
