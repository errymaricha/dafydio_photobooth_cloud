<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { animate, stagger } from 'animejs';

const sessionCode = ref('');
const pageRoot = ref(null);
const runningAnimations = [];

const sanitizedSessionCode = computed(() => sessionCode.value.trim().toUpperCase());
const publicGalleryUrl = computed(() => (sanitizedSessionCode.value ? `/${sanitizedSessionCode.value}` : '#'));

const flowActors = [
    {
        label: 'Android',
        subtitle: 'Capture & event launcher',
        title: 'Android Device',
        body: [
            'Capture foto dari device Android.',
            'Mulai event dari aplikasi photobooth.',
            'Kirim session dan hasil capture ke station.',
        ],
    },
    {
        label: 'Station',
        subtitle: 'Local source of truth',
        title: 'Photobooth Station',
        body: [
            'Render lokal untuk preview dan hasil akhir.',
            'Menyimpan local database event.',
            'Mengelola printer queue dan cetak fisik.',
            'Polling print request dari cloud.',
        ],
        highlighted: true,
    },
    {
        label: 'Cloud',
        subtitle: 'Archive & sync coordinator',
        title: 'Cloud Portal',
        body: [
            'Gallery customer dan link pendek WhatsApp.',
            'Arsip foto dan asset session.',
            'Admin console, billing, subscription, dan sync log.',
        ],
    },
];

const accessCards = [
    ['Customer Portal', 'Cepat, mobile-first, mudah dibuka dari WhatsApp untuk gallery, download foto, dan request cetak ulang.'],
    ['Station Operation', 'Station tetap bisa bekerja lokal untuk render, database, printer queue, dan cetak fisik.'],
    ['Admin Console', 'Owner/admin bisa monitoring station, customer, billing, template, sync log, dan arsip dalam satu dashboard.'],
];

const featureCards = [
    ['Gallery Public Link', 'Customer bisa buka foto lewat URL pendek seperti /SES-XXXX.'],
    ['WhatsApp Login', 'Customer masuk memakai nomor WhatsApp dan password dari station.'],
    ['Download Foto', 'Original dan framed photo bisa diakses dari portal.'],
    ['Print Request', 'Customer bisa request cetak ulang, station akan polling request.'],
    ['Admin Dashboard', 'Tenant/admin bisa kelola station, customer, session, billing, dan sync log.'],
    ['Cloud Archive', 'Session dan asset tersimpan sebagai arsip cloud yang mudah dicari.'],
];

const readinessItems = [
    ['Database', 'MySQL + database queue'],
    ['Auth', 'Admin session, customer Sanctum, station token'],
    ['Print Flow', 'Cloud request dipolling oleh station'],
    ['Storage', 'Local public disk, struktur S3/R2 disiapkan'],
];

onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || !pageRoot.value) {
        return;
    }

    runningAnimations.push(
        animate(pageRoot.value.querySelectorAll('[data-anime="node"]'), {
            translateY: [-8, 8],
            opacity: [0.35, 0.85],
            duration: 2800,
            delay: stagger(280),
            alternate: true,
            loop: true,
            ease: 'inOutSine',
        }),
        animate(pageRoot.value.querySelector('[data-anime="scan"]'), {
            translateX: ['-110%', '110%'],
            duration: 5200,
            loop: true,
            ease: 'inOutSine',
        }),
        animate(pageRoot.value.querySelectorAll('[data-anime="card"]'), {
            translateY: [18, 0],
            opacity: [0, 1],
            duration: 720,
            delay: stagger(90),
            ease: 'outCubic',
        }),
        animate(pageRoot.value.querySelectorAll('[data-anime="flow-line"]'), {
            strokeDashoffset: [34, 0],
            duration: 2200,
            delay: stagger(180),
            loop: true,
            ease: 'linear',
        }),
        animate(pageRoot.value.querySelectorAll('[data-anime="flow-pulse"]'), {
            scale: [0.82, 1.12],
            opacity: [0.45, 0.95],
            duration: 1800,
            delay: stagger(220),
            alternate: true,
            loop: true,
            ease: 'inOutSine',
        }),
        animate(pageRoot.value.querySelectorAll('[data-anime="arrow"]'), {
            translateX: [0, 6],
            duration: 920,
            delay: stagger(160),
            alternate: true,
            loop: true,
            ease: 'inOutSine',
        }),
    );
});

onBeforeUnmount(() => {
    runningAnimations.forEach((animation) => animation.revert());
});
</script>

<template>
    <Head>
        <title>Dafydio Photobooth Cloud</title>
        <meta name="description" content="Portal cloud Dafydio untuk gallery customer, arsip session, print request, dan sinkronisasi station photobooth." />
    </Head>

    <main ref="pageRoot" class="relative min-h-screen overflow-hidden bg-[#F8FAFC] text-[#191b23]">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute inset-x-0 top-0 h-px bg-[#c3c6d7]"></div>
            <div data-anime="scan" class="absolute top-0 h-full w-1/2 -skew-x-12 bg-gradient-to-r from-transparent via-[#004ac6]/10 to-transparent"></div>
            <div class="absolute right-4 top-24 hidden h-72 w-72 rounded-xl border border-[#c3c6d7]/70 sm:block"></div>
            <div class="absolute right-12 top-32 hidden h-72 w-72 rounded-xl border border-[#004ac6]/10 sm:block"></div>
            <span data-anime="node" class="absolute right-20 top-28 hidden size-3 rounded-sm bg-[#004ac6] sm:block"></span>
            <span data-anime="node" class="absolute right-72 top-64 hidden size-2 rounded-sm bg-[#fea619] sm:block"></span>
            <span data-anime="node" class="absolute bottom-28 left-10 size-2 rounded-sm bg-[#8B5CF6]"></span>
            <span data-anime="node" class="absolute bottom-44 right-8 size-2 rounded-sm bg-[#10B981]"></span>
        </div>

        <section class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-5 sm:px-6 lg:px-8">
            <header data-anime="card" class="sticky top-0 z-30 -mx-4 flex items-center justify-between gap-4 border-b border-[#c3c6d7]/70 bg-[#F8FAFC]/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <Link class="flex min-w-0 items-center gap-3" href="/">
                    <img class="size-12 rounded-xl border border-[#c3c6d7] object-cover shadow-sm" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#004ac6]">Dafydio Cloud</p>
                        <p class="truncate text-lg font-bold sm:text-xl">Customer portal, archive, station sync</p>
                    </div>
                </Link>
                <div class="flex shrink-0 items-center gap-2">
                    <Link class="hidden min-h-9 items-center rounded-full border border-[#c3c6d7] bg-white px-3 text-xs font-extrabold text-[#004ac6] sm:inline-flex" href="/login?mode=admin">
                        Admin
                    </Link>
                    <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-900">Online</span>
                    </div>
                </div>
            </header>

            <section class="grid gap-5 py-8 lg:grid-cols-[1fr_360px] lg:py-10">
                <div data-anime="card" class="rounded-xl border border-[#c3c6d7]/80 bg-white/95 p-6 shadow-sm sm:p-8">
                    <div class="inline-flex items-center rounded-full border border-[#d8e5ff] bg-[#eef4ff] px-3 py-1.5 text-xs font-extrabold text-[#004ac6]">
                        Flow-first cloud portal
                    </div>
                    <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight tracking-normal text-[#191b23] sm:text-6xl">
                        Portal Cloud untuk Dafydio Photobooth
                    </h1>
                    <p class="mt-5 max-w-3xl text-base leading-8 text-[#434655]">
                        Akses gallery, download foto, request cetak ulang, dan kelola station photobooth dari satu portal cloud. Sistem ini menghubungkan Android Device, Photobooth Station, dan Cloud tanpa mengambil alih proses cetak lokal.
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 md:max-w-xl">
                        <Link class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#004ac6] px-5 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#2563eb] active:scale-[0.99]" href="/login?mode=customer">
                            Masuk Customer
                        </Link>
                        <Link class="inline-flex min-h-12 items-center justify-center rounded-xl border border-[#b8cdf8] bg-white px-5 text-sm font-extrabold text-[#004ac6] shadow-sm transition hover:bg-[#f3f3fe] active:scale-[0.99]" href="/login?mode=admin">
                            Masuk Admin
                        </Link>
                    </div>
                </div>

                <aside data-anime="card" class="rounded-xl border border-[#c3c6d7]/80 bg-white/95 p-5 shadow-sm">
                    <h2 class="text-lg font-extrabold">Buka Gallery Public</h2>
                    <p class="mt-2 text-sm leading-6 text-[#434655]">
                        Masukkan kode session untuk membuka gallery customer dari link pendek.
                    </p>
                    <form class="mt-4 space-y-3" @submit.prevent>
                        <label class="text-xs font-extrabold uppercase tracking-wide text-[#434655]" for="session-code">Kode Gallery</label>
                        <input
                            id="session-code"
                            v-model="sessionCode"
                            class="min-h-12 w-full rounded-xl border-[#c3c6d7] bg-white px-4 text-sm font-bold uppercase text-[#191b23] placeholder:normal-case placeholder:text-[#737686] focus:border-[#004ac6] focus:ring-[#004ac6]"
                            inputmode="text"
                            placeholder="Contoh: SES-LM7CMO5G"
                            type="text"
                        >
                        <Link
                            :class="[
                                'inline-flex min-h-12 w-full items-center justify-center rounded-xl px-5 text-sm font-extrabold transition',
                                sanitizedSessionCode
                                    ? 'bg-[#004ac6] text-white hover:bg-[#2563eb]'
                                    : 'pointer-events-none bg-[#e1e2ed] text-[#737686]',
                            ]"
                            :href="publicGalleryUrl"
                        >
                            Buka Gallery
                        </Link>
                    </form>
                    <p class="mt-3 text-xs leading-5 text-[#737686]">
                        Gunakan URL pendek untuk share WhatsApp yang lebih rapi.
                    </p>
                    <div class="mt-4 space-y-2 border-t border-[#c3c6d7]/70 pt-4">
                        <div class="flex items-center justify-between gap-3 text-xs font-bold text-[#434655]">
                            <span>Gallery Link</span>
                            <span class="rounded-full bg-[#eef4ff] px-2 py-1 text-[#004ac6]">/SES-XXXX</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs font-bold text-[#434655]">
                            <span>Login Customer</span>
                            <span class="rounded-full bg-[#eef4ff] px-2 py-1 text-[#004ac6]">WhatsApp</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs font-bold text-[#434655]">
                            <span>Print Request</span>
                            <span class="rounded-full bg-[#eef4ff] px-2 py-1 text-[#004ac6]">Polling</span>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="py-6" id="flow">
                <div data-anime="card" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-black tracking-normal text-[#191b23]">Diagram utama: Android -> Station -> Cloud</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-7 text-[#434655]">
                            Dibuat flow-first supaya operator, admin, dan customer paham posisi cloud dalam beberapa detik.
                        </p>
                    </div>
                    <div class="inline-flex w-fit rounded-full border border-[#d8e5ff] bg-[#eef4ff] px-3 py-2 text-xs font-extrabold text-[#004ac6]">
                        Station tetap pusat event
                    </div>
                </div>

                <div data-anime="card" class="rounded-xl border border-[#c3c6d7]/80 bg-white p-5 shadow-sm">
                    <div class="grid items-stretch gap-3 lg:grid-cols-[1fr_56px_1.12fr_56px_1fr]">
                        <template v-for="(actor, index) in flowActors" :key="actor.label">
                            <article
                                :class="[
                                    'flex min-h-80 flex-col rounded-xl border bg-white p-5',
                                    actor.highlighted ? 'border-[#004ac6]/50 shadow-inner shadow-[#004ac6]/5' : 'border-[#c3c6d7]/80',
                                ]"
                            >
                                <div class="mb-4 flex items-center gap-3 border-b border-[#c3c6d7]/60 pb-4">
                                    <div class="grid size-11 shrink-0 place-items-center rounded-xl bg-[#004ac6] text-white">
                                        <svg v-if="actor.label === 'Android'" class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <rect x="7" y="3" width="10" height="18" rx="2" stroke="currentColor" stroke-width="2" />
                                            <path d="M10.5 18h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                        <svg v-else-if="actor.label === 'Station'" class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <rect x="4" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="2" />
                                            <path d="M8 20h8M10 16v4M14 16v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                        <svg v-else class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M6.5 18.5h10.2a3.7 3.7 0 0 0 .6-7.35A5.5 5.5 0 0 0 6.85 9.5a4.5 4.5 0 0 0-.35 9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-extrabold">{{ actor.title }}</h3>
                                        <p class="text-xs font-bold text-[#737686]">{{ actor.subtitle }}</p>
                                    </div>
                                </div>
                                <ul class="space-y-2">
                                    <li v-for="item in actor.body" :key="item" class="flex items-start gap-2 text-sm font-semibold leading-6 text-[#434655]">
                                        <span class="mt-1 grid size-5 shrink-0 place-items-center rounded-md bg-[#eef4ff] text-xs font-black text-[#004ac6]">✓</span>
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                                <div v-if="actor.highlighted" class="mt-auto pt-4">
                                    <span class="inline-flex rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-extrabold text-orange-800">
                                        Source of truth event ada di Station
                                    </span>
                                </div>
                            </article>

                            <div v-if="index < flowActors.length - 1" :key="`${actor.label}-arrow`" class="grid place-items-center">
                                <div data-anime="arrow" class="grid size-12 rotate-90 place-items-center rounded-xl border border-[#b8cdf8] bg-[#eef4ff] text-[#004ac6] lg:rotate-0">
                                    <svg class="size-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 12h15M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.35" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-[#c3c6d7]/80 bg-[#F8FAFC] p-4 text-sm font-bold leading-6 text-[#434655]">
                            <strong class="text-[#004ac6]">Cloud tidak mencetak langsung.</strong> Cloud hanya menerima request dan menyinkronkan status.
                        </div>
                        <div class="rounded-xl border border-[#c3c6d7]/80 bg-[#F8FAFC] p-4 text-sm font-bold leading-6 text-[#434655]">
                            <strong class="text-[#004ac6]">Station menangani printer fisik.</strong> Semua antrean cetak tetap diproses lokal.
                        </div>
                        <div class="rounded-xl border border-[#c3c6d7]/80 bg-[#F8FAFC] p-4 text-sm font-bold leading-6 text-[#434655]">
                            <strong class="text-[#004ac6]">Customer akses dari cloud.</strong> Gallery, download, dan print request dibuat mobile-first.
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-6">
                <div data-anime="card" class="mb-4">
                    <h2 class="text-3xl font-black tracking-normal">Akses sesuai kebutuhan pengguna</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-[#434655]">
                        Tiga kebutuhan utama dipisahkan agar portal tetap sederhana dan tidak membingungkan.
                    </p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <article v-for="[title, body] in accessCards" :key="title" data-anime="card" class="rounded-xl border border-[#c3c6d7]/80 bg-white p-5 shadow-sm">
                        <h3 class="text-lg font-extrabold">{{ title }}</h3>
                        <p class="mt-3 text-sm leading-7 text-[#434655]">{{ body }}</p>
                    </article>
                </div>
            </section>

            <section class="py-6">
                <div data-anime="card" class="mb-4">
                    <h2 class="text-3xl font-black tracking-normal">Fitur unggulan cloud</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-[#434655]">
                        Fitur dibuat ringan dan langsung mendukung kebutuhan operasional photobooth.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <article v-for="[title, body] in featureCards" :key="title" data-anime="card" class="min-h-40 rounded-xl border border-[#c3c6d7]/80 bg-white p-4 shadow-sm">
                        <h3 class="text-base font-extrabold">{{ title }}</h3>
                        <p class="mt-3 text-sm leading-6 text-[#434655]">{{ body }}</p>
                    </article>
                </div>
            </section>

            <section data-anime="card" class="mb-6 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm sm:p-5">
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

            <footer class="border-t border-[#c3c6d7]/70 py-7 text-center text-sm font-bold text-[#737686]">
                Dafydio Photobooth Cloud - customer portal, archive, and station sync coordinator.
            </footer>
        </section>
    </main>
</template>
