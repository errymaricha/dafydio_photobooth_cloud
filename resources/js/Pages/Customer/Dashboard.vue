<script setup>
import { computed, onMounted, ref } from 'vue';

const customer = ref(null);
const sessions = ref([]);
const templates = ref([]);
const payments = ref([]);
const editJobs = ref([]);
const printRequests = ref([]);
const loading = ref(true);
const templatesLoading = ref(false);
const paymentsLoading = ref(false);
const editJobsLoading = ref(false);
const printRequestsLoading = ref(false);
const errorMessage = ref('');
const actionMessage = ref('');
const actionError = ref('');
const processingAssetId = ref(null);
const processingTemplateId = ref(null);
const processingEditAssetId = ref(null);
const selectedSession = ref(null);
const activeView = ref('sessions');
const selectedTemplateId = ref('');
const askingName = ref(false);
const profileName = ref('');
const profileSaving = ref(false);

const customerName = computed(() => customer.value?.name || 'Customer');
const visibleSessions = computed(() => sessions.value);
const ownedTemplates = computed(() => templates.value.filter((template) => template.is_owned || template.is_premium_included));
const pendingPayments = computed(() => payments.value.filter((payment) => payment.status === 'pending'));
const activePrintRequests = computed(() => printRequests.value.filter((request) => !['printed', 'failed'].includes(request.status)));
const totalAssets = computed(() => sessions.value.reduce((total, session) => total + (session.assets?.length || 0), 0));
const uploadedAssets = computed(() => sessions.value.reduce((total, session) => total + uploadedSessionAssets(session).length, 0));
const latestSession = computed(() => visibleSessions.value[0] || null);
const readyTemplates = computed(() => ownedTemplates.value);

const customerHeaders = () => ({
    Authorization: `Bearer ${localStorage.getItem('dafydio_customer_token')}`,
});

const redirectToLogin = () => {
    localStorage.removeItem('dafydio_customer_token');
    localStorage.removeItem('dafydio_customer');
    window.location.href = '/login?mode=customer';
};

const uploadedSessionAssets = (session) => session.assets?.filter((asset) => asset.status === 'uploaded') || [];
const firstAsset = (session) => uploadedSessionAssets(session).find((asset) => asset.type === 'framed') || uploadedSessionAssets(session)[0] || null;
const framedCount = (session) => uploadedSessionAssets(session).filter((asset) => asset.type === 'framed').length;
const originalCount = (session) => uploadedSessionAssets(session).filter((asset) => asset.type === 'original').length;

const formatDate = (value) => {
    if (!value) return 'Recent session';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const formatBytes = (value) => {
    const bytes = Number(value || 0);

    if (bytes < 1) return '-';
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const loadStoredCustomer = () => {
    const storedCustomer = localStorage.getItem('dafydio_customer');

    if (!storedCustomer) return;

    try {
        customer.value = JSON.parse(storedCustomer);
        profileName.value = customer.value?.name || '';
        askingName.value = !customer.value?.name;
    } catch {
        localStorage.removeItem('dafydio_customer');
    }
};

const saveName = async () => {
    const name = profileName.value.trim();

    actionMessage.value = '';
    actionError.value = '';

    if (!name) {
        actionError.value = 'Nama perlu diisi dulu.';
        return;
    }

    profileSaving.value = true;

    try {
        const response = await window.axios.patch('/api/customer/profile', {
            name,
        }, {
            headers: customerHeaders(),
        });

        customer.value = response.data.data.customer;
        localStorage.setItem('dafydio_customer', JSON.stringify(customer.value));
        profileName.value = customer.value.name || '';
        askingName.value = false;
        actionMessage.value = response.data.message || 'Nama berhasil disimpan.';
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = error.response?.data?.message || 'Nama belum bisa disimpan.';
    } finally {
        profileSaving.value = false;
    }
};

const fetchSessions = async () => {
    loadStoredCustomer();

    if (!localStorage.getItem('dafydio_customer_token')) {
        redirectToLogin();
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.get('/api/customer/sessions', {
            headers: customerHeaders(),
        });

        sessions.value = response.data.data || [];
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        errorMessage.value = 'Session belum bisa dimuat. Coba refresh halaman.';
    } finally {
        loading.value = false;
    }
};

const fetchTemplates = async () => {
    if (!localStorage.getItem('dafydio_customer_token')) {
        redirectToLogin();
        return;
    }

    templatesLoading.value = true;

    try {
        const response = await window.axios.get('/api/customer/templates', {
            headers: customerHeaders(),
        });

        templates.value = response.data.data || [];
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = 'Template marketplace belum bisa dimuat.';
    } finally {
        templatesLoading.value = false;
    }
};

const fetchEditJobs = async () => {
    if (!localStorage.getItem('dafydio_customer_token')) {
        redirectToLogin();
        return;
    }

    editJobsLoading.value = true;

    try {
        const response = await window.axios.get('/api/customer/edit-jobs', {
            headers: customerHeaders(),
        });

        editJobs.value = response.data.data || [];
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = 'Edit jobs belum bisa dimuat.';
    } finally {
        editJobsLoading.value = false;
    }
};

const fetchPrintRequests = async () => {
    if (!localStorage.getItem('dafydio_customer_token')) {
        redirectToLogin();
        return;
    }

    printRequestsLoading.value = true;

    try {
        const response = await window.axios.get('/api/customer/print-requests', {
            headers: customerHeaders(),
        });

        printRequests.value = response.data.data || [];
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = 'Print request belum bisa dimuat.';
    } finally {
        printRequestsLoading.value = false;
    }
};

const downloadAsset = async (session, preferredAsset = null) => {
    const asset = preferredAsset || firstAsset(session);

    actionMessage.value = '';
    actionError.value = '';

    if (!asset) {
        actionError.value = 'Asset belum tersedia untuk session ini.';
        return;
    }

    processingAssetId.value = asset.id;

    try {
        const response = await window.axios.post(`/api/customer/assets/${asset.id}/download-url`, {}, {
            headers: customerHeaders(),
        });

        window.open(response.data.data.download_url, '_blank', 'noopener,noreferrer');
        actionMessage.value = 'Download link berhasil dibuat.';
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = error.response?.data?.message || 'Download belum bisa diproses.';
    } finally {
        processingAssetId.value = null;
    }
};

const requestPrint = async (session, preferredAsset = null) => {
    const asset = preferredAsset || firstAsset(session);

    actionMessage.value = '';
    actionError.value = '';

    if (!asset) {
        actionError.value = 'Asset belum tersedia untuk dicetak.';
        return;
    }

    processingAssetId.value = asset.id;

    try {
        await window.axios.post('/api/customer/print-requests', {
            cloud_session_id: session.id,
            cloud_session_asset_id: asset.id,
            quantity: 1,
        }, {
            headers: customerHeaders(),
        });

        actionMessage.value = 'Print request berhasil dibuat.';
        await fetchPrintRequests();
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = error.response?.data?.message || 'Print request belum bisa dibuat.';
    } finally {
        processingAssetId.value = null;
    }
};

const shareGalleryUrl = (session) => {
    const text = [
        'Dafydio Photobooth',
        session.title,
        `Session: ${session.session_code || session.station_session_id}`,
        session.public_url,
    ].filter(Boolean).join('\n');

    return `https://wa.me/?text=${encodeURIComponent(text)}`;
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

const paymentMoney = (payment) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: payment.currency || 'IDR',
    maximumFractionDigits: 0,
}).format(Number(payment.amount || 0));

const templateStatusLabel = (template) => {
    if (template.is_owned) return 'Owned';
    if (template.is_premium_included) return 'Premium';
    if (template.access_level === 'premium') return 'Premium Only';

    return priceLabel(template);
};

const fetchPayments = async () => {
    if (!localStorage.getItem('dafydio_customer_token')) {
        redirectToLogin();
        return;
    }

    paymentsLoading.value = true;

    try {
        const response = await window.axios.get('/api/customer/payments', {
            headers: customerHeaders(),
        });

        payments.value = response.data.data || [];
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = 'Status pembayaran belum bisa dimuat.';
    } finally {
        paymentsLoading.value = false;
    }
};

const purchaseTemplate = async (template) => {
    actionMessage.value = '';
    actionError.value = '';

    if (template.is_owned || template.is_premium_included) {
        actionMessage.value = 'Template sudah bisa dipakai.';
        return;
    }

    if (template.access_level !== 'marketplace') {
        actionError.value = 'Template ini hanya untuk member premium.';
        return;
    }

    processingTemplateId.value = template.id;

    try {
        const response = await window.axios.post(`/api/customer/templates/${template.id}/purchase`, {}, {
            headers: customerHeaders(),
        });

        actionMessage.value = response.data.data?.manual_instruction || response.data.message || 'Template berhasil diproses.';
        await fetchTemplates();
        await fetchPayments();
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = error.response?.data?.message || 'Template belum bisa dibeli.';
    } finally {
        processingTemplateId.value = null;
    }
};

const createEditJob = async (session, asset) => {
    actionMessage.value = '';
    actionError.value = '';

    if (!asset) {
        actionError.value = 'Pilih foto yang akan diedit.';
        return;
    }

    if (!selectedTemplateId.value) {
        actionError.value = 'Pilih template yang sudah kamu miliki.';
        return;
    }

    processingEditAssetId.value = asset.id;

    try {
        const response = await window.axios.post('/api/customer/edit-jobs', {
            cloud_session_id: session.id,
            source_asset_id: asset.id,
            cloud_template_id: selectedTemplateId.value,
            editor_payload: {
                mode: 'template_apply',
            },
        }, {
            headers: customerHeaders(),
        });

        actionMessage.value = response.data.data.status === 'completed'
            ? 'Edit selesai. Hasil edit sudah masuk ke daftar foto session.'
            : `Edit job dibuat: ${response.data.data.status}.`;
        await fetchEditJobs();
        await fetchSessions();
        activeView.value = 'editor';
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        actionError.value = error.response?.data?.message || 'Edit job belum bisa dibuat.';
    } finally {
        processingEditAssetId.value = null;
    }
};

const openGallery = (session) => {
    if (session.public_url) {
        window.open(session.public_url, '_blank', 'noopener,noreferrer');
    }
};

const logout = async () => {
    if (localStorage.getItem('dafydio_customer_token')) {
        await window.axios.post('/api/customer/auth/logout', {}, {
            headers: customerHeaders(),
        }).catch(() => {});
    }

    redirectToLogin();
};

onMounted(() => {
    fetchSessions();
    fetchTemplates();
    fetchPayments();
    fetchEditJobs();
    fetchPrintRequests();
});
</script>

<template>
    <main class="min-h-[100dvh] bg-[#F8FAFC] pb-24 text-[#191b23]">
        <header class="sticky top-0 z-40 border-b border-[#c3c6d7] bg-[#faf8ff]/95 px-4 py-3 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                <a class="leading-tight" href="/customer/dashboard">
                    <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="block text-lg font-black text-[#004ac6]">Dafydio</span></span>
                    <span class="block text-[11px] font-semibold uppercase tracking-wide text-[#737686]">Photobooth</span>
                </a>
                <button class="min-h-11 rounded-xl border border-[#c3c6d7] bg-white px-4 text-xs font-black text-[#004ac6]" type="button" @click="logout">
                    Logout
                </button>
            </div>
        </header>

        <section class="mx-auto max-w-6xl px-4 py-5">
            <div class="mb-5 rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">Customer Portal</p>
                <h1 class="mt-2 text-2xl font-black leading-tight">Halo, {{ customerName }}</h1>
                <p class="mt-2 text-sm leading-6 text-[#434655]">Lihat session, download foto, share gallery, atau request print.</p>
                <button v-if="!askingName" class="mt-3 inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] bg-white px-3 text-xs font-black text-[#004ac6]" type="button" @click="askingName = true">
                    Edit Nama
                </button>
                <form v-if="askingName" class="mt-4 grid gap-3 rounded-xl bg-[#f3f3fe] p-3 md:grid-cols-[1fr_auto_auto] md:items-end" @submit.prevent="saveName">
                    <label class="block text-xs font-bold text-[#434655]">
                        Nama kamu
                        <input v-model="profileName" class="mt-1 min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-white px-3 text-sm outline-none focus:border-[#004ac6]" maxlength="255" placeholder="Tulis nama kamu" type="text">
                    </label>
                    <button class="min-h-12 rounded-lg bg-[#004ac6] px-5 text-sm font-black text-white disabled:opacity-60" type="submit" :disabled="profileSaving">
                        {{ profileSaving ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <button v-if="customer?.name" class="min-h-12 rounded-lg border border-[#c3c6d7] bg-white px-5 text-sm font-black text-[#434655]" type="button" @click="askingName = false">
                        Batal
                    </button>
                </form>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-[#f3f3fe] p-3">
                        <p class="text-lg font-black text-[#004ac6]">{{ sessions.length }}</p>
                        <p class="text-xs font-bold text-[#737686]">Session</p>
                    </div>
                    <div class="rounded-xl bg-[#f3f3fe] p-3">
                        <p class="text-lg font-black text-[#004ac6]">{{ uploadedAssets }}</p>
                        <p class="text-xs font-bold text-[#737686]">Ready</p>
                    </div>
                    <div class="rounded-xl bg-[#f3f3fe] p-3">
                        <p class="text-lg font-black text-[#004ac6]">{{ ownedTemplates.length }}</p>
                        <p class="text-xs font-bold text-[#737686]">Template</p>
                    </div>
                </div>
            </div>

            <p v-if="errorMessage" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">{{ errorMessage }}</p>
            <p v-if="actionMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900">{{ actionMessage }}</p>
            <p v-if="actionError" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700">{{ actionError }}</p>

            <div class="mb-5 grid grid-cols-4 gap-2 rounded-xl border border-[#c3c6d7] bg-white p-2 shadow-sm">
                <button class="min-h-11 rounded-lg text-sm font-black" :class="activeView === 'sessions' ? 'bg-[#004ac6] text-white' : 'text-[#004ac6]'" type="button" @click="activeView = 'sessions'">
                    Sessions
                </button>
                <button class="min-h-11 rounded-lg text-sm font-black" :class="activeView === 'prints' ? 'bg-[#004ac6] text-white' : 'text-[#004ac6]'" type="button" @click="activeView = 'prints'">
                    Prints
                </button>
                <button class="min-h-11 rounded-lg text-sm font-black" :class="activeView === 'marketplace' ? 'bg-[#004ac6] text-white' : 'text-[#004ac6]'" type="button" @click="activeView = 'marketplace'">
                    Marketplace
                </button>
                <button class="min-h-11 rounded-lg text-sm font-black" :class="activeView === 'editor' ? 'bg-[#004ac6] text-white' : 'text-[#004ac6]'" type="button" @click="activeView = 'editor'">
                    Editor
                </button>
            </div>

            <section v-if="activeView === 'sessions' && latestSession" class="mb-6 overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                <button class="block w-full bg-[#f3f3fe]" type="button" @click="openGallery(latestSession)">
                    <img v-if="firstAsset(latestSession)?.file_url" class="max-h-[52vh] min-h-80 w-full object-contain" :src="firstAsset(latestSession).file_url" :alt="latestSession.title || 'Latest session'">
                    <div v-else class="flex min-h-80 items-center justify-center text-sm font-semibold text-[#737686]">Foto belum tersedia</div>
                </button>
                <div class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-lg font-black">{{ latestSession.title || 'Latest Session' }}</p>
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide text-[#737686]">{{ formatDate(latestSession.started_at || latestSession.created_at) }} - {{ latestSession.session_code }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-[#dbe1ff] px-3 py-1 text-xs font-black text-[#003ea8]">Terbaru</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button class="min-h-12 rounded-xl bg-[#004ac6] px-4 text-sm font-black text-white" type="button" @click="openGallery(latestSession)">Gallery</button>
                        <button class="min-h-12 rounded-xl border border-[#004ac6] px-4 text-sm font-black text-[#004ac6]" type="button" @click="downloadAsset(latestSession)">Download</button>
                    </div>
                </div>
            </section>

            <section v-if="activeView === 'sessions'">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xl font-black">Riwayat Session</h2>
                    <button class="min-h-10 rounded-xl border border-[#c3c6d7] bg-white px-3 text-xs font-black text-[#004ac6]" type="button" @click="fetchSessions">Refresh</button>
                </div>

                <div v-if="loading" class="rounded-xl border border-[#c3c6d7] bg-white p-5 text-sm text-[#434655]">
                    Loading sessions...
                </div>

                <div v-else-if="visibleSessions.length > 0" class="space-y-4">
                    <article v-for="session in visibleSessions" :key="session.id" class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                        <div class="flex gap-3 p-3">
                            <button class="h-28 w-24 shrink-0 overflow-hidden rounded-xl bg-[#f3f3fe]" type="button" @click="selectedSession = session">
                                <img v-if="firstAsset(session)?.file_url" class="h-full w-full object-cover" :src="firstAsset(session).file_url" :alt="session.title || 'Session photo'">
                                <span v-else class="flex h-full items-center justify-center text-xs font-bold text-[#737686]">No Photo</span>
                            </button>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-black">{{ session.title || 'Untitled Session' }}</h3>
                                        <p class="mt-1 truncate text-xs font-bold uppercase tracking-wide text-[#737686]">{{ session.session_code }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-[#f3f3fe] px-2 py-1 text-[11px] font-black text-[#004ac6]">{{ uploadedSessionAssets(session).length }}</span>
                                </div>
                                <p class="mt-2 text-xs font-semibold text-[#434655]">{{ formatDate(session.started_at || session.created_at) }} - Frame {{ framedCount(session) }} - Original {{ originalCount(session) }}</p>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <button class="min-h-10 rounded-lg bg-[#004ac6] text-xs font-black text-white" type="button" @click="openGallery(session)">Gallery</button>
                                    <button class="min-h-10 rounded-lg border border-[#c3c6d7] text-xs font-black text-[#004ac6]" type="button" @click="selectedSession = session">Detail</button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-xl border border-[#c3c6d7] bg-white p-6 text-center shadow-sm">
                    <img class="mx-auto mb-4 size-16 rounded-full object-cover shadow-sm" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                    <h3 class="text-xl font-black">Belum ada session</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#434655]">Session akan muncul setelah station sync ke cloud untuk WhatsApp kamu.</p>
                </div>
            </section>

            <section v-if="activeView === 'prints'">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black">Print Request</h2>
                        <p class="mt-1 text-xs font-semibold text-[#737686]">{{ activePrintRequests.length }} request aktif dari {{ printRequests.length }} total.</p>
                    </div>
                    <button class="min-h-10 rounded-xl border border-[#c3c6d7] bg-white px-3 text-xs font-black text-[#004ac6]" type="button" @click="fetchPrintRequests">Refresh</button>
                </div>

                <div v-if="printRequestsLoading" class="rounded-xl border border-[#c3c6d7] bg-white p-5 text-sm text-[#434655]">
                    Loading print requests...
                </div>

                <div v-else-if="printRequests.length > 0" class="space-y-3">
                    <article v-for="printRequest in printRequests" :key="printRequest.id" class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-black">{{ printRequest.session_title || 'Photobooth Session' }}</p>
                                <p class="mt-1 text-xs font-bold uppercase tracking-wide text-[#737686]">{{ printRequest.session_code || 'Session' }} - {{ printRequest.asset_type || 'asset' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-black uppercase" :class="printRequest.status === 'printed' ? 'bg-green-100 text-[#10B981]' : printRequest.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-[#dbe1ff] text-[#003ea8]'">
                                {{ printRequest.status }}
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs font-bold text-[#434655] md:grid-cols-4">
                            <div class="rounded-xl bg-[#f3f3fe] p-3">
                                <p class="text-[#737686]">Copies</p>
                                <p class="mt-1 text-sm font-black text-[#191b23]">{{ printRequest.quantity }}</p>
                            </div>
                            <div class="rounded-xl bg-[#f3f3fe] p-3">
                                <p class="text-[#737686]">Payment</p>
                                <p class="mt-1 text-sm font-black text-[#191b23]">{{ printRequest.payment_status }}</p>
                            </div>
                            <div class="rounded-xl bg-[#f3f3fe] p-3">
                                <p class="text-[#737686]">Station</p>
                                <p class="mt-1 truncate text-sm font-black text-[#191b23]">{{ printRequest.station_name || printRequest.station_code || '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-[#f3f3fe] p-3">
                                <p class="text-[#737686]">Dibuat</p>
                                <p class="mt-1 text-sm font-black text-[#191b23]">{{ formatDate(printRequest.created_at) }}</p>
                            </div>
                        </div>
                        <p v-if="printRequest.last_error" class="mt-3 rounded-xl bg-red-50 p-3 text-xs font-semibold text-red-700">{{ printRequest.last_error }}</p>
                        <p v-else-if="printRequest.status === 'pending_operator'" class="mt-3 rounded-xl bg-[#eef4ff] p-3 text-xs font-semibold text-[#003ea8]">Menunggu station mengambil request cetak.</p>
                        <p v-else-if="printRequest.status === 'claimed'" class="mt-3 rounded-xl bg-[#eef4ff] p-3 text-xs font-semibold text-[#003ea8]">Station sudah mengambil request ini.</p>
                        <p v-else-if="printRequest.status === 'printing'" class="mt-3 rounded-xl bg-[#eef4ff] p-3 text-xs font-semibold text-[#003ea8]">Sedang dicetak oleh station.</p>
                        <p v-else-if="printRequest.status === 'printed'" class="mt-3 rounded-xl bg-green-50 p-3 text-xs font-semibold text-green-700">Foto sudah selesai dicetak.</p>
                    </article>
                </div>

                <div v-else class="rounded-xl border border-[#c3c6d7] bg-white p-6 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#e1e2ed] text-2xl font-black text-[#737686]">PR</div>
                    <h3 class="text-xl font-black">Belum ada print request</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#434655]">Buka detail session, pilih foto, lalu tekan Print untuk membuat request cetak.</p>
                </div>
            </section>

            <section v-if="activeView === 'marketplace'">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black">Template Marketplace</h2>
                        <p class="mt-1 text-xs font-semibold text-[#737686]">{{ ownedTemplates.length }} template sudah bisa dipakai</p>
                    </div>
                    <button class="min-h-10 rounded-xl border border-[#c3c6d7] bg-white px-3 text-xs font-black text-[#004ac6]" type="button" @click="fetchTemplates">Refresh</button>
                </div>

                <div v-if="paymentsLoading" class="mb-4 rounded-xl border border-[#c3c6d7] bg-white p-4 text-sm text-[#434655]">
                    Memuat status pembayaran...
                </div>

                <div v-if="pendingPayments.length > 0" class="mb-4 space-y-3">
                    <article v-for="payment in pendingPayments" :key="payment.id" class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-amber-950">{{ payment.template_name || 'Template marketplace' }}</p>
                                <p class="mt-1 text-xs font-bold uppercase tracking-wide text-amber-800">{{ paymentMoney(payment) }} - Menunggu approval admin</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-2 py-1 text-[10px] font-black uppercase text-amber-800">Pending</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-amber-900">{{ payment.manual_instruction || 'Kirim bukti pembayaran ke admin Dafydio Photobooth.' }}</p>
                    </article>
                </div>

                <div v-if="templatesLoading" class="rounded-xl border border-[#c3c6d7] bg-white p-5 text-sm text-[#434655]">
                    Loading templates...
                </div>

                <div v-else-if="templates.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <article v-for="template in templates" :key="template.id" class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                        <div class="flex aspect-[4/3] items-center justify-center bg-[#f3f3fe]">
                            <img v-if="template.preview_url" class="h-full w-full object-cover" :src="template.preview_url" :alt="template.name">
                            <div v-else class="px-4 text-center">
                                <img class="size-14 rounded-xl object-cover shadow-sm" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                                <p class="mt-1 text-sm font-bold text-[#737686]">Template Preview</p>
                            </div>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-black">{{ template.name }}</h3>
                                    <p class="mt-1 line-clamp-2 text-sm leading-6 text-[#434655]">{{ template.description || 'Template Dafydio Photobooth.' }}</p>
                                    <p v-if="template.category || template.paper_size" class="mt-2 truncate text-xs font-bold uppercase tracking-wide text-[#737686]">{{ template.category || 'Template' }} - {{ template.paper_size || 'Paper' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-[#f3f3fe] px-2 py-1 text-[10px] font-black uppercase text-[#004ac6]">{{ template.access_level }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-black text-[#004ac6]">{{ templateStatusLabel(template) }}</span>
                                <span v-if="template.is_owned || template.is_premium_included" class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-black uppercase text-[#10B981]">Ready</span>
                            </div>
                            <button
                                class="min-h-12 w-full rounded-xl px-4 text-sm font-black disabled:opacity-60"
                                :class="template.is_owned || template.is_premium_included ? 'border border-[#004ac6] text-[#004ac6]' : 'bg-[#004ac6] text-white'"
                                type="button"
                                :disabled="processingTemplateId === template.id"
                                @click="purchaseTemplate(template)"
                            >
                                {{ processingTemplateId === template.id ? 'Processing...' : (template.is_owned || template.is_premium_included ? 'Pakai Template' : 'Beli Template') }}
                            </button>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-xl border border-[#c3c6d7] bg-white p-6 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#e1e2ed] text-2xl font-black text-[#737686]">TP</div>
                    <h3 class="text-xl font-black">Belum ada template</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#434655]">Template akan muncul setelah admin mengaktifkan marketplace template.</p>
                </div>
            </section>

            <section v-if="activeView === 'editor'">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black">Cloud Editor</h2>
                        <p class="mt-1 text-xs font-semibold text-[#737686]">Status edit template online.</p>
                    </div>
                    <button class="min-h-10 rounded-xl border border-[#c3c6d7] bg-white px-3 text-xs font-black text-[#004ac6]" type="button" @click="fetchEditJobs">Refresh</button>
                </div>

                <div v-if="editJobsLoading" class="rounded-xl border border-[#c3c6d7] bg-white p-5 text-sm text-[#434655]">
                    Loading edit jobs...
                </div>

                <div v-else-if="editJobs.length > 0" class="space-y-3">
                    <article v-for="job in editJobs" :key="job.id" class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-black">{{ job.template_name || 'Template Edit' }}</p>
                                <p class="mt-1 text-xs font-bold uppercase tracking-wide text-[#737686]">{{ job.session_title || 'Session' }} - {{ job.source_asset_type || 'asset' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-[#dbe1ff] px-3 py-1 text-xs font-black uppercase text-[#003ea8]">{{ job.status }}</span>
                        </div>
                        <p v-if="job.error_message" class="mt-3 rounded-xl bg-red-50 p-3 text-xs font-semibold text-red-700">{{ job.error_message }}</p>
                        <a
                            v-if="job.result_asset?.file_url"
                            class="mt-3 inline-flex min-h-10 items-center rounded-lg bg-[#004ac6] px-3 text-xs font-black text-white"
                            :href="job.result_asset.file_url"
                            download
                        >
                            Download Hasil Edit
                        </a>
                        <p class="mt-3 text-xs font-semibold text-[#737686]">{{ formatDate(job.created_at) }}</p>
                    </article>
                </div>

                <div v-else class="rounded-xl border border-[#c3c6d7] bg-white p-6 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-[#e1e2ed] text-2xl font-black text-[#737686]">ED</div>
                    <h3 class="text-xl font-black">Belum ada edit job</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#434655]">Buka detail session, pilih template, lalu buat edit job.</p>
                </div>
            </section>
        </section>

        <div v-if="selectedSession" class="fixed inset-0 z-50 bg-black/55 px-4 py-6 backdrop-blur-sm">
            <section class="mx-auto flex max-h-full max-w-xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
                <header class="flex items-start justify-between gap-3 border-b border-[#c3c6d7] p-4">
                    <div class="min-w-0">
                        <p class="truncate text-lg font-black">{{ selectedSession.title || 'Session Detail' }}</p>
                        <p class="mt-1 text-xs font-bold uppercase tracking-wide text-[#737686]">{{ selectedSession.session_code }}</p>
                    </div>
                    <button class="min-h-10 rounded-xl bg-[#f3f3fe] px-3 text-xs font-black text-[#004ac6]" type="button" @click="selectedSession = null">Tutup</button>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <div class="mb-4 grid grid-cols-2 gap-3">
                        <button class="min-h-12 rounded-xl bg-[#004ac6] px-4 text-sm font-black text-white" type="button" @click="openGallery(selectedSession)">Buka Gallery</button>
                        <a class="flex min-h-12 items-center justify-center rounded-xl border border-[#25d366] px-4 text-sm font-black text-[#128c3a]" :href="shareGalleryUrl(selectedSession)" target="_blank" rel="noopener noreferrer">Share WA</a>
                        <a v-if="selectedSession.download_all_url" class="flex min-h-12 items-center justify-center rounded-xl border border-[#004ac6] px-4 text-sm font-black text-[#004ac6]" :href="selectedSession.download_all_url">Download Semua</a>
                        <button class="min-h-12 rounded-xl border border-[#c3c6d7] px-4 text-sm font-black text-[#004ac6]" type="button" @click="requestPrint(selectedSession)">Request Print</button>
                    </div>

                    <div class="mb-4 rounded-xl border border-[#c3c6d7] bg-[#f3f3fe] p-3">
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#737686]">Template untuk Cloud Editor</span>
                            <select v-model="selectedTemplateId" class="mt-2 min-h-11 w-full rounded-xl border border-[#c3c6d7] bg-white px-3 text-sm font-semibold">
                                <option value="">Pilih template owned/premium</option>
                                <option v-for="template in readyTemplates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                        </label>
                        <p v-if="readyTemplates.length === 0" class="mt-2 text-xs font-semibold text-[#737686]">Beli template marketplace dulu untuk membuat edit job.</p>
                    </div>

                    <div class="space-y-3">
                        <article v-for="asset in uploadedSessionAssets(selectedSession)" :key="asset.id" class="flex gap-3 rounded-xl border border-[#c3c6d7] p-3">
                            <img v-if="asset.file_url" class="h-20 w-16 rounded-lg bg-[#f3f3fe] object-cover" :src="asset.file_url" :alt="asset.station_asset_id">
                            <div v-else class="h-20 w-16 rounded-lg bg-[#f3f3fe]"></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black capitalize">{{ asset.type }}</p>
                                <p class="mt-1 text-xs font-semibold text-[#737686]">{{ asset.width && asset.height ? `${asset.width} x ${asset.height}` : 'Resolusi tersimpan' }} - {{ formatBytes(asset.size_bytes) }}</p>
                                <div class="mt-2 flex gap-2">
                                    <button class="min-h-9 rounded-lg bg-[#004ac6] px-3 text-xs font-black text-white disabled:opacity-60" type="button" :disabled="processingAssetId === asset.id" @click="downloadAsset(selectedSession, asset)">
                                        {{ processingAssetId === asset.id ? '...' : 'Download' }}
                                    </button>
                                    <button class="min-h-9 rounded-lg border border-[#c3c6d7] px-3 text-xs font-black text-[#004ac6] disabled:opacity-60" type="button" :disabled="processingAssetId === asset.id" @click="requestPrint(selectedSession, asset)">
                                        Print
                                    </button>
                                    <button class="min-h-9 rounded-lg border border-[#004ac6] px-3 text-xs font-black text-[#004ac6] disabled:opacity-60" type="button" :disabled="processingEditAssetId === asset.id || readyTemplates.length === 0" @click="createEditJob(selectedSession, asset)">
                                        {{ processingEditAssetId === asset.id ? '...' : 'Edit' }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
