<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const sessionCode = ref('');

const sanitizedSessionCode = computed(() => sessionCode.value.trim().toUpperCase());
const publicGalleryUrl = computed(() => (sanitizedSessionCode.value ? `/${sanitizedSessionCode.value}` : '#'));

const accessCards = [
    {
        label: 'Customer Portal',
        title: 'Gallery, download, edit, dan print request',
        body: 'Masuk memakai WhatsApp dan password dari station untuk melihat arsip foto.',
    },
    {
        label: 'Admin Console',
        title: 'Operasional tenant dan station',
        body: 'Kelola customer, session archive, station sync, billing, template, dan logs.',
    },
    {
        label: 'Public Gallery',
        title: 'URL pendek siap share WhatsApp',
        body: 'Gunakan kode session seperti SES-LM7CMO5G agar link lebih rapi saat dibagikan.',
    },
    {
        label: 'Station API',
        title: 'Sync archive dan print request polling',
        body: 'Station tetap menangani capture dan cetak fisik, cloud menjadi koordinator.',
    },
];

const readinessItems = [
    ['Database', 'MySQL + database queue'],
    ['Auth', 'Admin session, customer Sanctum, station token'],
    ['Print Flow', 'Cloud request dipolling oleh station'],
    ['Storage', 'Local public disk, struktur S3/R2 disiapkan'],
];
</script>

<template>
    <Head>
        <title>Dafydio Photobooth Cloud</title>
        <meta name="description" content="Portal cloud Dafydio untuk gallery customer, arsip session, print request, dan sinkronisasi station photobooth." />
    </Head>

    <main class="min-h-screen bg-[#F8FAFC] text-[#191b23]">
        <section class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-5 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between gap-4">
                <Link class="flex min-w-0 items-center gap-3" href="/">
                    <img class="size-12 rounded-xl border border-[#c3c6d7] object-cover shadow-sm" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#004ac6]">Dafydio Cloud</p>
                        <p class="truncate text-lg font-bold sm:text-xl">Photobooth Portal</p>
                    </div>
                </Link>
                <div class="flex shrink-0 items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-semibold text-emerald-900">Online</span>
                </div>
            </header>

            <section class="grid flex-1 items-center gap-8 py-8 lg:grid-cols-[1.05fr_0.95fr] lg:py-12">
                <div>
                    <div class="inline-flex items-center rounded-full border border-[#c3c6d7] bg-white px-3 py-1.5 text-xs font-semibold text-[#434655] shadow-sm">
                        Customer gallery, admin console, station sync
                    </div>
                    <h1 class="mt-5 max-w-3xl text-4xl font-extrabold leading-tight tracking-normal text-[#191b23] sm:text-5xl">
                        Portal utama Dafydio Photobooth Cloud.
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-[#434655]">
                        Buka gallery customer dari kode session, masuk ke portal WhatsApp, atau kelola operasional tenant dan station dari satu halaman.
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <Link
                            class="inline-flex min-h-12 items-center justify-center rounded-lg bg-[#004ac6] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#2563eb] active:scale-[0.99]"
                            href="/login?mode=customer"
                        >
                            Masuk Customer
                        </Link>
                        <Link
                            class="inline-flex min-h-12 items-center justify-center rounded-lg border border-[#c3c6d7] bg-white px-5 text-sm font-bold text-[#191b23] shadow-sm transition hover:bg-[#f3f3fe] active:scale-[0.99]"
                            href="/login?mode=admin"
                        >
                            Masuk Admin
                        </Link>
                    </div>

                    <form class="mt-6 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm" @submit.prevent>
                        <label class="text-xs font-bold uppercase tracking-wide text-[#434655]" for="session-code">Buka gallery public</label>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                            <input
                                id="session-code"
                                v-model="sessionCode"
                                class="min-h-12 flex-1 rounded-lg border-[#c3c6d7] bg-[#f3f3fe] text-sm font-semibold uppercase text-[#191b23] placeholder:normal-case placeholder:text-[#737686] focus:border-[#004ac6] focus:ring-[#004ac6]"
                                inputmode="text"
                                placeholder="Contoh: SES-LM7CMO5G"
                                type="text"
                            >
                            <Link
                                :class="[
                                    'inline-flex min-h-12 items-center justify-center rounded-lg px-5 text-sm font-bold transition',
                                    sanitizedSessionCode
                                        ? 'bg-[#191b23] text-white hover:bg-[#2e3039]'
                                        : 'pointer-events-none bg-[#e1e2ed] text-[#737686]',
                                ]"
                                :href="publicGalleryUrl"
                            >
                                Buka Gallery
                            </Link>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-[#737686]">
                            Pakai URL pendek seperti <span class="font-semibold text-[#191b23]">/SES-LM7CMO5G</span> untuk share WhatsApp yang lebih rapi.
                        </p>
                    </form>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <article
                        v-for="card in accessCards"
                        :key="card.label"
                        class="rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm"
                    >
                        <p class="text-xs font-bold uppercase tracking-wide text-[#004ac6]">{{ card.label }}</p>
                        <h2 class="mt-3 text-lg font-bold leading-snug">{{ card.title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-[#434655]">{{ card.body }}</p>
                    </article>
                </div>
            </section>

            <section class="mb-6 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Status kesiapan cloud</h2>
                        <p class="mt-1 text-sm leading-6 text-[#434655]">
                            Cloud menyimpan arsip, login customer, dan request cetak. Station tetap menjadi eksekutor capture dan printer fisik.
                        </p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:min-w-[520px]">
                        <div
                            v-for="[label, value] in readinessItems"
                            :key="label"
                            class="rounded-lg bg-[#f3f3fe] px-3 py-3"
                        >
                            <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">{{ label }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#191b23]">{{ value }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </main>
</template>
