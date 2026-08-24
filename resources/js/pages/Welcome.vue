<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="AI Document Journal" />
    <div
        class="flex min-h-screen flex-col items-center bg-background p-6 lg:justify-center"
    >
        <header class="mb-8 flex w-full max-w-4xl justify-end gap-3 text-sm">
            <Link
                v-if="$page.props.auth.user"
                href="/dashboard"
                class="rounded-md border px-4 py-1.5"
                >Dashboard</Link
            >
            <template v-else>
                <Link href="/login" class="rounded-md px-4 py-1.5">Masuk</Link>
                <Link href="/register" class="rounded-md border px-4 py-1.5"
                    >Daftar</Link
                >
            </template>
        </header>
        <main class="w-full max-w-3xl rounded-2xl border bg-card p-8 lg:p-12">
            <p class="text-sm font-medium text-primary">AI Document Journal</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Unggah nota, dapatkan jurnal siap ekspor.
            </h1>
            <p class="mt-4 text-muted-foreground">
                Satu akun, banyak workspace. Upload tidak pernah menunggu AI.
                Duplikasi dicek, data diekstrak, reviewer mengoreksi, approver
                mengesahkan, lalu export Excel/CSV.
            </p>
            <ol class="mt-6 grid gap-2 text-sm">
                <li>1. Unggah foto/PDF nota</li>
                <li>2. Worker cek duplikasi dan mengekstrak data</li>
                <li>3. Review dan approval terpisah</li>
                <li>4. Export tabel transaksi atau jurnal double-entry</li>
            </ol>
            <div class="mt-8 flex gap-3">
                <Link
                    href="/register"
                    class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >Mulai gratis</Link
                >
                <Link href="/login" class="rounded-md border px-4 py-2 text-sm"
                    >Masuk</Link
                >
            </div>
        </main>
    </div>
</template>
