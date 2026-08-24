<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    token: string;
    workspace?: string | null;
    email?: string | null;
    valid?: boolean;
}>();

const form = useForm({});
</script>

<template>
    <Head title="Terima undangan" />
    <div
        class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-4 p-6"
    >
        <h1 class="text-xl font-semibold">Undangan workspace</h1>
        <p v-if="workspace" class="text-sm">
            Anda diundang ke <strong>{{ workspace }}</strong>
            <span v-if="email"> untuk {{ email }}</span
            >.
        </p>
        <p v-else class="text-sm text-muted-foreground">
            Masuk atau daftar dengan email yang diundang, lalu terima undangan
            ini.
        </p>
        <p v-if="valid === false" class="text-sm text-red-700">
            Undangan tidak valid atau sudah kedaluwarsa.
        </p>
        <div class="flex gap-2">
            <Link href="/login" class="underline">Masuk</Link>
            <Link href="/register" class="underline">Daftar</Link>
        </div>
        <form @submit.prevent="form.post(`/invitations/${token}`)">
            <Button :disabled="form.processing || valid === false"
                >Terima undangan</Button
            >
        </form>
    </div>
</template>
