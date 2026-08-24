<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    members: Array<Record<string, any>>;
    invitations: Array<Record<string, any>>;
    roles: Array<{ value: string; label: string }>;
    canManage: boolean;
}>();
const page = usePage();
const slug = page.props.currentWorkspace?.slug;
const form = useForm({ email: '', role: 'uploader' });
defineOptions({ layout: { breadcrumbs: [{ title: 'Anggota', href: '#' }] } });
</script>

<template>
    <Head title="Anggota" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Anggota workspace</h1>
        <form
            v-if="canManage"
            class="flex gap-2"
            @submit.prevent="form.post(`/w/${slug}/members/invite`)"
        >
            <Input
                v-model="form.email"
                type="email"
                placeholder="email@contoh.com"
            />
            <select
                v-model="form.role"
                class="h-9 rounded-md border bg-background px-2 text-sm"
            >
                <option
                    v-for="role in roles"
                    :key="role.value"
                    :value="role.value"
                >
                    {{ role.label }}
                </option>
            </select>
            <Button :disabled="form.processing">Undang</Button>
        </form>
        <div class="divide-y rounded-xl border">
            <div
                v-for="member in members"
                :key="member.id"
                class="flex items-center justify-between p-3 text-sm"
            >
                <div>
                    <div class="font-medium">{{ member.user?.name }}</div>
                    <div class="text-muted-foreground">
                        {{ member.user?.email }} · {{ member.role }}
                    </div>
                </div>
                <Button
                    v-if="canManage && member.role !== 'owner'"
                    size="sm"
                    variant="outline"
                    @click="router.delete(`/w/${slug}/members/${member.id}`)"
                    >Hapus</Button
                >
            </div>
        </div>
        <h2 class="font-medium">Undangan</h2>
        <div
            class="text-sm text-muted-foreground"
            v-for="invite in invitations"
            :key="invite.id"
        >
            {{ invite.email }} — {{ invite.role }} ({{ invite.status }})
        </div>
    </div>
</template>
