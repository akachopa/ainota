<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    templates: Array<{
        slug: string;
        name: string;
        description: string | null;
    }>;
}>();

const form = useForm({
    name: '',
    legal_name: '',
    currency: 'IDR',
    timezone: 'Asia/Jakarta',
    locale: 'id',
    template: 'jasa',
    require_separate_approver: false,
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Buat workspace', href: '/workspaces/create' }],
    },
});
</script>

<template>
    <Head title="Buat workspace" />
    <form
        class="mx-auto flex max-w-xl flex-1 flex-col gap-4 p-4"
        @submit.prevent="form.post('/workspaces')"
    >
        <h1 class="text-xl font-semibold">Buat workspace</h1>
        <div class="grid gap-2">
            <Label>Nama workspace</Label>
            <Input v-model="form.name" required placeholder="PT Maju Jaya" />
        </div>
        <div class="grid gap-2">
            <Label>Nama legal</Label>
            <Input v-model="form.legal_name" />
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div class="grid gap-2">
                <Label>Mata uang</Label><Input v-model="form.currency" />
            </div>
            <div class="grid gap-2">
                <Label>Zona waktu</Label><Input v-model="form.timezone" />
            </div>
            <div class="grid gap-2">
                <Label>Locale</Label><Input v-model="form.locale" />
            </div>
        </div>
        <div class="grid gap-2">
            <Label>Template COA</Label>
            <select
                v-model="form.template"
                class="h-9 rounded-md border bg-background px-3 text-sm"
            >
                <option value="empty">Mulai kosong</option>
                <option
                    v-for="tpl in templates"
                    :key="tpl.slug"
                    :value="tpl.slug"
                >
                    {{ tpl.name }}
                </option>
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input v-model="form.require_separate_approver" type="checkbox" />
            Wajibkan approver berbeda dari reviewer
        </label>
        <Button :disabled="form.processing">Buat workspace</Button>
    </form>
</template>
