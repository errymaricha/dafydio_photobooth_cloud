<script setup>
import { ref } from 'vue';

const props = defineProps({
    tenantSlug: {
        type: String,
        default: 'dafydio-demo',
    },
});

const whatsappNumber = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const login = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.post('/api/customer/auth/login', {
            tenant_slug: props.tenantSlug,
            whatsapp_number: whatsappNumber.value,
            password: password.value,
        });

        localStorage.setItem('dafydio_customer_token', response.data.data.token);
        localStorage.setItem('dafydio_customer', JSON.stringify(response.data.data.customer));
        window.location.href = '/customer/dashboard';
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Login gagal. Periksa WhatsApp dan password.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <main class="flex min-h-[100dvh] flex-col bg-[#faf8ff] text-[#191b23]">
        <section class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="flex w-full max-w-sm flex-col items-center">
                <header class="mb-9 text-center">
                    <img class="mx-auto mb-4 size-16 rounded-xl object-cover shadow-lg" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                    <h1 class="font-sans text-[32px] font-bold leading-tight tracking-normal text-[#004ac6]">Dafydio</h1>
                    <p class="mt-2 text-base leading-6 text-[#434655]">Access your digital gallery</p>
                </header>

                <section class="w-full rounded-xl border border-[#c3c6d7]/30 bg-white p-8 shadow-sm">
                    <form class="space-y-6" @submit.prevent="login">
                        <div>
                            <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="whatsapp">WhatsApp Number</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <span class="text-xl text-[#25D366]" aria-hidden="true">☎</span>
                                </div>
                                <input
                                    id="whatsapp"
                                    v-model="whatsappNumber"
                                    class="block min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] py-3 pl-12 pr-4 text-sm leading-6 outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                                    name="whatsapp"
                                    placeholder="+62 812 0000 0000"
                                    required
                                    type="tel"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="password">Access Password</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <span class="text-xl text-[#434655]" aria-hidden="true">⌕</span>
                                </div>
                                <input
                                    id="password"
                                    v-model="password"
                                    class="block min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] py-3 pl-12 pr-12 text-sm leading-6 outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                                    name="password"
                                    placeholder="••••••••"
                                    required
                                    :type="showPassword ? 'text' : 'password'"
                                >
                                <button
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-[#434655] hover:text-[#004ac6]"
                                    type="button"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword"
                                >
                                    <span class="text-sm font-semibold">{{ showPassword ? 'Hide' : 'Show' }}</span>
                                </button>
                            </div>
                            <p class="mt-3 flex items-start gap-2 text-[11px] font-medium leading-relaxed tracking-wide text-[#434655]/80">
                                <span class="mt-0.5 text-sm" aria-hidden="true">i</span>
                                Password is sent or created from the photobooth station.
                            </p>
                        </div>

                        <p v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm leading-6 text-red-700">
                            {{ errorMessage }}
                        </p>

                        <div class="pt-2">
                            <button
                                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#004ac6] text-xl font-semibold leading-7 text-white shadow-md transition active:scale-[0.98] disabled:opacity-60"
                                type="submit"
                                :disabled="loading"
                            >
                                {{ loading ? 'Logging in...' : 'Login' }}
                                <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </form>
                </section>

                <div class="mt-8 flex flex-col items-center gap-4">
                    <button class="flex min-h-11 items-center gap-2 text-xs font-semibold tracking-wide text-[#434655] transition hover:text-[#004ac6]" type="button">
                        <span aria-hidden="true">?</span>
                        Need help accessing photos?
                    </button>
                </div>
            </div>
        </section>

        <footer class="px-4 py-8 text-center">
            <p class="text-[11px] font-medium leading-5 tracking-wide text-[#434655]/60">
                © 2026 Dafydio Cloud. All rights reserved.
            </p>
        </footer>
    </main>
</template>
