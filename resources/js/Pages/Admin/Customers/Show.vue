<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminPagination from '@/Components/AdminPagination.vue';

defineProps({
    customer: {
        type: Object,
        required: true,
    },
    sessions: {
        type: Object,
        required: true,
    },
});

const logoutForm = useForm({});
const logout = () => logoutForm.post('/admin/logout');

const statusClass = (status) => {
    const value = String(status ?? '').toLowerCase();

    if (['active', 'complete', 'uploaded'].includes(value)) return 'bg-green-100 text-[#10B981]';
    if (['syncing', 'pending'].includes(value)) return 'bg-blue-100 text-[#3B82F6]';
    if (['failed', 'cancelled'].includes(value)) return 'bg-red-100 text-[#EF4444]';

    return 'bg-slate-100 text-[#64748B]';
};

const sidebarItems = [
    ['DB', 'Overview', '/admin', false],
    ['BS', 'Business', '#business', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['CU', 'Customers', '/admin/customers', true],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['PR', 'Print Requests', '#prints', false],
    ['BI', 'Billing', '#billing', false],
    ['LG', 'Logs', '#logs', false],
    ['SG', 'Settings', '#settings', false],
];

const bottomItems = [
    ['DB', 'Dashboard', '/admin', false],
    ['CU', 'Customers', '/admin/customers', true],
    ['ST', 'Stations', '/admin/stations', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['SG', 'Settings', '#settings', false],
];
</script>

<template>
    <main class="min-h-screen bg-[#F8FAFC] text-[#191b23]">
        <header class="fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between bg-[#faf8ff] px-4 shadow-sm lg:hidden">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#dbe1ff] text-xs font-bold text-[#003ea8]">MN</span>
                <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="text-xl font-bold text-[#004ac6]">Dafydio</span></span>
            </div>
            <button class="flex size-9 items-center justify-center rounded-full border border-[#c3c6d7] bg-white text-xs font-bold text-[#004ac6]" type="button" @click="logout">AD</button>
        </header>

        <aside class="fixed inset-y-0 left-0 z-50 hidden w-[260px] flex-col border-r border-[#c3c6d7] bg-white lg:flex">
            <div class="flex h-16 items-center border-b border-[#c3c6d7] px-6">
                <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="text-xl font-bold text-[#004ac6]">Dafydio</span></span>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <template v-for="([icon, label, href, active], index) in sidebarItems" :key="label">
                    <div v-if="index === 6" class="px-3 pb-2 pt-4 text-[10px] font-bold uppercase tracking-wider text-[#737686]">Finance &amp; System</div>
                    <Link v-if="href.startsWith('/')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold transition-colors" :class="active ? 'bg-[#dbe1ff] text-[#003ea8]' : 'text-[#434655] hover:bg-[#f3f3fe]'" :href="href">
                        <span class="flex size-7 items-center justify-center rounded-md bg-white/70 text-[10px] font-bold">{{ icon }}</span>
                        {{ label }}
                    </Link>
                    <a v-else class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold transition-colors" :class="active ? 'bg-[#dbe1ff] text-[#003ea8]' : 'text-[#434655] hover:bg-[#f3f3fe]'" :href="href">
                        <span class="flex size-7 items-center justify-center rounded-md bg-[#f3f3fe] text-[10px] font-bold text-[#004ac6]">{{ icon }}</span>
                        {{ label }}
                    </a>
                </template>
            </nav>
            <div class="border-t border-[#c3c6d7] p-4">
                <button class="flex w-full items-center gap-3 rounded-xl bg-[#f3f3fe] p-2 text-left" type="button" @click="logout">
                    <span class="flex size-10 items-center justify-center rounded-full border border-[#c3c6d7] bg-white text-xs font-bold text-[#004ac6]">AD</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-semibold">Admin User</span>
                        <span class="block truncate text-[10px] text-[#737686]">Tenant Admin</span>
                    </span>
                    <span class="text-xs font-bold text-[#737686]">OUT</span>
                </button>
            </div>
        </aside>

        <section class="px-4 pb-28 pt-20 lg:ml-[260px] lg:p-8">
            <header class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center lg:mb-8">
                <div>
                    <p class="text-sm font-medium text-[#434655]">Admin / Customer Detail</p>
                    <h1 class="mt-1 text-[22px] font-bold leading-[1.3] lg:text-[32px]">{{ customer.name || 'Customer' }}</h1>
                    <p class="mt-2 text-sm text-[#434655]">{{ customer.whatsapp_number }}</p>
                </div>
                <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin/customers">
                    Kembali Customers
                </Link>
            </header>

            <section class="grid grid-cols-1 gap-5 lg:grid-cols-4">
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-[#737686]">Plan</p>
                    <h2 class="mt-2 text-2xl font-bold capitalize text-[#004ac6]">{{ customer.subscription_plan }}</h2>
                    <p class="mt-1 text-sm text-[#434655]">{{ customer.subscription_status }}</p>
                </article>
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-[#737686]">Status</p>
                    <span class="mt-3 inline-block rounded-full px-2 py-1 text-xs font-semibold uppercase" :class="statusClass(customer.status)">{{ customer.status }}</span>
                </article>
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-[#737686]">Last Login</p>
                    <p class="mt-2 text-sm font-semibold">{{ customer.last_login_at || '-' }}</p>
                </article>
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-[#737686]">Joined</p>
                    <p class="mt-2 text-sm font-semibold">{{ customer.created_at || '-' }}</p>
                </article>
            </section>

            <section class="mt-5 overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#c3c6d7] px-5 py-4">
                    <h2 class="text-lg font-semibold">Customer Sessions</h2>
                    <span class="text-sm font-semibold text-[#434655]">{{ sessions.total ?? sessions.data.length }} total</span>
                </div>

                <div class="hidden grid-cols-[1.4fr_1fr_1fr_1fr_auto] gap-3 border-b border-[#c3c6d7] bg-[#f3f3fe] p-4 text-xs font-semibold uppercase tracking-wide text-[#737686] lg:grid">
                    <span>Session</span>
                    <span>Station</span>
                    <span>Status</span>
                    <span>Assets</span>
                    <span>Action</span>
                </div>

                <article v-for="session in sessions.data" :key="session.id" class="grid gap-4 border-b border-[#c3c6d7]/50 p-4 last:border-b-0 lg:grid-cols-[1.4fr_1fr_1fr_1fr_auto] lg:items-center">
                    <div>
                        <p class="font-semibold">{{ session.title }}</p>
                        <p class="mt-1 text-xs text-[#737686]">{{ session.station_session_id }} - {{ session.created_at || '-' }}</p>
                    </div>
                    <p class="text-sm text-[#434655]">{{ session.station_name || 'No station' }}</p>
                    <span class="w-fit rounded-full px-2 py-1 text-[10px] font-semibold uppercase" :class="statusClass(session.sync_status)">{{ session.sync_status }}</span>
                    <p class="text-sm text-[#434655]">{{ session.assets_count ?? 0 }} assets</p>
                    <div class="flex flex-wrap gap-2">
                        <a
                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#004ac6] px-3 text-xs font-semibold text-[#004ac6] hover:bg-[#f3f3fe]"
                            :href="session.public_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Public Gallery
                        </a>
                        <Link class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#004ac6] px-3 text-xs font-semibold text-white" :href="`/admin/sessions/${session.id}?from_customer=1`">
                            Detail
                        </Link>
                    </div>
                </article>

                <p v-if="sessions.data.length === 0" class="p-6 text-sm text-[#434655]">Customer ini belum punya session.</p>
                <AdminPagination :paginator="sessions" />
            </section>
        </section>

        <nav class="fixed bottom-0 left-0 z-50 flex h-20 w-full items-center justify-around border-t border-[#c3c6d7] bg-[#ededf9] px-2 lg:hidden">
            <a v-for="[icon, label, href, active] in bottomItems" :key="label" class="flex flex-col items-center justify-center px-2 py-1.5 text-[#434655]" :class="{ 'rounded-xl bg-[#fea619] text-[#684000]': active }" :href="href">
                <span class="text-[11px] font-bold">{{ icon }}</span>
                <span class="mt-1 text-xs font-semibold">{{ label }}</span>
            </a>
        </nav>
    </main>
</template>
