<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    vendors: { data: Array<Record<string, any>> };
    accounts: Array<{ id: string; code: string; name: string }>;
    canManage: boolean;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const form = useForm({ name: '', tax_id: '', default_expense_account_id: '' });
defineOptions({ layout: { breadcrumbs: [{ title: 'Vendor', href: '#' }] } });
</script>

<template>
    <Head title="Vendor" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Vendor</h1>
        <form
            v-if="canManage"
            class="grid gap-2 rounded-xl border p-4 md:grid-cols-4"
            @submit.prevent="form.post(`/w/${slug}/vendors`)"
        >
            <Input v-model="form.name" placeholder="Nama" />
            <Input v-model="form.tax_id" placeholder="NPWP" />
            <select
                v-model="form.default_expense_account_id"
                class="h-9 rounded-md border bg-background px-2 text-sm"
            >
                <option value="">Akun beban</option>
                <option
                    v-for="account in accounts"
                    :key="account.id"
                    :value="account.id"
                >
                    {{ account.code }} {{ account.name }}
                </option>
            </select>
            <Button :disabled="form.processing">Tambah</Button>
        </form>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Nama</th>
                        <th class="p-3">Akun default</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="vendor in vendors.data"
                        :key="vendor.id"
                        class="border-t"
                    >
                        <td class="p-3">{{ vendor.name }}</td>
                        <td class="p-3">
                            {{ vendor.default_expense_account?.name ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
