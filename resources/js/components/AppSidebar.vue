<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CheckSquare,
    FileText,
    FolderTree,
    LayoutGrid,
    Link2,
    Receipt,
    Settings,
    ShieldCheck,
    Store,
    Upload,
    Users,
    Wallet,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();

function can(permission: string): boolean {
    return (page.props.permissions as string[]).includes(permission);
}

const workspace = () => page.props.currentWorkspace;

const mainNavItems = (): NavItem[] => {
    const ws = workspace();

    if (!ws) {
        return [{ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid }];
    }

    const base = `/w/${ws.slug}`;
    const items: NavItem[] = [
        { title: 'Dashboard', href: base, icon: LayoutGrid },
        { title: 'Dokumen', href: `${base}/documents`, icon: FileText },
    ];

    if (can('document.upload')) {
        items.push({
            title: 'Unggah',
            href: `${base}/documents?upload=1`,
            icon: Upload,
        });
    }

    if (can('transaction.review')) {
        items.push({
            title: 'Review',
            href: `${base}/review`,
            icon: CheckSquare,
        });
    }

    if (can('transaction.approve')) {
        items.push({
            title: 'Persetujuan',
            href: `${base}/approval`,
            icon: ShieldCheck,
        });
    }

    if (
        can('document.view_all') ||
        can('transaction.review') ||
        can('transaction.approve')
    ) {
        items.push({
            title: 'Transaksi',
            href: `${base}/transactions`,
            icon: Receipt,
        });
    }

    if (can('transaction.export')) {
        items.push({
            title: 'Export',
            href: `${base}/exports`,
            icon: FolderTree,
        });
    }

    return items;
};

const masterNavItems = (): NavItem[] => {
    const ws = workspace();

    if (!ws) {
        return [];
    }

    const base = `/w/${ws.slug}`;
    const items: NavItem[] = [];

    if (can('coa.view')) {
        items.push({
            title: 'Akun / COA',
            href: `${base}/accounts`,
            icon: Wallet,
        });
        items.push({ title: 'Vendor', href: `${base}/vendors`, icon: Store });
    }

    if (can('coa.manage')) {
        items.push({
            title: 'Mapping Bayar',
            href: `${base}/payments`,
            icon: Wallet,
        });
    }

    if (can('member.manage')) {
        items.push({ title: 'Anggota', href: `${base}/members`, icon: Users });
    }

    if (can('upload_link.manage')) {
        items.push({
            title: 'Link Unggah',
            href: `${base}/upload-links`,
            icon: Link2,
        });
    }

    if (can('workspace.manage')) {
        items.push({
            title: 'Pengaturan',
            href: `${base}/settings`,
            icon: Settings,
        });
        items.push({ title: 'Kuota', href: `${base}/usage`, icon: Receipt });
    }

    return items;
};

function switchWorkspace(event: Event) {
    const value = (event.target as HTMLSelectElement).value;

    if (!value) {
        return;
    }

    if (value === '__new') {
        window.location.href = '/workspaces/create';

        return;
    }

    window.location.href = `/w/${value}`;
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <div class="px-2">
                <select
                    v-if="page.props.workspaces.length"
                    class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                    :value="page.props.currentWorkspace?.slug ?? ''"
                    @change="switchWorkspace"
                >
                    <option disabled value="">Pilih workspace</option>
                    <option
                        v-for="ws in page.props.workspaces"
                        :key="ws.id"
                        :value="ws.slug"
                    >
                        {{ ws.name }}
                    </option>
                    <option value="__new">+ Buat workspace</option>
                </select>
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain label="Menu" :items="mainNavItems()" />
            <NavMain
                v-if="masterNavItems().length"
                label="Master Data"
                :items="masterNavItems()"
            />
        </SidebarContent>

        <SidebarFooter>
            <Link
                v-if="page.props.auth.user?.is_platform_admin"
                href="/platform"
                class="mx-2 mb-2 rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-accent"
            >
                Platform admin
            </Link>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
