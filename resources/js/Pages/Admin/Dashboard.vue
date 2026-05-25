<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },
    recentStations: {
        type: Array,
        default: () => [],
    },
    recentSessions: {
        type: Array,
        default: () => [],
    },
    printRequests: {
        type: Array,
        default: () => [],
    },
    syncLogs: {
        type: Array,
        default: () => [],
    },
    storage: {
        type: Object,
        default: () => ({}),
    },
});

const logoutForm = useForm({});
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const page = usePage();
const flash = computed(() => page.props.flash || {});

const logout = () => {
    logoutForm.post('/admin/logout');
};

const updatePassword = () => {
    passwordForm.patch('/admin/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

const todayLabel = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
}).format(new Date());

const storagePercent = computed(() => {
    const total = Number(props.metrics.assets_total ?? 0);
    const uploaded = Number(props.metrics.assets_uploaded ?? 0);

    if (total < 1) {
        return 0;
    }

    return Math.min(100, Math.round((uploaded / total) * 100));
});

const statusClass = (status) => {
    const value = String(status ?? '').toLowerCase();

    if (['active', 'complete', 'uploaded', 'paid', 'printed', 'ok'].includes(value)) {
        return 'bg-green-100 text-[#10B981]';
    }

    if (['printing', 'syncing', 'claimed', 'processing'].includes(value)) {
        return 'bg-blue-100 text-[#3B82F6]';
    }

    if (['failed', 'cancelled', 'expired'].includes(value)) {
        return 'bg-red-100 text-[#EF4444]';
    }

    return 'bg-slate-100 text-[#64748B]';
};

const stationHealth = computed(() => props.recentStations.slice(0, 4));
const recentSessions = computed(() => props.recentSessions.slice(0, 6));
const recentPrints = computed(() => props.printRequests.slice(0, 5));
const mobilePrints = computed(() => props.printRequests.slice(0, 3));

const sidebarItems = [
    ['DB', 'Overview', '/admin', true],
    ['BS', 'Business', '#business', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['TP', 'Templates', '/admin/templates', false],
    ['PR', 'Print Requests', '#prints', false],
    ['BI', 'Payments', '/admin/payments', false],
    ['LG', 'Logs', '/admin/sync-logs', false],
    ['SG', 'Settings', '#settings', false],
];

const bottomItems = [
    ['DB', 'Dashboard', '/admin', true],
    ['BS', 'Business', '#business', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['TP', 'Templates', '/admin/templates', false],
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
            <div class="flex items-center gap-4">
                <span class="relative flex size-9 items-center justify-center rounded-lg bg-white text-xs font-bold text-[#434655]">
                    NT
                    <span class="absolute right-1 top-1 size-2 rounded-full bg-[#ba1a1a]" />
                </span>
                <button class="flex size-9 items-center justify-center rounded-full border border-[#c3c6d7] bg-white text-xs font-bold text-[#004ac6]" type="button" @click="logout">AD</button>
            </div>
        </header>

        <aside class="fixed inset-y-0 left-0 z-50 hidden w-[260px] flex-col border-r border-[#c3c6d7] bg-white lg:flex">
            <div class="flex h-16 items-center border-b border-[#c3c6d7] px-6">
                <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="text-xl font-bold text-[#004ac6]">Dafydio</span></span>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <template v-for="([icon, label, href, active], index) in sidebarItems" :key="label">
                    <div v-if="index === 6" class="px-3 pb-2 pt-4 text-[10px] font-bold uppercase tracking-wider text-[#737686]">Finance &amp; System</div>
                    <Link
                        v-if="href.startsWith('/')"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold transition-colors"
                        :class="active ? 'bg-[#dbe1ff] text-[#003ea8]' : 'text-[#434655] hover:bg-[#f3f3fe]'"
                        :href="href"
                    >
                        <span class="flex size-7 items-center justify-center rounded-md bg-white/70 text-[10px] font-bold">{{ icon }}</span>
                        {{ label }}
                    </Link>
                    <a
                        v-else
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold text-[#434655] transition-colors hover:bg-[#f3f3fe]"
                        :href="href"
                    >
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
                    <h1 class="text-[22px] font-bold leading-[1.3] text-[#191b23] lg:text-[32px] lg:leading-[1.2]">Dashboard Overview</h1>
                    <p class="mt-1 text-sm leading-6 text-[#434655]">{{ todayLabel }}</p>
                </div>
                <div class="hidden items-center gap-3 md:flex">
                    <button class="flex min-h-11 items-center gap-2 rounded-lg border border-[#c3c6d7] bg-white px-4 text-xs font-semibold text-[#434655] transition-colors hover:bg-[#f3f3fe]" type="button">
                        <span class="text-[10px] font-bold">DL</span>
                        Export Report
                    </button>
                    <Link class="flex min-h-11 items-center gap-2 rounded-lg bg-[#004ac6] px-4 text-xs font-semibold text-white transition-all active:scale-95" href="/admin/stations">
                        <span class="text-[10px] font-bold">+</span>
                        New Station
                    </Link>
                </div>
            </header>

            <section class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:p-5">
                    <div class="mb-3 flex items-start justify-between">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-[#dbe1ff] text-xs font-bold text-[#004ac6]">CU</span>
                        <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-[#10B981]">+{{ metrics.premium_customers ?? 0 }}</span>
                    </div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-[#434655]">Customers</p>
                    <h3 class="mt-1 text-xl font-bold lg:text-2xl">{{ metrics.customers ?? 0 }}</h3>
                </article>

                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:p-5">
                    <div class="mb-3 flex items-start justify-between">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-[#ffddb8] text-xs font-bold text-[#855300]">ST</span>
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-[#3B82F6]">{{ metrics.stations_online ?? 0 }} online</span>
                    </div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-[#434655]">Active Stations</p>
                    <h3 class="mt-1 text-xl font-bold lg:text-2xl">{{ metrics.stations_total ?? 0 }}</h3>
                </article>

                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:p-5">
                    <div class="mb-3 flex items-start justify-between">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-[#ffdad6] text-xs font-bold text-[#ba1a1a]">PR</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-[#64748B]">Urgent</span>
                    </div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-[#434655]">Pending Prints</p>
                    <h3 class="mt-1 text-xl font-bold lg:text-2xl">{{ metrics.pending_prints ?? 0 }}</h3>
                </article>

                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:p-5">
                    <div class="mb-3 flex items-start justify-between">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-[#e1e2ed] text-xs font-bold text-[#434655]">AS</span>
                    </div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-[#434655]">Storage Used</p>
                    <div class="mt-2 flex items-baseline gap-1">
                        <h3 class="text-xl font-bold lg:text-2xl">{{ metrics.assets_uploaded ?? 0 }}</h3>
                        <span class="text-xs font-semibold text-[#434655]">/ {{ metrics.assets_total ?? 0 }} assets</span>
                    </div>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-[#f3f3fe]">
                        <div class="h-full bg-[#004ac6]" :style="{ width: `${storagePercent}%` }" />
                    </div>
                </article>
            </section>

            <section id="sessions" class="mb-5 overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#c3c6d7] px-4 py-4 lg:px-6">
                    <h2 class="text-xl font-semibold">Recent Sessions</h2>
                    <span class="text-xs font-semibold text-[#434655]">{{ metrics.sessions_total ?? 0 }} total</span>
                </div>
                <div class="grid grid-cols-1 divide-y divide-[#c3c6d7] md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-3">
                    <article v-for="session in recentSessions" :key="session.id" class="p-4 lg:p-5">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold">{{ session.title || 'Untitled Session' }}</h3>
                                <p class="mt-1 text-xs text-[#434655]">{{ session.customer_name || 'No customer' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold uppercase" :class="statusClass(session.sync_status)">
                                {{ session.sync_status || 'pending' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-[#434655]">
                            <span>{{ session.station_name || 'No station' }}</span>
                            <span>{{ session.assets_count ?? 0 }} assets</span>
                        </div>
                        <p class="mt-3 text-[11px] text-[#737686]">{{ session.created_at || '-' }}</p>
                        <Link class="mt-4 inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] px-3 text-xs font-semibold text-[#004ac6] hover:bg-[#f3f3fe]" :href="`/admin/sessions/${session.id}`">
                            Detail
                        </Link>
                    </article>
                    <p v-if="recentSessions.length === 0" class="p-5 text-sm text-[#434655] md:col-span-2 xl:col-span-3">
                        Belum ada session. Data akan muncul setelah station sync session ke cloud.
                    </p>
                </div>
            </section>

            <div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-12">
                <section id="prints" class="hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm lg:col-span-8 lg:block">
                    <div class="flex items-center justify-between border-b border-[#c3c6d7] px-6 py-4">
                        <h2 class="text-xl font-semibold">Recent Print Requests</h2>
                        <a class="text-xs font-semibold text-[#004ac6] hover:underline" href="#prints">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-[#f3f3fe]">
                                    <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-[#434655]">Station</th>
                                    <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-[#434655]">Customer</th>
                                    <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-[#434655]">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[#434655]">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#c3c6d7]">
                                <tr v-for="print in recentPrints" :key="print.id" class="transition-colors hover:bg-white">
                                    <td class="px-6 py-4 text-sm">{{ print.station_name || 'No station' }}</td>
                                    <td class="px-6 py-4 text-sm">{{ print.customer_name || 'No customer' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase" :class="statusClass(print.status)">{{ print.status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-[#434655]">{{ print.created_at || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="recentPrints.length === 0" class="p-6 text-sm text-[#434655]">Belum ada print request.</p>
                    </div>
                </section>

                <section class="space-y-4 lg:col-span-4 lg:rounded-xl lg:border lg:border-[#c3c6d7] lg:bg-white lg:shadow-sm">
                    <div class="flex items-center justify-between lg:border-b lg:border-[#c3c6d7] lg:px-6 lg:py-4">
                        <h2 class="text-xl font-semibold">Station Sync Health</h2>
                        <a class="text-xs font-semibold text-[#004ac6] lg:hidden" href="/admin/stations">View All</a>
                    </div>
                    <div class="space-y-3 lg:p-4">
                        <article
                            v-for="station in stationHealth"
                            :key="station.id"
                            class="flex items-center justify-between rounded-xl border border-[#c3c6d7] bg-white p-3 lg:bg-[#f3f3fe] lg:border-[#c3c6d7]/50"
                            :class="{ 'lg:bg-[#ffdad6]/20': !station.is_online && station.last_seen_at }"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-lg bg-[#ededf9] text-xs font-bold text-[#434655]">ST</span>
                                <div>
                                    <p class="text-xs font-semibold">{{ station.name }}</p>
                                    <p class="text-[11px] text-[#434655]">{{ station.last_seen_at || 'Belum pernah heartbeat' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 rounded bg-[#f3f3fe] px-2 py-1 lg:bg-white/70">
                                <span class="size-2 rounded-full" :class="station.is_online ? 'bg-[#10B981]' : 'bg-[#64748B]'" />
                                <span class="text-[11px] font-semibold text-[#434655]">{{ station.is_online ? 'Healthy' : 'Offline' }}</span>
                            </div>
                        </article>
                        <p v-if="stationHealth.length === 0" class="rounded-xl border border-[#c3c6d7] bg-white p-4 text-sm text-[#434655]">Belum ada station.</p>
                    </div>
                    <div class="hidden border-t border-[#c3c6d7] p-4 lg:block">
                        <Link class="block w-full rounded-lg bg-[#e1e2ed] py-2 text-center text-xs font-semibold text-[#191b23] transition-colors hover:bg-[#c3c6d7]" href="/admin/stations">
                            Run Station Diagnostics
                        </Link>
                    </div>
                </section>
            </div>

            <section class="space-y-4 lg:hidden">
                <h2 class="text-xl font-semibold">Recent Print Requests</h2>
                <div class="divide-y divide-[#c3c6d7] rounded-xl border border-[#c3c6d7] bg-white">
                    <article v-for="print in mobilePrints" :key="print.id" class="flex items-center justify-between p-4">
                        <div>
                            <p class="text-xs font-semibold">{{ print.customer_name || 'No customer' }}</p>
                            <p class="mt-1 text-[11px] text-[#434655]">Qty {{ print.quantity }} - {{ print.created_at || '-' }}</p>
                        </div>
                        <span class="rounded-lg px-2 py-1 text-[10px] font-bold uppercase" :class="statusClass(print.status)">{{ print.status }}</span>
                    </article>
                    <p v-if="mobilePrints.length === 0" class="p-4 text-sm text-[#434655]">Belum ada print request.</p>
                </div>
                <a class="block min-h-12 rounded-xl border-2 border-[#c3c6d7] py-3 text-center text-xs font-semibold text-[#434655] transition-colors hover:bg-[#f3f3fe]" href="#logs">View System Logs</a>
            </section>

            <section id="logs" class="mt-5 overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#c3c6d7] px-4 py-4 lg:px-6">
                    <div class="flex items-center gap-2">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-[#f3f3fe] text-[10px] font-bold text-[#004ac6]">LG</span>
                        <h2 class="text-xl font-semibold">System Logs</h2>
                    </div>
                    <span class="rounded-full bg-[#10B981]/10 px-2 py-0.5 text-[10px] font-bold text-[#10B981]">LIVE STREAMING</span>
                </div>
                <div class="max-h-60 space-y-2 overflow-y-auto bg-[#1e1e2e] p-4 font-mono text-xs leading-relaxed text-[#a6adc8]">
                    <div v-for="log in syncLogs" :key="log.id" class="flex gap-4">
                        <span class="text-[#f38ba8]">[{{ log.created_at || '-' }}]</span>
                        <span :class="String(log.status).toLowerCase() === 'failed' ? 'text-[#f9e2af]' : 'text-[#a6e3a1]'">{{ String(log.status || 'INFO').toUpperCase() }}</span>
                        <span>{{ log.station_name || 'Station' }} {{ log.direction }} {{ log.topic }}<span v-if="log.error_message"> - {{ log.error_message }}</span></span>
                    </div>
                    <div v-if="syncLogs.length === 0" class="flex gap-4">
                        <span class="text-[#f38ba8]">[ready]</span>
                        <span class="text-[#89b4fa]">INFO</span>
                        <span>Sync log belum tersedia. Station akan mengisi log saat integrasi berjalan.</span>
                    </div>
                    <div class="flex gap-4">
                        <span class="text-[#f38ba8]">[storage]</span>
                        <span class="text-[#89b4fa]">INFO</span>
                        <span>Storage disk active: {{ storage.default_disk || 'public' }}.</span>
                    </div>
                </div>
            </section>

            <section id="settings" class="mt-5 rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm lg:p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Settings</h2>
                    <p class="mt-1 text-sm leading-6 text-[#434655]">Ubah password akun admin tenant.</p>
                </div>

                <p v-if="flash.message" class="mb-4 rounded-lg border border-[#dbe1ff] bg-[#eeefff] p-3 text-sm font-semibold text-[#003ea8]">{{ flash.message }}</p>

                <form class="grid gap-4 lg:max-w-xl" @submit.prevent="updatePassword">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Password Lama</span>
                        <input v-model="passwordForm.current_password" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]" type="password" autocomplete="current-password">
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs font-semibold text-red-600">{{ passwordForm.errors.current_password }}</p>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Password Baru</span>
                        <input v-model="passwordForm.password" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]" type="password" autocomplete="new-password">
                        <p v-if="passwordForm.errors.password" class="mt-1 text-xs font-semibold text-red-600">{{ passwordForm.errors.password }}</p>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Konfirmasi Password Baru</span>
                        <input v-model="passwordForm.password_confirmation" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]" type="password" autocomplete="new-password">
                    </label>

                    <button class="min-h-11 rounded-lg bg-[#004ac6] px-4 text-sm font-semibold text-white shadow-sm disabled:opacity-60 lg:w-fit" type="submit" :disabled="passwordForm.processing">
                        {{ passwordForm.processing ? 'Menyimpan...' : 'Update Password' }}
                    </button>
                </form>
            </section>
        </section>

        <nav class="fixed bottom-0 left-0 z-50 flex h-20 w-full items-center justify-around border-t border-[#c3c6d7] bg-[#ededf9] px-2 lg:hidden">
            <a
                v-for="[icon, label, href, active] in bottomItems"
                :key="label"
                class="flex flex-col items-center justify-center px-2 py-1.5 text-[#434655]"
                :class="{ 'rounded-xl bg-[#fea619] text-[#684000]': active }"
                :href="href"
            >
                <span class="text-[11px] font-bold">{{ icon }}</span>
                <span class="mt-1 text-xs font-semibold">{{ label }}</span>
            </a>
        </nav>

        <div class="fixed bottom-24 right-4 z-40 lg:hidden">
            <Link class="flex size-14 items-center justify-center rounded-xl bg-[#004ac6] text-2xl font-semibold text-white shadow-lg transition-transform active:scale-95" href="/admin/stations">+</Link>
        </div>
    </main>
</template>
