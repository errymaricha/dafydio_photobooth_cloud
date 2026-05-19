<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPagination from '@/Components/AdminPagination.vue';

const props = defineProps({
    sessions: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ identity: 'all' }),
    },
});

const logoutForm = useForm({});
const searchForm = useForm({
    q: props.filters.q || '',
    identity: props.filters.identity || 'all',
    status: props.filters.status || 'all',
});
const sessions = computed(() => props.sessions);
const filters = computed(() => props.filters);

const logout = () => logoutForm.post('/admin/logout');
const applyFilters = () => searchForm.get('/admin/sessions', {
    preserveScroll: true,
    preserveState: true,
});

const filterUrl = (identity) => {
    const params = new URLSearchParams();
    params.set('identity', identity);

    if (searchForm.q) params.set('q', searchForm.q);
    if (searchForm.status && searchForm.status !== 'all') params.set('status', searchForm.status);

    return `/admin/sessions?${params.toString()}`;
};

const statusClass = (status) => {
    const value = String(status ?? '').toLowerCase();

    if (['complete', 'uploaded', 'ok'].includes(value)) return 'bg-green-100 text-[#10B981]';
    if (['syncing', 'pending'].includes(value)) return 'bg-blue-100 text-[#3B82F6]';
    if (['failed', 'cancelled'].includes(value)) return 'bg-red-100 text-[#EF4444]';

    return 'bg-slate-100 text-[#64748B]';
};

const sidebarItems = [
    ['DB', 'Overview', '/admin', false],
    ['BS', 'Business', '#business', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', true],
    ['PR', 'Print Requests', '#prints', false],
    ['BI', 'Billing', '#billing', false],
    ['LG', 'Logs', '#logs', false],
    ['SG', 'Settings', '#settings', false],
];

const bottomItems = [
    ['DB', 'Dashboard', '/admin', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['SE', 'Sessions', '/admin/sessions', true],
    ['SG', 'Settings', '#settings', false],
];

const filterItems = [
    ['Semua', 'all'],
    ['Customer dengan WA', 'customers'],
    ['Guest', 'guests'],
];

const hasActiveFilters = computed(() => (
    (props.filters.q || '') !== ''
    || props.filters.identity !== 'all'
    || props.filters.status !== 'all'
));

const statusOptions = [
    ['Semua status', 'all'],
    ['Pending', 'pending'],
    ['Syncing', 'syncing'],
    ['Complete', 'complete'],
    ['Failed', 'failed'],
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
                    <h1 class="text-[22px] font-bold leading-[1.3] lg:text-[32px]">Sessions</h1>
                    <p class="mt-1 text-sm leading-6 text-[#434655]">Arsip session yang diterima dari station.</p>
                </div>
                <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin">
                    Dashboard
                </Link>
            </header>

            <nav class="mb-4 flex flex-wrap gap-2">
                <Link
                    v-for="[label, value] in filterItems"
                    :key="value"
                    class="inline-flex min-h-10 items-center rounded-lg border px-4 text-xs font-semibold"
                    :class="filters.identity === value ? 'border-[#004ac6] bg-[#004ac6] text-white' : 'border-[#c3c6d7] bg-white text-[#434655] hover:bg-[#f3f3fe]'"
                    :href="filterUrl(value)"
                >
                    {{ label }}
                </Link>
            </nav>

            <form class="mb-4 grid gap-3 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:grid-cols-[1fr_180px_auto_auto] lg:items-end" @submit.prevent="applyFilters">
                <label class="block text-xs font-semibold text-[#434655]">
                    Cari session, WhatsApp, customer, station
                    <input v-model="searchForm.q" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="SES-ABC123 / 628... / Station 01" type="search">
                </label>
                <label class="block text-xs font-semibold text-[#434655]">
                    Status
                    <select v-model="searchForm.status" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]">
                        <option v-for="[label, value] in statusOptions" :key="value" :value="value">{{ label }}</option>
                    </select>
                </label>
                <button class="min-h-11 rounded-lg bg-[#004ac6] px-5 text-sm font-black text-white" type="submit">
                    Filter
                </button>
                <Link v-if="hasActiveFilters" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-[#c3c6d7] bg-white px-5 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin/sessions">
                    Reset
                </Link>
            </form>

            <section class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <div class="hidden grid-cols-[1.4fr_1fr_1fr_1fr_auto] gap-3 border-b border-[#c3c6d7] bg-[#f3f3fe] p-4 text-xs font-semibold uppercase tracking-wide text-[#737686] lg:grid">
                    <span>Session</span>
                    <span>Customer</span>
                    <span>Station</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <article v-for="session in sessions.data" :key="session.id" class="grid gap-4 border-b border-[#c3c6d7]/50 p-4 last:border-b-0 lg:grid-cols-[1.4fr_1fr_1fr_1fr_auto] lg:items-center">
                    <div>
                        <p class="font-semibold">{{ session.title }}</p>
                        <p class="mt-1 text-xs text-[#737686]">{{ session.public_session_code }} - {{ session.created_at || '-' }}</p>
                    </div>
                    <p class="text-sm font-semibold" :class="session.is_guest ? 'text-[#B45309]' : 'text-[#434655]'">{{ session.customer_name }}</p>
                    <p class="text-sm text-[#434655]">{{ session.station_name || 'No station' }}</p>
                    <div>
                        <span class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase" :class="statusClass(session.sync_status)">{{ session.sync_status }}</span>
                        <p class="mt-2 text-xs text-[#737686]">{{ session.assets_count ?? 0 }} assets</p>
                    </div>
                    <Link class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#004ac6] px-4 text-xs font-semibold text-white" :href="`/admin/sessions/${session.id}`">
                        Detail
                    </Link>
                </article>

                <p v-if="sessions.data.length === 0" class="p-6 text-sm text-[#434655]">Belum ada session.</p>
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
