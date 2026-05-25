<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <main class="min-h-screen bg-[#F8FAFC] text-[#191b23]">
        <section class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 rounded-xl border border-[#c3c6d7]/40 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-medium text-[#434655]">Admin / Stations</p>
                    <h1 class="mt-1 text-2xl font-bold text-[#004ac6] sm:text-3xl">Station Token Management</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#434655]">
                        Token station dipakai Android/station untuk sync session, upload asset, dan polling print request.
                    </p>
                </div>
                <a class="min-h-11 rounded-lg border border-[#c3c6d7] bg-white px-4 py-3 text-sm font-semibold text-[#434655]" href="/admin">Kembali Dashboard</a>
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
    </main>
</template>
