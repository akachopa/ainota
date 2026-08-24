<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    token: string;
    link: {
        name: string;
        workspace: string;
        require_uploader_name: boolean;
        require_pin: boolean;
        remaining: number | null;
    };
}>();

const uploaderName = ref('');
const pin = ref('');
const message = ref('');
const error = ref('');
const uploading = ref(false);
const dragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function csrf(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function upload(files: FileList | File[] | null) {
    if (!files || (files as FileList).length === 0) {
        return;
    }

    if (props.link.require_uploader_name && !uploaderName.value) {
        error.value = 'Nama pengirim wajib diisi.';

        return;
    }

    if (props.link.require_pin && !pin.value) {
        error.value = 'PIN wajib diisi.';

        return;
    }

    uploading.value = true;
    error.value = '';

    for (const file of Array.from(files)) {
        const form = new FormData();
        form.append('file', file);
        form.append('uploader_name', uploaderName.value);
        form.append('pin', pin.value);
        const res = await fetch(`/u/${props.token}`, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        });

        if (!res.ok) {
            error.value = 'Upload gagal. Periksa PIN atau batas unggahan.';
            uploading.value = false;

            return;
        }
    }

    uploading.value = false;
    message.value = 'Upload berhasil. File diproses di latar belakang.';
}
</script>

<template>
    <Head title="Unggah nota" />
    <div
        class="mx-auto flex min-h-screen max-w-lg flex-col justify-center gap-4 p-6"
    >
        <h1 class="text-xl font-semibold">{{ link.name }}</h1>
        <p class="text-sm text-muted-foreground">
            Kirim nota ke {{ link.workspace }}. Anda tidak dapat melihat dokumen
            lain.
        </p>
        <Input
            v-if="link.require_uploader_name"
            v-model="uploaderName"
            placeholder="Nama Anda"
        />
        <Input
            v-if="link.require_pin"
            v-model="pin"
            type="password"
            placeholder="PIN"
        />
        <input
            ref="fileInput"
            type="file"
            class="hidden"
            multiple
            accept="image/jpeg,image/png,image/webp,application/pdf"
            @change="upload(($event.target as HTMLInputElement).files)"
        />
        <div
            class="rounded-xl border-2 border-dashed p-8 text-center text-sm text-muted-foreground"
            :class="dragging ? 'border-primary bg-primary/5' : ''"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="
                dragging = false;
                upload($event.dataTransfer?.files ?? null);
            "
        >
            Seret file ke sini atau pilih dari perangkat.
        </div>
        <Button :disabled="uploading" @click="fileInput?.click()">{{
            uploading ? 'Mengunggah…' : 'Pilih file'
        }}</Button>
        <p v-if="message" class="text-sm text-emerald-700">{{ message }}</p>
        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
        <p v-if="link.remaining !== null" class="text-xs text-muted-foreground">
            Sisa kuota: {{ link.remaining }}
        </p>
    </div>
</template>
