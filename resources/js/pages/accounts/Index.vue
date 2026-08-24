<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    accounts: Array<Record<string, any>>;
    types: Array<{ value: string; label: string }>;
    canManage: boolean;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const form = useForm({
    code: '',
    name: '',
    type: 'EXPENSE',
    parent_id: '',
    description: '',
});
const importForm = useForm({ file: null as File | null });
defineOptions({ layout: { breadcrumbs: [{ title: 'COA', href: '#' }] } });
</script>

<template>
    <Head title="Chart of Accounts" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Daftar akun</h1>
        <form
            v-if="canManage"
            class="grid gap-2 rounded-xl border p-4 md:grid-cols-5"
            @submit.prevent="form.post(`/w/${slug}/accounts`)"
        >
            <Input v-model="form.code" placeholder="Kode" />
            <Input v-model="form.name" placeholder="Nama" />
            <select
                v-model="form.type"
                class="h-9 rounded-md border bg-background px-2 text-sm"
            >
                <option
                    v-for="type in types"
                    :key="type.value"
                    :value="type.value"
                >
                    {{ type.label }}
                </option>
            </select>
            <Input v-model="form.description" placeholder="Deskripsi" />
            <Button :disabled="form.processing">Tambah</Button>
        </form>
        <form
            v-if="canManage"
            class="flex flex-wrap items-center gap-2 rounded-xl border p-4 text-sm"
            @submit.prevent="
                importForm.post(`/w/${slug}/accounts/import`, {
                    forceFormData: true,
                })
            "
        >
            <span>Impor CSV/XLSX</span>
            <input
                type="file"
                accept=".xlsx,.csv,.xls"
                @change="
                    importForm.file =
                        ($event.target as HTMLInputElement).files?.[0] ?? null
                "
            />
            <Button
                type="submit"
                variant="outline"
                :disabled="importForm.processing || !importForm.file"
                >Impor</Button
            >
        </form>
        <div
            v-if="!accounts.length"
            class="rounded-xl border p-6 text-sm text-muted-foreground"
        >
            Belum ada akun.
        </div>
        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Kode</th>
                        <th class="p-3">Nama</th>
                        <th class="p-3">Tipe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="account in accounts"
                        :key="account.id"
                        class="border-t"
                    >
                        <td class="p-3">{{ account.code }}</td>
                        <td class="p-3">{{ account.name }}</td>
                        <td class="p-3">{{ account.type }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
