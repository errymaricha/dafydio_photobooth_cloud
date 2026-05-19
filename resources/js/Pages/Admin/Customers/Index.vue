<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminPagination from '@/Components/AdminPagination.vue';

const props = defineProps({
    customers: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '', plan: 'all' }),
    },
});

const logoutForm = useForm({});
const logout = () => logoutForm.post('/admin/logout');
const editingCustomerId = ref(null);

const filterForm = useForm({
    q: props.filters.q || '',
    plan: props.filters.plan || 'all',
});

const renameForm = useForm({
    name: '',
});

const applyFilters = () => filterForm.get('/admin/customers', {
    preserveScroll: true,
    preserveState: true,
});

const startRename = (customer) => {
    editingCustomerId.value = customer.id;
    renameForm.clearErrors();
    renameForm.name = customer.name || '';
};

const cancelRename = () => {
    editingCustomerId.value = null;
    renameForm.reset();
    renameForm.clearErrors();
};

const saveRename = (customer) => {
    renameForm.patch(`/admin/customers/${customer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingCustomerId.value = null;
            router.reload({ only: ['customers'] });
        },
    });
};

const hasActiveFilters = computed(() => (
    (props.filters.q || '') !== ''
    || props.filters.plan !== 'all'
));

const statusClass = (status) => String(status).toLowerCase() === 'active' ? 'bg-green-100 text-[#10B981]' : 'bg-slate-100 text-[#64748B]';

const planOptions = [
    ['Semua plan', 'all'],
    ['Regular', 'regular'],
    ['Premium', 'premium'],
];

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
                    <h1 class="text-[22px] font-bold leading-[1.3] lg:text-[32px]">Customers</h1>
                    <p class="mt-1 text-sm leading-6 text-[#434655]">Customer yang pernah sync dari station atau login portal.</p>
                </div>
                <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin">
                    Dashboard
                </Link>
            </header>

            <form class="mb-4 grid gap-3 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm lg:grid-cols-[1fr_180px_auto_auto] lg:items-end" @submit.prevent="applyFilters">
                <label class="block text-xs font-semibold text-[#434655]">
                    Cari WhatsApp, customer, plan
                    <input v-model="filterForm.q" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] px-3 text-sm outline-none focus:border-[#004ac6]" placeholder="628... / nama customer / premium" type="search">
                </label>
                <label class="block text-xs font-semibold text-[#434655]">
                    Plan
                    <select v-model="filterForm.plan" class="mt-1 min-h-11 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]">
                        <option v-for="[label, value] in planOptions" :key="value" :value="value">{{ label }}</option>
                    </select>
                </label>
                <button class="min-h-11 rounded-lg bg-[#004ac6] px-5 text-sm font-black text-white" type="submit">
                    Filter
                </button>
                <Link v-if="hasActiveFilters" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-[#c3c6d7] bg-white px-5 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin/customers">
                    Reset
                </Link>
            </form>

            <section class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <div class="hidden grid-cols-[1.3fr_1fr_1fr_1fr_1fr_auto] gap-3 border-b border-[#c3c6d7] bg-[#f3f3fe] p-4 text-xs font-semibold uppercase tracking-wide text-[#737686] lg:grid">
                    <span>Customer</span>
                    <span>Plan</span>
                    <span>Sessions</span>
                    <span>Prints</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <article v-for="customer in customers.data" :key="customer.id" class="grid gap-4 border-b border-[#c3c6d7]/50 p-4 last:border-b-0 lg:grid-cols-[1.3fr_1fr_1fr_1fr_1fr_auto] lg:items-center">
                    <div>
                        <form v-if="editingCustomerId === customer.id" class="space-y-2" @submit.prevent="saveRename(customer)">
                            <label class="sr-only" :for="`customer-name-${customer.id}`">Nama customer</label>
                            <input
                                :id="`customer-name-${customer.id}`"
                                v-model="renameForm.name"
                                class="min-h-11 w-full rounded-lg border border-[#c3c6d7] px-3 text-sm font-semibold outline-none focus:border-[#004ac6]"
                                maxlength="255"
                                placeholder="Nama customer"
                                type="text"
                            >
                            <p v-if="renameForm.errors.name" class="text-xs font-semibold text-[#EF4444]">{{ renameForm.errors.name }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button class="inline-flex min-h-10 items-center rounded-lg bg-[#004ac6] px-3 text-xs font-semibold text-white disabled:opacity-60" type="submit" :disabled="renameForm.processing">
                                    {{ renameForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                                <button class="inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] bg-white px-3 text-xs font-semibold text-[#434655]" type="button" @click="cancelRename">
                                    Batal
                                </button>
                            </div>
                        </form>
                        <div v-else class="flex items-start justify-between gap-3 lg:block">
                            <p class="font-semibold">{{ customer.name || 'Customer' }}</p>
                            <button class="inline-flex min-h-8 shrink-0 items-center rounded-lg border border-[#c3c6d7] bg-white px-2 text-[11px] font-semibold text-[#004ac6] hover:bg-[#f3f3fe] lg:mt-2" type="button" @click="startRename(customer)">
                                Edit Nama
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-[#434655]">{{ customer.whatsapp_number }}</p>
                        <p class="mt-1 text-xs text-[#737686]">Login: {{ customer.last_login_at || '-' }}</p>
                    </div>
                    <p class="text-sm font-semibold capitalize text-[#004ac6]">{{ customer.subscription_plan }}</p>
                    <p class="text-sm text-[#434655]">{{ customer.sessions_count ?? 0 }} sessions</p>
                    <p class="text-sm text-[#434655]">{{ customer.print_requests_count ?? 0 }} requests</p>
                    <div>
                        <span class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase" :class="statusClass(customer.status)">{{ customer.status }}</span>
                        <p class="mt-2 text-xs text-[#737686]">{{ customer.created_at || '-' }}</p>
                    </div>
                    <Link class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#004ac6] px-4 text-xs font-semibold text-white" :href="`/admin/customers/${customer.id}`">
                        Detail
                    </Link>
                </article>

                <p v-if="customers.data.length === 0" class="p-6 text-sm text-[#434655]">Belum ada customer.</p>
                <AdminPagination :paginator="customers" />
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
