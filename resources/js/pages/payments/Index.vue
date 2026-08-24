<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    mappings: Array<Record<string, any>>;
    accounts: Array<{ id: string; code: string; name: string }>;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const form = useForm({
    keyword: '',
    label: '',
    account_id: '',
    is_default: false,
});
defineOptions({
    layout: { breadcrumbs: [{ title: 'Mapping pembayaran', href: '#' }] },
});
</script>

<template>
    <Head title="Mapping pembayaran" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Mapping kas/bank</h1>
        <form
            class="grid gap-2 rounded-xl border p-4 md:grid-cols-4"
            @submit.prevent="form.post(`/w/${slug}/payments`)"
        >
            <Input v-model="form.keyword" placeholder="kata kunci (bca)" />
            <Input v-model="form.label" placeholder="Label" />
            <select
                v-model="form.account_id"
                class="h-9 rounded-md border bg-background px-2 text-sm"
            >
                <option
                    v-for="account in accounts"
                    :key="account.id"
                    :value="account.id"
                >
                    {{ account.code }} {{ account.name }}
                </option>
            </select>
            <Button :disabled="form.processing">Simpan</Button>
        </form>
        <div class="divide-y rounded-xl border">
            <div
                v-for="map in mappings"
                :key="map.id"
                class="flex justify-between p-3 text-sm"
            >
                <span>{{ map.label }} ({{ map.keyword }})</span>
                <span>{{ map.account?.code }} {{ map.account?.name }}</span>
            </div>
        </div>
    </div>
</template>
