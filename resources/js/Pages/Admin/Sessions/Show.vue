<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    customer: {
        type: Object,
        default: null,
    },
    station: {
        type: Object,
        default: null,
    },
    assets: {
        type: Array,
        default: () => [],
    },
    backToCustomerUrl: {
        type: String,
        default: null,
    },
    identityLabel: {
        type: String,
        required: true,
    },
    publicSessionCode: {
        type: String,
        required: true,
    },
    publicGalleryUrl: {
        type: String,
        required: true,
    },
});

const formatBytes = (value) => {
    const bytes = Number(value || 0);

    if (bytes < 1) {
        return '-';
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const logoutForm = useForm({});
const linkCustomerForm = useForm({
    customer_whatsapp: '',
    customer_name: '',
    customer_tier: 'regular',
});

const logout = () => {
    logoutForm.post('/admin/logout');
};

const linkCustomer = () => {
    linkCustomerForm.post(`/admin/sessions/${props.session.id}/link-customer`, {
        preserveScroll: true,
    });
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
    ['BS', 'Business', '#business', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['SE', 'Sessions', '/admin/sessions', true],
    ['SG', 'Settings', '#settings', false],
];

const statusClass = (status) => {
    const value = String(status ?? '').toLowerCase();

    if (['complete', 'uploaded', 'processed', 'active'].includes(value)) {
        return 'bg-green-100 text-[#10B981]';
    }

    if (['pending_upload', 'syncing'].includes(value)) {
        return 'bg-blue-100 text-[#3B82F6]';
    }

    if (['failed', 'cancelled'].includes(value)) {
        return 'bg-red-100 text-[#EF4444]';
    }

    return 'bg-slate-100 text-[#64748B]';
};

const isImage = (asset) => String(asset.mime_type || '').startsWith('image/');
const isGuestSession = computed(() => !props.customer);
const identityBadge = computed(() => isGuestSession.value
    ? { label: 'Guest', className: 'bg-amber-100 text-[#B45309]' }
    : { label: 'Customer', className: 'bg-green-100 text-[#10B981]' });
const whatsappUrl = computed(() => {
    if (!props.customer?.whatsapp_number) return null;

    return `https://wa.me/${props.customer.whatsapp_number}`;
});
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
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold transition-colors"
                        :class="active ? 'bg-[#dbe1ff] text-[#003ea8]' : 'text-[#434655] hover:bg-[#f3f3fe]'"
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
                    <p class="text-sm font-medium text-[#434655]">Admin / Session Detail</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase" :class="identityBadge.className">{{ identityBadge.label }}</span>
                        <span class="rounded-full bg-[#f3f3fe] px-3 py-1 text-xs font-black uppercase text-[#004ac6]">{{ session.sync_status }}</span>
                    </div>
                    <h1 class="mt-3 text-[22px] font-bold leading-[1.3] text-[#191b23] lg:text-[32px] lg:leading-[1.2]">{{ session.title }}</h1>
                    <p class="mt-2 text-sm text-[#434655]">{{ publicSessionCode }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#004ac6] hover:bg-[#f3f3fe]" :href="publicGalleryUrl" target="_blank" rel="noopener noreferrer">
                        Public Gallery
                    </a>
                    <a v-if="backToCustomerUrl" class="inline-flex min-h-11 items-center rounded-lg bg-[#004ac6] px-4 text-sm font-semibold text-white" :href="backToCustomerUrl">
                        Kembali Customer
                    </a>
                    <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin">
                        Kembali Dashboard
                    </Link>
                </div>
            </header>

            <section class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                <article class="rounded-xl border border-[#c3c6d7]/40 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Session</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <p><span class="text-[#737686]">Status:</span> <span class="rounded-full px-2 py-1 text-xs font-semibold uppercase" :class="statusClass(session.sync_status)">{{ session.sync_status }}</span></p>
                        <p><span class="text-[#737686]">Created:</span> {{ session.created_at || '-' }}</p>
                        <p><span class="text-[#737686]">Started:</span> {{ session.started_at || '-' }}</p>
                        <p><span class="text-[#737686]">Ended:</span> {{ session.ended_at || '-' }}</p>
                    </div>
                </article>

                <article class="rounded-xl border border-[#c3c6d7]/40 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Customer</h2>
                        <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase" :class="identityBadge.className">{{ identityBadge.label }}</span>
                    </div>
                    <div v-if="customer" class="mt-4 space-y-3 text-sm">
                        <p><span class="text-[#737686]">Name:</span> {{ customer.name || '-' }}</p>
                        <p><span class="text-[#737686]">WhatsApp:</span> {{ customer.whatsapp_number }}</p>
                        <p><span class="text-[#737686]">Status:</span> {{ customer.status }}</p>
                        <p><span class="text-[#737686]">Last login:</span> {{ customer.last_login_at || '-' }}</p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <a
                                v-if="whatsappUrl"
                                class="inline-flex min-h-10 items-center rounded-lg bg-[#10B981] px-3 text-xs font-black text-white"
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Chat WhatsApp
                            </a>
                            <a
                                class="inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] px-3 text-xs font-black text-[#004ac6] hover:bg-[#f3f3fe]"
                                :href="`/admin/customers/${customer.id}`"
                            >
                                Detail Customer
                            </a>
                        </div>
                    </div>
                    <div v-else class="mt-4 space-y-4">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-sm font-black text-[#B45309]">{{ identityLabel }}</p>
                            <p class="mt-1 text-xs leading-5 text-[#7C2D12]">Session ini belum punya WhatsApp customer. Isi nomor WhatsApp untuk menautkan archive ke customer portal.</p>
                        </div>
                        <form class="space-y-3" @submit.prevent="linkCustomer">
                            <label class="block text-xs font-semibold text-[#434655]">
                                WhatsApp Customer
                                <input v-model="linkCustomerForm.customer_whatsapp" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="6282118401998" type="text">
                                <span v-if="linkCustomerForm.errors.customer_whatsapp" class="mt-1 block text-xs text-[#EF4444]">{{ linkCustomerForm.errors.customer_whatsapp }}</span>
                            </label>
                            <label class="block text-xs font-semibold text-[#434655]">
                                Nama
                                <input v-model="linkCustomerForm.customer_name" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="Opsional" type="text">
                                <span v-if="linkCustomerForm.errors.customer_name" class="mt-1 block text-xs text-[#EF4444]">{{ linkCustomerForm.errors.customer_name }}</span>
                            </label>
                            <label class="block text-xs font-semibold text-[#434655]">
                                Tier
                                <select v-model="linkCustomerForm.customer_tier" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]">
                                    <option value="regular">Regular</option>
                                    <option value="premium">Premium</option>
                                </select>
                                <span v-if="linkCustomerForm.errors.customer_tier" class="mt-1 block text-xs text-[#EF4444]">{{ linkCustomerForm.errors.customer_tier }}</span>
                            </label>
                            <button class="min-h-11 w-full rounded-lg bg-[#004ac6] px-4 text-sm font-black text-white disabled:opacity-60" type="submit" :disabled="linkCustomerForm.processing">
                                Link Customer
                            </button>
                        </form>
                    </div>
                </article>

                <article class="rounded-xl border border-[#c3c6d7]/40 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Station</h2>
                    <div v-if="station" class="mt-4 space-y-3 text-sm">
                        <p><span class="text-[#737686]">Name:</span> {{ station.name }}</p>
                        <p><span class="text-[#737686]">Code:</span> {{ station.code }}</p>
                        <p><span class="text-[#737686]">Status:</span> {{ station.status }}</p>
                        <p><span class="text-[#737686]">Last seen:</span> {{ station.last_seen_at || '-' }}</p>
                    </div>
                    <p v-else class="mt-4 text-sm text-[#434655]">Station belum tersedia.</p>
                </article>
            </section>

            <section class="mt-5 overflow-hidden rounded-xl border border-[#c3c6d7]/40 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#c3c6d7]/40 px-5 py-4">
                    <h2 class="text-lg font-semibold">Assets</h2>
                    <span class="text-sm font-semibold text-[#434655]">{{ assets.length }} asset</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead class="bg-[#f3f3fe]">
                            <tr>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">Type</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">Preview</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">Station Asset</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">Status</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">File</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">Size</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase text-[#434655]">Dimension</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c3c6d7]/40">
                            <tr v-for="asset in assets" :key="asset.id">
                                <td class="px-5 py-4 text-sm font-semibold">{{ asset.type }}</td>
                                <td class="px-5 py-4">
                                    <a
                                        v-if="asset.file_url && isImage(asset)"
                                        class="block size-16 overflow-hidden rounded-lg border border-[#c3c6d7] bg-[#f3f3fe]"
                                        :href="asset.file_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <img class="size-full object-cover" :src="asset.file_url" :alt="asset.station_asset_id" loading="lazy">
                                    </a>
                                    <a
                                        v-else-if="asset.file_url"
                                        class="inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] px-3 text-xs font-semibold text-[#004ac6] hover:bg-[#f3f3fe]"
                                        :href="asset.file_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Open
                                    </a>
                                    <span v-else class="text-xs text-[#737686]">No file</span>
                                </td>
                                <td class="px-5 py-4 text-sm">{{ asset.station_asset_id }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold uppercase" :class="statusClass(asset.status)">{{ asset.status }}</span></td>
                                <td class="max-w-[360px] px-5 py-4 text-xs text-[#434655]">
                                    <p class="break-all">{{ asset.path }}</p>
                                    <p class="mt-1">{{ asset.mime_type || '-' }}</p>
                                    <a
                                        v-if="asset.file_url"
                                        class="mt-2 inline-flex min-h-9 items-center rounded-lg bg-[#004ac6] px-3 text-xs font-semibold text-white"
                                        :href="asset.file_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Open File
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-sm">{{ formatBytes(asset.size_bytes) }}</td>
                                <td class="px-5 py-4 text-sm">{{ asset.width && asset.height ? `${asset.width} x ${asset.height}` : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="assets.length === 0" class="p-5 text-sm text-[#434655]">Belum ada asset untuk session ini.</p>
                </div>
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
    </main>
</template>
