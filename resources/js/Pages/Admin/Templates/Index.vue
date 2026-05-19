<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminPagination from '@/Components/AdminPagination.vue';

const props = defineProps({
    templates: {
        type: Object,
        required: true,
    },
    metrics: {
        type: Object,
        default: () => ({}),
    },
});

const editingTemplate = ref(null);
const logoutForm = useForm({});
const form = useForm({
    name: '',
    description: '',
    access_level: 'marketplace',
    price_amount: 0,
    price_currency: 'IDR',
    preview_path: '',
    source_path: '',
    preview_file: null,
    source_file: null,
    status: 'active',
});

const templateRows = computed(() => props.templates.data || []);

const sidebarItems = [
    ['DB', 'Overview', '/admin', false],
    ['ST', 'Stations', '/admin/stations', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['TP', 'Templates', '/admin/templates', true],
    ['PR', 'Print Requests', '#prints', false],
    ['LG', 'Logs', '#logs', false],
    ['SG', 'Settings', '#settings', false],
];

const bottomItems = [
    ['DB', 'Dashboard', '/admin', false],
    ['CU', 'Customers', '/admin/customers', false],
    ['SE', 'Sessions', '/admin/sessions', false],
    ['TP', 'Templates', '/admin/templates', true],
];

const resetForm = () => {
    editingTemplate.value = null;
    form.reset();
    form.clearErrors();
    form.access_level = 'marketplace';
    form.price_amount = 0;
    form.price_currency = 'IDR';
    form.status = 'active';
};

const editTemplate = (template) => {
    editingTemplate.value = template;
    form.clearErrors();
    form.name = template.name || '';
    form.description = template.description || '';
    form.access_level = template.access_level || 'marketplace';
    form.price_amount = template.price_amount ?? 0;
    form.price_currency = template.price_currency || 'IDR';
    form.preview_path = template.preview_path || '';
    form.source_path = template.source_path || '';
    form.preview_file = null;
    form.source_file = null;
    form.status = template.status || 'active';
};

const submitTemplate = () => {
    if (editingTemplate.value) {
        form.patch(`/admin/templates/${editingTemplate.value.id}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });
        return;
    }

    form.post('/admin/templates', {
        preserveScroll: true,
        onSuccess: resetForm,
    });
};

const deleteTemplate = (template) => {
    if (!window.confirm(`Hapus template ${template.name}?`)) return;

    form.delete(`/admin/templates/${template.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingTemplate.value?.id === template.id) resetForm();
        },
    });
};

const logout = () => {
    logoutForm.post('/admin/logout');
};

const priceLabel = (template) => {
    const amount = Number(template.price_amount || 0);

    if (amount <= 0) return 'Free';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: template.price_currency || 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};

const badgeClass = (value) => {
    if (value === 'active' || value === 'marketplace') return 'bg-green-100 text-[#10B981]';
    if (value === 'premium') return 'bg-[#dbe1ff] text-[#003ea8]';
    if (value === 'draft') return 'bg-blue-100 text-[#3B82F6]';
    if (value === 'archived' || value === 'private') return 'bg-slate-100 text-[#64748B]';

    return 'bg-slate-100 text-[#64748B]';
};
</script>

<template>
    <main class="min-h-screen bg-[#F8FAFC] text-[#191b23]">
        <header class="fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between bg-[#faf8ff] px-4 shadow-sm lg:hidden">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#dbe1ff] text-xs font-bold text-[#003ea8]">TP</span>
                <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="text-xl font-bold text-[#004ac6]">Dafydio</span></span>
            </div>
            <button class="flex size-9 items-center justify-center rounded-full border border-[#c3c6d7] bg-white text-xs font-bold text-[#004ac6]" type="button" @click="logout">AD</button>
        </header>

        <aside class="fixed inset-y-0 left-0 z-50 hidden w-[260px] flex-col border-r border-[#c3c6d7] bg-white lg:flex">
            <div class="flex h-16 items-center border-b border-[#c3c6d7] px-6">
                <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="text-xl font-bold text-[#004ac6]">Dafydio</span></span>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <template v-for="([icon, label, href, active]) in sidebarItems" :key="label">
                    <Link v-if="href.startsWith('/')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold transition-colors" :class="active ? 'bg-[#dbe1ff] text-[#003ea8]' : 'text-[#434655] hover:bg-[#f3f3fe]'" :href="href">
                        <span class="flex size-7 items-center justify-center rounded-md bg-white/70 text-[10px] font-bold">{{ icon }}</span>
                        {{ label }}
                    </Link>
                    <a v-else class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold text-[#434655] transition-colors hover:bg-[#f3f3fe]" :href="href">
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
                    <h1 class="text-[22px] font-bold leading-[1.3] lg:text-[32px]">Template Marketplace</h1>
                    <p class="mt-1 text-sm leading-6 text-[#434655]">Kelola template yang bisa dibeli atau dipakai customer premium.</p>
                </div>
                <Link class="inline-flex min-h-11 items-center rounded-lg border border-[#c3c6d7] bg-white px-4 text-sm font-semibold text-[#434655] hover:bg-[#f3f3fe]" href="/admin">
                    Dashboard
                </Link>
            </header>

            <section class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">Total</p>
                    <p class="mt-2 text-2xl font-black text-[#004ac6]">{{ metrics.total ?? 0 }}</p>
                </article>
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">Active</p>
                    <p class="mt-2 text-2xl font-black text-[#004ac6]">{{ metrics.active ?? 0 }}</p>
                </article>
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">Marketplace</p>
                    <p class="mt-2 text-2xl font-black text-[#004ac6]">{{ metrics.marketplace ?? 0 }}</p>
                </article>
                <article class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">Premium</p>
                    <p class="mt-2 text-2xl font-black text-[#004ac6]">{{ metrics.premium ?? 0 }}</p>
                </article>
            </section>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                <section class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm xl:col-span-4">
                    <h2 class="text-xl font-black">{{ editingTemplate ? 'Edit Template' : 'Tambah Template' }}</h2>
                    <form class="mt-4 space-y-4" @submit.prevent="submitTemplate">
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Nama</span>
                            <input v-model="form.name" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm" type="text" placeholder="Wedding Classic">
                            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Deskripsi</span>
                            <textarea v-model="form.description" class="mt-1 min-h-24 w-full rounded-xl border border-[#c3c6d7] px-3 py-2 text-sm" placeholder="Deskripsi singkat template"></textarea>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Access</span>
                                <select v-model="form.access_level" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm">
                                    <option value="marketplace">Marketplace</option>
                                    <option value="premium">Premium</option>
                                    <option value="private">Private</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Status</span>
                                <select v-model="form.status" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm">
                                    <option value="active">Active</option>
                                    <option value="draft">Draft</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Harga</span>
                                <input v-model="form.price_amount" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm" type="number" min="0">
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Currency</span>
                                <input v-model="form.price_currency" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm" type="text" maxlength="3">
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Preview Path / URL</span>
                            <input v-model="form.preview_path" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm" type="text" placeholder="templates/preview.jpg">
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Upload Preview Terbatas</span>
                            <input class="mt-1 w-full rounded-xl border border-[#c3c6d7] px-3 py-2 text-sm" type="file" accept="image/jpeg,image/png,image/webp" @input="form.preview_file = $event.target.files[0]">
                            <span v-if="form.errors.preview_file" class="mt-1 block text-xs text-red-600">{{ form.errors.preview_file }}</span>
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Source Path</span>
                            <input v-model="form.source_path" class="mt-1 min-h-11 w-full rounded-xl border border-[#c3c6d7] px-3 text-sm" type="text" placeholder="templates/source.json">
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Upload Frame/Source Terbatas</span>
                            <input class="mt-1 w-full rounded-xl border border-[#c3c6d7] px-3 py-2 text-sm" type="file" accept="image/jpeg,image/png,image/webp,application/json,application/zip" @input="form.source_file = $event.target.files[0]">
                            <span v-if="form.errors.source_file" class="mt-1 block text-xs text-red-600">{{ form.errors.source_file }}</span>
                        </label>
                        <div class="flex gap-3">
                            <button class="min-h-12 flex-1 rounded-xl bg-[#004ac6] px-4 text-sm font-black text-white disabled:opacity-60" type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : (editingTemplate ? 'Update' : 'Create') }}
                            </button>
                            <button v-if="editingTemplate" class="min-h-12 rounded-xl border border-[#c3c6d7] px-4 text-sm font-black text-[#004ac6]" type="button" @click="resetForm">Cancel</button>
                        </div>
                    </form>
                </section>

                <section class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm xl:col-span-8">
                    <div class="border-b border-[#c3c6d7] px-4 py-4">
                        <h2 class="text-xl font-black">Daftar Template</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        <article v-for="template in templateRows" :key="template.id" class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                            <div class="flex aspect-[4/3] items-center justify-center bg-[#f3f3fe]">
                                <img v-if="template.preview_url" class="h-full w-full object-cover" :src="template.preview_url" :alt="template.name">
                                <div v-else class="px-4 text-center">
                                    <img class="size-14 rounded-xl object-cover shadow-sm" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                                    <p class="mt-1 text-sm font-bold text-[#737686]">Preview belum diisi</p>
                                </div>
                            </div>
                            <div class="space-y-3 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-black">{{ template.name }}</h3>
                                        <p class="mt-1 line-clamp-2 text-sm leading-6 text-[#434655]">{{ template.description || 'Tidak ada deskripsi.' }}</p>
                                        <p v-if="template.template_code || template.category || template.paper_size" class="mt-2 truncate text-xs font-bold uppercase tracking-wide text-[#737686]">
                                            {{ template.template_code || 'No Code' }} - {{ template.category || 'No Category' }} - {{ template.paper_size || 'No Size' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-black uppercase" :class="badgeClass(template.status)">{{ template.status }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase" :class="badgeClass(template.access_level)">{{ template.access_level }}</span>
                                    <span class="rounded-full bg-[#f3f3fe] px-2 py-1 text-[10px] font-black text-[#004ac6]">{{ priceLabel(template) }}</span>
                                    <span v-if="template.station_template_id" class="rounded-full bg-[#ffddb8] px-2 py-1 text-[10px] font-black text-[#855300]">Station Sync</span>
                                    <span v-if="template.slots_count" class="rounded-full bg-[#f3f3fe] px-2 py-1 text-[10px] font-black text-[#004ac6]">{{ template.slots_count }} slot</span>
                                    <span v-if="template.assets_count" class="rounded-full bg-[#f3f3fe] px-2 py-1 text-[10px] font-black text-[#004ac6]">{{ template.assets_count }} asset</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button class="min-h-10 rounded-lg bg-[#004ac6] text-xs font-black text-white" type="button" @click="editTemplate(template)">Edit</button>
                                    <button class="min-h-10 rounded-lg border border-red-200 text-xs font-black text-red-600" type="button" @click="deleteTemplate(template)">Delete</button>
                                </div>
                            </div>
                        </article>
                        <p v-if="templateRows.length === 0" class="rounded-xl border border-[#c3c6d7] p-5 text-sm text-[#434655] md:col-span-2">Belum ada template.</p>
                    </div>
                    <AdminPagination :paginator="templates" />
                </section>
            </div>
        </section>

        <nav class="fixed bottom-0 left-0 z-50 flex h-20 w-full items-center justify-around border-t border-[#c3c6d7] bg-[#ededf9] px-2 lg:hidden">
            <a v-for="[icon, label, href, active] in bottomItems" :key="label" class="flex flex-col items-center justify-center px-2 py-1.5 text-[#434655]" :class="{ 'rounded-xl bg-[#fea619] text-[#684000]': active }" :href="href">
                <span class="text-[11px] font-bold">{{ icon }}</span>
                <span class="mt-1 text-xs font-semibold">{{ label }}</span>
            </a>
        </nav>
    </main>
</template>
