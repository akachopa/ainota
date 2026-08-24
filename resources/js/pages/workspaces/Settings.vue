<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    workspaceModel: Record<string, any>;
    accounts: Array<{ id: string; code: string; name: string }>;
}>();

const page = usePage();
const settings = props.workspaceModel.settings ?? {};
const form = useForm({
    name: props.workspaceModel.name,
    legal_name: props.workspaceModel.legal_name ?? '',
    currency: props.workspaceModel.currency,
    timezone: props.workspaceModel.timezone,
    locale: props.workspaceModel.locale,
    require_approval: settings.require_approval ?? true,
    require_separate_approver: settings.require_separate_approver ?? false,
    allow_approver_edit: settings.allow_approver_edit ?? true,
    allow_export_unapproved: settings.allow_export_unapproved ?? false,
    uploader_can_view_amounts: settings.uploader_can_view_amounts ?? true,
    ai_processing_enabled: settings.ai_processing_enabled ?? true,
    duplicate_threshold: settings.duplicate_threshold ?? 75,
    default_cash_account_id: settings.default_cash_account_id ?? '',
});

defineOptions({
    layout: { breadcrumbs: [{ title: 'Pengaturan', href: '#' }] },
});
</script>

<template>
    <Head title="Pengaturan workspace" />
    <form
        class="mx-auto flex max-w-2xl flex-1 flex-col gap-4 p-4"
        @submit.prevent="
            form.patch(`/w/${page.props.currentWorkspace?.slug}/settings`)
        "
    >
        <h1 class="text-xl font-semibold">Pengaturan workspace</h1>
        <div class="grid gap-2">
            <Label>Nama</Label><Input v-model="form.name" />
        </div>
        <div class="grid gap-2">
            <Label>Nama legal</Label><Input v-model="form.legal_name" />
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
        <label class="flex gap-2 text-sm"
            ><input v-model="form.require_approval" type="checkbox" /> Wajibkan
            approval</label
        >
        <label class="flex gap-2 text-sm"
            ><input v-model="form.require_separate_approver" type="checkbox" />
            Approver terpisah</label
        >
        <label class="flex gap-2 text-sm"
            ><input v-model="form.allow_approver_edit" type="checkbox" />
            Approver boleh mengedit</label
        >
        <label class="flex gap-2 text-sm"
            ><input v-model="form.allow_export_unapproved" type="checkbox" />
            Izinkan export non-approved</label
        >
        <label class="flex gap-2 text-sm"
            ><input v-model="form.uploader_can_view_amounts" type="checkbox" />
            Uploader melihat nilai</label
        >
        <label class="flex gap-2 text-sm"
            ><input v-model="form.ai_processing_enabled" type="checkbox" />
            Aktifkan AI</label
        >
        <div class="grid gap-2">
            <Label>Ambang duplikasi</Label
            ><Input v-model="form.duplicate_threshold" type="number" />
        </div>
        <div class="grid gap-2">
            <Label>Akun kas default</Label>
            <select
                v-model="form.default_cash_account_id"
                class="h-9 rounded-md border bg-background px-3 text-sm"
            >
                <option value="">-</option>
                <option
                    v-for="account in accounts"
                    :key="account.id"
                    :value="account.id"
                >
                    {{ account.code }} {{ account.name }}
                </option>
            </select>
        </div>
        <Button :disabled="form.processing">Simpan</Button>
    </form>
</template>
