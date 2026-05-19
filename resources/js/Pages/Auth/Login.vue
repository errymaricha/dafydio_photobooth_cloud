<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tenantSlug: {
        type: String,
        default: 'dafydio-demo',
    },
    defaultMode: {
        type: String,
        default: 'customer',
    },
});

const mode = ref(props.defaultMode);
const showCustomerPassword = ref(false);
const showAdminPassword = ref(false);
const customerLoading = ref(false);
const customerError = ref('');
const customer = ref({
    whatsapp_number: '',
    password: '',
});

const adminForm = useForm({
    email: 'admin@dafydio.local',
    password: '',
    remember: true,
});

const loginCustomer = async () => {
    customerLoading.value = true;
    customerError.value = '';

    try {
        const response = await window.axios.post('/api/customer/auth/login', {
            tenant_slug: props.tenantSlug,
            whatsapp_number: customer.value.whatsapp_number,
            password: customer.value.password,
        });

        localStorage.setItem('dafydio_customer_token', response.data.data.token);
        localStorage.setItem('dafydio_customer', JSON.stringify(response.data.data.customer));
        window.location.href = '/customer/dashboard';
    } catch (error) {
        customerError.value = error.response?.data?.message || 'Login gagal. Periksa WhatsApp dan password.';
    } finally {
        customerLoading.value = false;
    }
};

const loginAdmin = () => {
    adminForm.post('/login/admin');
};
</script>

<template>
    <main class="flex min-h-[100dvh] flex-col bg-[#faf8ff] text-[#191b23]">
        <section class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="flex w-full max-w-sm flex-col items-center">
                <header class="mb-8 text-center">
                    <img class="mx-auto mb-4 size-16 rounded-xl object-cover shadow-lg" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                    <h1 class="text-[32px] font-bold leading-tight tracking-normal text-[#004ac6]">Dafydio</h1>
                    <p class="mt-2 text-base leading-6 text-[#434655]">
                        {{ mode === 'customer' ? 'Access your digital gallery' : 'Manage your photobooth cloud' }}
                    </p>
                </header>

                <section class="w-full rounded-xl border border-[#c3c6d7]/30 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6 grid grid-cols-2 gap-2 rounded-lg bg-[#f3f3fe] p-1">
                        <button
                            class="min-h-11 rounded-lg text-sm font-semibold transition"
                            :class="mode === 'customer' ? 'bg-[#004ac6] text-white shadow-sm' : 'text-[#434655]'"
                            type="button"
                            @click="mode = 'customer'"
                        >
                            Customer
                        </button>
                        <button
                            class="min-h-11 rounded-lg text-sm font-semibold transition"
                            :class="mode === 'admin' ? 'bg-[#004ac6] text-white shadow-sm' : 'text-[#434655]'"
                            type="button"
                            @click="mode = 'admin'"
                        >
                            Admin
                        </button>
                    </div>

                    <form v-if="mode === 'customer'" class="space-y-6" @submit.prevent="loginCustomer">
                        <div>
                            <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="customer-whatsapp">WhatsApp Number</label>
                            <input
                                id="customer-whatsapp"
                                v-model="customer.whatsapp_number"
                                class="block min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] px-4 text-sm leading-6 outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                                placeholder="+62 812 0000 0000"
                                required
                                type="tel"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="customer-password">Access Password</label>
                            <div class="relative">
                                <input
                                    id="customer-password"
                                    v-model="customer.password"
                                    class="block min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] px-4 pr-16 text-sm leading-6 outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                                    placeholder="Password dari station"
                                    required
                                    :type="showCustomerPassword ? 'text' : 'password'"
                                >
                                <button class="absolute inset-y-0 right-0 px-4 text-sm font-semibold text-[#434655]" type="button" @click="showCustomerPassword = !showCustomerPassword">
                                    {{ showCustomerPassword ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <p class="mt-3 text-[11px] font-medium leading-relaxed tracking-wide text-[#434655]/80">
                                Password dikirim atau dibuat dari photobooth station.
                            </p>
                        </div>

                        <p v-if="customerError" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm leading-6 text-red-700">
                            {{ customerError }}
                        </p>

                        <button
                            class="min-h-12 w-full rounded-lg bg-[#004ac6] px-5 text-xl font-semibold leading-7 text-white shadow-md transition active:scale-[0.98] disabled:opacity-60"
                            type="submit"
                            :disabled="customerLoading"
                        >
                            {{ customerLoading ? 'Logging in...' : 'Login Customer' }}
                        </button>
                    </form>

                    <form v-else class="space-y-5" @submit.prevent="loginAdmin">
                        <div>
                            <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="admin-email">Email Admin</label>
                            <input
                                id="admin-email"
                                v-model="adminForm.email"
                                class="min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] px-4 text-base outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                                type="email"
                                autocomplete="email"
                                required
                            >
                            <p v-if="adminForm.errors.email" class="mt-2 text-sm text-red-600">{{ adminForm.errors.email }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="admin-password">Password Admin</label>
                            <div class="relative">
                                <input
                                    id="admin-password"
                                    v-model="adminForm.password"
                                    class="min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] px-4 pr-16 text-base outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                                    :type="showAdminPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                >
                                <button class="absolute inset-y-0 right-0 px-4 text-sm font-semibold text-[#434655]" type="button" @click="showAdminPassword = !showAdminPassword">
                                    {{ showAdminPassword ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <p v-if="adminForm.errors.password" class="mt-2 text-sm text-red-600">{{ adminForm.errors.password }}</p>
                        </div>

                        <label class="flex min-h-10 items-center gap-3 text-sm text-[#434655]">
                            <input v-model="adminForm.remember" class="size-4" type="checkbox">
                            Ingat login admin
                        </label>

                        <button
                            class="min-h-12 w-full rounded-lg bg-[#004ac6] px-5 text-xl font-semibold leading-7 text-white shadow-md transition active:scale-[0.98] disabled:opacity-60"
                            type="submit"
                            :disabled="adminForm.processing"
                        >
                            Login Admin
                        </button>
                    </form>
                </section>

                <p class="mt-6 text-center text-[11px] font-medium leading-5 tracking-wide text-[#434655]/60">
                    Customer dan admin memakai satu URL login. Fitur setelah masuk dibedakan oleh role dan guard.
                </p>
            </div>
        </section>
    </main>
</template>
