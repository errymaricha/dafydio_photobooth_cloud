<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: 'admin@dafydio.local',
    password: '',
    remember: true,
});

const submit = () => {
    form.post('/login/admin');
};
</script>

<template>
    <main class="flex min-h-[100dvh] items-center justify-center bg-[#faf8ff] px-4 py-8 text-[#191b23]">
        <section class="w-full max-w-md rounded-xl border border-[#c3c6d7]/40 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-7 text-center">
                <img class="mx-auto mb-4 size-16 rounded-xl object-cover shadow-lg" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#004ac6]">Tenant Admin</p>
                <h1 class="mt-2 text-3xl font-bold leading-tight text-[#004ac6]">Dafydio Admin</h1>
                <p class="mt-2 text-sm leading-6 text-[#434655]">Kelola station, session, template, print request, billing, dan sync status.</p>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        class="min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] px-4 text-base outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                        type="email"
                        autocomplete="email"
                    >
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold tracking-wide text-[#191b23]" for="password">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        class="min-h-12 w-full rounded-lg border border-[#c3c6d7] bg-[#f3f3fe] px-4 text-base outline-none transition focus:border-[#004ac6] focus:ring-2 focus:ring-[#004ac6]/20"
                        type="password"
                        autocomplete="current-password"
                    >
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-3 text-sm text-zinc-700">
                    <input v-model="form.remember" class="size-4" type="checkbox">
                    Ingat login admin
                </label>

                <button
                    class="min-h-12 w-full rounded-lg bg-[#004ac6] px-5 text-xl font-semibold leading-7 text-white shadow-md transition active:scale-[0.98] disabled:opacity-60"
                    type="submit"
                    :disabled="form.processing"
                >
                    Masuk
                </button>
            </form>

            <p class="mt-5 text-center text-xs leading-5 text-[#434655]/70">Default seed: admin@dafydio.local / password.</p>
        </section>
    </main>
</template>
