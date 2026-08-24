<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    plan: Record<string, any>;
    used: number;
    remaining: number;
    ledgers: Array<Record<string, any>>;
}>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Kuota', href: '#' }] } });
</script>

<template>
    <Head title="Kuota & billing" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Penggunaan</h1>
        <div class="grid gap-4 md:grid-cols-3">
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm">Paket</CardTitle></CardHeader
                ><CardContent>{{ plan.name }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm"
                        >Halaman terpakai</CardTitle
                    ></CardHeader
                ><CardContent
                    >{{ used }} / {{ plan.pages_per_month }}</CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm">Sisa</CardTitle></CardHeader
                ><CardContent>{{ remaining }}</CardContent></Card
            >
        </div>
        <div class="divide-y rounded-xl border text-sm">
            <div
                v-for="row in ledgers"
                :key="row.id"
                class="flex justify-between p-3"
            >
                <span>{{ row.usage_type }} · {{ row.quantity }}</span>
                <span>{{ row.created_at }}</span>
            </div>
        </div>
    </div>
</template>
