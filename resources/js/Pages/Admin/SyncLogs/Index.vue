<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPagination from '@/Components/AdminPagination.vue';

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '', topic: 'all', status: 'all' }),
    },
    topics: {
        type: Array,
        default: () => [],
    },
});

const logoutForm = useForm({});
const filterForm = useForm({
    q: props.filters.q || '',
    topic: props.filters.topic || 'all',
    status: props.filters.status || 'all',
});

const logout = () => logoutForm.post('/admin/logout');
const applyFilters = () => filterForm.get('/admin/sync-logs', {
    preserveScroll: true,
    preserveState: true,
});

const hasActiveFilters = computed(() => (
    (props.filters.q || '') !== ''
    || props.filters.topic !== 'all'
    || props.filters.status !== 'all'
));

const statusClass = (status) => {
    const value = String(status ?? '').toLowerCase();

    if (['ok', 'success', 'synced'].includes(value)) return 'bg-green-100 text-[#10B981]';
    if (['pending', 'syncing'].includes(value)) return 'bg-blue-100 text-[#3B82F6]';
    if (['failed', 'error'].includes(value)) return 'bg-red-100 text-[#EF4444]';

    return 'bg-slate-100 text-[#64748B]';
};

const jsonPreview = (value) => {
    if (!value) return '-';

    return JSON.stringify(value, null, 2);
};

const sidebarItems = [
    ['DB', 'Overview', '/admin', false],
    ['BS', 'Business', '#business', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['PR', 'Print Requests', '#prints', false],
    ['BI', 'Billing', '#billing', false],
    ['LG', 'Logs', '/admin/sync-logs', true],
    ['SG', 'Settings', '#settings', false],
];

const bottomItems = [
    ['DB', 'Dashboard', '/admin', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['LG', 'Logs', '/admin/sync-logs', true],
];
</script>

<template>
    <main class="min-h-screen bg-[#F8FAFC] text-[#191b23]">
        <header class="fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between bg-[#faf8ff] px-4 shadow-sm lg:hidden">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#dbe1ff] text-xs font-bold text-[#003ea8]">LG</span>
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
        </aside>

        <section class="px-4 pb-28 pt-20 lg:ml-[260px] lg:p-8">
            <header class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center lg:mb-8">
                <div>
                    <h1 class="text-[22px] font-bold leading-[1.3] lg:text-[32px]">Sync Logs</h1>
                    <p class="mt-1 text-sm leading-6 text-[#434655]">Log sync station ke cloud untuk audit dan debugging upload.</p>
                </div>
                <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin">
                    Dashboard
                </Link>
            </header>

            <form class="mb-4 grid gap-3 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:grid-cols-[1fr_190px_150px_auto_auto] lg:items-end" @submit.prevent="applyFilters">
                <label class="block text-xs font-semibold text-[#434655]">
                    Cari station, idempotency, payload, error
                    <input v-model="filterForm.q" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="SES-... / asset-upload / error" type="search">
                </label>
                <label class="block text-xs font-semibold text-[#434655]">
                    Topic
                    <select v-model="filterForm.topic" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]">
                        <option value="all">Semua topic</option>
                        <option v-for="topic in topics" :key="topic" :value="topic">{{ topic }}</option>
                    </select>
                </label>
                <label class="block text-xs font-semibold text-[#434655]">
                    Status
                    <select v-model="filterForm.status" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]">
                        <option value="all">Semua</option>
                        <option value="ok">OK</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                        <option value="syncing">Syncing</option>
                    </select>
                </label>
                <button class="min-h-11 rounded-lg bg-[#004ac6] px-5 text-sm font-black text-white" type="submit">
                    Filter
                </button>
                <Link v-if="hasActiveFilters" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-[#c3c6d7] bg-white px-5 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin/sync-logs">
                    Reset
                </Link>
            </form>

            <section class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <div class="hidden grid-cols-[1fr_1fr_1fr_1.6fr] gap-3 border-b border-[#c3c6d7] bg-[#f3f3fe] p-4 text-xs font-semibold uppercase tracking-wide text-[#737686] lg:grid">
                    <span>Sync</span>
                    <span>Station</span>
                    <span>Status</span>
                    <span>Data</span>
                </div>

                <article v-for="log in logs.data" :key="log.id" class="grid gap-4 border-b border-[#c3c6d7]/50 p-4 last:border-b-0 lg:grid-cols-[1fr_1fr_1fr_1.6fr] lg:items-start">
                    <div>
                        <p class="text-sm font-black">{{ log.topic }}</p>
                        <p class="mt-1 text-xs text-[#737686]">{{ log.direction }}</p>
                        <p class="mt-2 break-all text-[11px] text-[#737686]">{{ log.idempotency_key || 'No idempotency key' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ log.station_name || 'No station' }}</p>
                        <p class="mt-1 text-xs text-[#737686]">{{ log.station_code || '-' }}</p>
                        <p class="mt-2 text-xs text-[#737686]">{{ log.created_at }}</p>
                    </div>
                    <div>
                        <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase" :class="statusClass(log.status)">{{ log.status }}</span>
                        <p v-if="log.error_message" class="mt-2 text-xs font-semibold text-[#EF4444]">{{ log.error_message }}</p>
                    </div>
                    <details class="rounded-lg border border-[#c3c6d7] bg-[#f8fafc]">
                        <summary class="cursor-pointer px-3 py-2 text-xs font-black text-[#004ac6]">Payload / Response</summary>
                        <div class="space-y-3 border-t border-[#c3c6d7] p-3">
                            <div>
                                <p class="text-[10px] font-black uppercase text-[#737686]">Payload</p>
                                <pre class="mt-1 max-h-52 overflow-auto whitespace-pre-wrap break-words rounded bg-white p-2 text-[11px] text-[#434655]">{{ jsonPreview(log.payload) }}</pre>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase text-[#737686]">Response</p>
                                <pre class="mt-1 max-h-52 overflow-auto whitespace-pre-wrap break-words rounded bg-white p-2 text-[11px] text-[#434655]">{{ jsonPreview(log.response) }}</pre>
                            </div>
                        </div>
                    </details>
                </article>

                <p v-if="logs.data.length === 0" class="p-6 text-sm text-[#434655]">Belum ada sync log.</p>
                <AdminPagination :paginator="logs" />
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
