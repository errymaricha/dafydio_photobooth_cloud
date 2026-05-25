<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPagination from '@/Components/AdminPagination.vue';

defineProps({
    stations: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const logoutForm = useForm({});
const tokenForm = useForm({});
const createForm = useForm({
    name: '',
    code: '',
    device_identifier: '',
    status: 'active',
    generate_token: true,
});

const createStation = () => {
    createForm
        .transform((data) => ({
            ...data,
            code: data.code.trim().toUpperCase(),
            name: data.name.trim(),
            device_identifier: data.device_identifier?.trim() || null,
        }))
        .post('/admin/stations', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createForm.status = 'active';
                createForm.generate_token = true;
            },
        });
};

const regenerateToken = (station) => {
    tokenForm.post(`/admin/stations/${station.id}/token`, {
        preserveScroll: true,
    });
};

const logout = () => {
    logoutForm.post('/admin/logout');
};

const sidebarItems = [
    ['DB', 'Overview', '/admin', false],
    ['ST', 'Stations', '/admin/stations', true],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['TP', 'Templates', '/admin/templates', false],
    ['PR', 'Print Requests', '#prints', false],
    ['BI', 'Billing', '/admin/payments', false],
    ['LG', 'Logs', '/admin/sync-logs', false],
    ['SG', 'Settings', '#settings', false],
];

const bottomItems = [
    ['DB', 'Dashboard', '/admin', false],
    ['ST', 'Stations', '/admin/stations', true],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['LG', 'Logs', '/admin/sync-logs', false],
];
</script>

<template>
    <main class="min-h-screen bg-[#F8FAFC] text-[#191b23]">
        <header class="fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between bg-[#faf8ff] px-4 shadow-sm lg:hidden">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#dbe1ff] text-xs font-bold text-[#003ea8]">ST</span>
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
                    <h1 class="text-[22px] font-bold leading-[1.3] lg:text-[32px]">Stations</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-[#434655]">
                        Token station dipakai Android/station untuk sync session, upload asset, dan polling print request.
                    </p>
                </div>
                <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin">
                    Dashboard
                </Link>
            </header>

            <section v-if="flash.message" class="mt-5 rounded-xl border border-[#dbe1ff] bg-[#eeefff] p-4 text-sm leading-6 text-[#00174b]">
                <p class="font-semibold">{{ flash.message }}</p>
                <div v-if="flash.station_token" class="mt-3 rounded-lg border border-[#c3c6d7] bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#004ac6]">Token Baru - {{ flash.station_token.station_code }}</p>
                    <code class="mt-2 block break-all text-sm text-[#191b23]">{{ flash.station_token.token }}</code>
                    <p class="mt-2 text-xs font-semibold text-[#737686]">Simpan token ini sekarang. Token tidak akan ditampilkan lagi setelah halaman berubah.</p>
                </div>
            </section>

            <section class="mt-5 rounded-xl border border-[#c3c6d7]/40 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-[#004ac6]">Tambah Station</p>
                        <h2 class="mt-1 text-xl font-bold">Daftarkan station baru</h2>
                        <p class="mt-1 text-sm leading-6 text-[#434655]">Buat station agar aplikasi station bisa heartbeat, sync session, upload asset, dan polling print request.</p>
                    </div>
                </div>

                <form class="grid gap-4 lg:grid-cols-[1fr_180px_1fr_160px_auto] lg:items-end" @submit.prevent="createStation">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Nama station</span>
                        <input v-model="createForm.name" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="Booth Utama" type="text">
                        <p v-if="createForm.errors.name" class="mt-1 text-xs font-semibold text-red-600">{{ createForm.errors.name }}</p>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Kode</span>
                        <input v-model="createForm.code" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm uppercase outline-none focus:border-[#004ac6]" placeholder="ST-001" type="text">
                        <p v-if="createForm.errors.code" class="mt-1 text-xs font-semibold text-red-600">{{ createForm.errors.code }}</p>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Device ID opsional</span>
                        <input v-model="createForm.device_identifier" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="android-device-id" type="text">
                        <p v-if="createForm.errors.device_identifier" class="mt-1 text-xs font-semibold text-red-600">{{ createForm.errors.device_identifier }}</p>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#737686]">Status</span>
                        <select v-model="createForm.status" class="mt-2 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm font-semibold outline-none focus:border-[#004ac6]">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        <p v-if="createForm.errors.status" class="mt-1 text-xs font-semibold text-red-600">{{ createForm.errors.status }}</p>
                    </label>

                    <div class="space-y-3">
                        <label class="flex min-h-11 items-center gap-2 rounded-lg border border-[#c3c6d7] px-3 text-xs font-semibold text-[#434655]">
                            <input v-model="createForm.generate_token" class="rounded border-[#c3c6d7] text-[#004ac6]" type="checkbox">
                            Buat token
                        </label>
                        <button class="min-h-11 w-full rounded-lg bg-[#004ac6] px-4 text-sm font-semibold text-white shadow-sm disabled:opacity-60" type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Menyimpan...' : 'Tambah' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="mt-5 overflow-hidden rounded-xl border border-[#c3c6d7]/40 bg-white shadow-sm">
                <div class="hidden grid-cols-[1fr_1fr_1fr_1fr_auto] gap-3 border-b border-[#c3c6d7]/40 bg-[#f3f3fe] p-4 text-xs font-semibold uppercase tracking-wide text-[#737686] lg:grid">
                    <span>Station</span>
                    <span>Device</span>
                    <span>Last Seen</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <article v-for="station in stations.data" :key="station.id" class="grid gap-4 border-b border-[#c3c6d7]/40 p-4 last:border-b-0 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-center">
                    <div>
                        <p class="font-semibold">{{ station.name }}</p>
                        <p class="mt-1 text-sm text-[#434655]">{{ station.code }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ station.device_identifier || 'Belum ada device' }}</p>
                        <p class="mt-1 text-xs text-[#737686]">{{ station.app_version || 'App version belum sync' }}</p>
                    </div>
                    <p class="text-sm text-[#434655]">{{ station.last_seen_at || 'Belum pernah heartbeat' }}</p>
                    <div>
                        <span class="inline-block rounded-lg border border-[#dbe1ff] bg-[#eeefff] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-[#004ac6]">{{ station.status }}</span>
                        <p class="mt-2 text-xs text-[#737686]">{{ station.has_token ? 'Token tersimpan' : 'Belum ada token' }}</p>
                    </div>
                    <button
                        class="min-h-11 rounded-lg bg-[#004ac6] px-4 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
                        type="button"
                        :disabled="tokenForm.processing"
                        @click="regenerateToken(station)"
                    >
                        Regenerate Token
                    </button>
                </article>

                <div v-if="stations.data.length === 0" class="p-6 text-sm text-[#434655]">Belum ada station untuk tenant ini.</div>
                <AdminPagination :paginator="stations" />
            </section>
        </section>

        <nav class="fixed bottom-0 left-0 z-50 flex h-20 w-full items-center justify-around border-t border-[#c3c6d7] bg-[#ededf9] px-2 lg:hidden">
            <template v-for="([icon, label, href, active]) in bottomItems" :key="label">
                <Link class="flex flex-col items-center justify-center rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors" :class="active ? 'bg-[#fea619] text-[#684000]' : 'text-[#434655] hover:bg-[#e7e7f3]'" :href="href">
                    <span class="text-sm font-bold">{{ icon }}</span>
                    <span class="mt-1">{{ label }}</span>
                </Link>
            </template>
        </nav>
    </main>
</template>
