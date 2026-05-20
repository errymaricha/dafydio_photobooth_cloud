<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    log: {
        type: Object,
        required: true,
    },
});

const logoutForm = useForm({});

const logout = () => logoutForm.post('/admin/logout');

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
                    <Link class="text-xs font-black uppercase tracking-wide text-[#004ac6]" href="/admin/sync-logs">
                        Kembali ke Sync Logs
                    </Link>
                    <h1 class="mt-2 text-[22px] font-bold leading-[1.3] lg:text-[32px]">Detail Sync Log</h1>
                    <p class="mt-1 break-all text-sm leading-6 text-[#434655]">{{ log.id }}</p>
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-[11px] font-black uppercase" :class="statusClass(log.status)">{{ log.status }}</span>
            </header>

            <section class="mb-5 grid gap-4 lg:grid-cols-4">
                <div class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#737686]">Topic</p>
                    <p class="mt-2 text-sm font-black">{{ log.topic || '-' }}</p>
                </div>
                <div class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#737686]">Direction</p>
                    <p class="mt-2 text-sm font-black">{{ log.direction || '-' }}</p>
                </div>
                <div class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#737686]">Station</p>
                    <p class="mt-2 text-sm font-black">{{ log.station_name || 'No station' }}</p>
                    <p class="mt-1 text-xs text-[#737686]">{{ log.station_code || '-' }}</p>
                </div>
                <div class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#737686]">Created</p>
                    <p class="mt-2 text-sm font-black">{{ log.created_at || '-' }}</p>
                    <p class="mt-1 text-xs text-[#737686]">Updated: {{ log.updated_at || '-' }}</p>
                </div>
            </section>

            <section class="mb-5 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wide text-[#737686]">Idempotency Key</p>
                <p class="mt-2 break-all text-sm font-semibold text-[#434655]">{{ log.idempotency_key || '-' }}</p>
                <p v-if="log.error_message" class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-semibold text-[#EF4444]">{{ log.error_message }}</p>
            </section>

            <section class="grid gap-5 xl:grid-cols-2">
                <article class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                    <div class="border-b border-[#c3c6d7] bg-[#f3f3fe] px-4 py-3">
                        <h2 class="text-sm font-black">Payload</h2>
                    </div>
                    <pre class="max-h-[70vh] overflow-auto whitespace-pre-wrap break-words p-4 text-xs leading-5 text-[#434655]">{{ jsonPreview(log.payload) }}</pre>
                </article>
                <article class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                    <div class="border-b border-[#c3c6d7] bg-[#f3f3fe] px-4 py-3">
                        <h2 class="text-sm font-black">Response</h2>
                    </div>
                    <pre class="max-h-[70vh] overflow-auto whitespace-pre-wrap break-words p-4 text-xs leading-5 text-[#434655]">{{ jsonPreview(log.response) }}</pre>
                </article>
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
