<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { animate, stagger } from 'animejs';

const sessionCode = ref('');
const pageRoot = ref(null);
const runningAnimations = [];

const sanitizedSessionCode = computed(() => sessionCode.value.trim().toUpperCase());
const publicGalleryUrl = computed(() => (sanitizedSessionCode.value ? `/${sanitizedSessionCode.value}` : '#'));

const advantageCards = [
    {
        label: 'Android',
        title: 'Capture cepat di lokasi event',
        body: 'Perangkat Android fokus mengambil foto dan menjaga workflow operator tetap sederhana.',
    },
    {
        label: 'Station',
        title: 'Render lokal dan print fisik',
        body: 'Station menangani session lokal, queue printer, retry, dan polling print request dari cloud.',
    },
    {
        label: 'Cloud',
        title: 'Gallery, arsip, dan share link pendek',
        body: 'Cloud menyimpan asset, login customer, public gallery, admin console, dan koordinasi print request.',
    },
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
            <header data-anime="card" class="flex items-center justify-between gap-4">
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
                    <div data-anime="card" class="inline-flex items-center rounded-full border border-[#c3c6d7] bg-white px-3 py-1.5 text-xs font-semibold text-[#434655] shadow-sm">
                        Customer gallery, admin console, station sync
                    </div>
                    <h1 data-anime="card" class="mt-5 max-w-3xl text-4xl font-extrabold leading-tight tracking-normal text-[#191b23] sm:text-5xl">
                        Portal utama Dafydio Photobooth Cloud.
                    </h1>
                    <p data-anime="card" class="mt-4 max-w-2xl text-base leading-7 text-[#434655]">
                        Buka gallery customer dari kode session, masuk ke portal WhatsApp, atau kelola operasional tenant dan station dari satu halaman.
                    </p>

                    <div data-anime="card" class="mt-6 grid gap-3 sm:grid-cols-2">
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

                    <form data-anime="card" class="mt-6 rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm" @submit.prevent>
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
                    <section data-anime="card" class="rounded-xl border border-[#c3c6d7] bg-white p-4 shadow-sm sm:col-span-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-[#004ac6]">Alur Photobooth</p>
                                <h2 class="mt-2 text-xl font-bold leading-snug">Android, Station, dan Cloud bekerja sesuai perannya.</h2>
                            </div>
                            <span class="hidden rounded-full bg-[#dbe1ff] px-3 py-1 text-xs font-bold text-[#003ea8] sm:inline-flex">Live Sync</span>
                        </div>

                        <svg
                            class="mt-4 h-auto w-full"
                            viewBox="0 0 720 420"
                            role="img"
                            aria-labelledby="photobooth-flow-title photobooth-flow-desc"
                        >
                            <title id="photobooth-flow-title">Alur aplikasi Dafydio Photobooth</title>
                            <desc id="photobooth-flow-desc">Diagram dari Android capture ke Station, Cloud, lalu Customer dan Admin.</desc>

                            <defs>
                                <linearGradient id="flow-blue" x1="0%" x2="100%" y1="0%" y2="0%">
                                    <stop offset="0%" stop-color="#004ac6" />
                                    <stop offset="100%" stop-color="#8B5CF6" />
                                </linearGradient>
                                <filter id="soft-shadow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="8" stdDeviation="9" flood-color="#00174b" flood-opacity="0.12" />
                                </filter>
                            </defs>

                            <rect width="720" height="420" rx="28" fill="#f3f3fe" />
                            <path d="M32 340 C160 285 218 390 330 322 C438 257 514 321 690 250" fill="none" stroke="#dbe1ff" stroke-width="18" stroke-linecap="round" />

                            <g filter="url(#soft-shadow)">
                                <rect x="34" y="86" width="146" height="206" rx="24" fill="#ffffff" stroke="#c3c6d7" />
                                <rect x="76" y="110" width="62" height="120" rx="16" fill="#dbe1ff" stroke="#004ac6" stroke-width="3" />
                                <circle cx="107" cy="170" r="22" fill="#004ac6" />
                                <circle cx="107" cy="170" r="10" fill="#ffffff" opacity="0.88" />
                                <text x="107" y="262" text-anchor="middle" fill="#191b23" font-size="22" font-weight="700">Android</text>
                                <text x="107" y="284" text-anchor="middle" fill="#434655" font-size="13">Capture foto</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="288" y="78" width="158" height="214" rx="24" fill="#ffffff" stroke="#c3c6d7" />
                                <rect x="324" y="114" width="86" height="92" rx="12" fill="#ffddb8" stroke="#855300" stroke-width="3" />
                                <path d="M330 222 H404 L420 246 H314 Z" fill="#191b23" />
                                <rect x="336" y="136" width="62" height="12" rx="6" fill="#855300" opacity="0.55" />
                                <rect x="336" y="160" width="62" height="12" rx="6" fill="#855300" opacity="0.35" />
                                <text x="367" y="262" text-anchor="middle" fill="#191b23" font-size="22" font-weight="700">Station</text>
                                <text x="367" y="284" text-anchor="middle" fill="#434655" font-size="13">Render + print</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="536" y="76" width="150" height="150" rx="28" fill="#ffffff" stroke="#c3c6d7" />
                                <path d="M584 158 H642 C658 158 670 147 670 132 C670 118 660 107 646 105 C640 87 625 78 606 82 C592 84 582 94 578 108 C562 110 550 121 550 136 C550 149 563 158 584 158 Z" fill="#dbe1ff" stroke="#004ac6" stroke-width="3" />
                                <text x="611" y="194" text-anchor="middle" fill="#191b23" font-size="22" font-weight="700">Cloud</text>
                                <text x="611" y="216" text-anchor="middle" fill="#434655" font-size="13">Archive + portal</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="488" y="280" width="198" height="86" rx="22" fill="#ffffff" stroke="#c3c6d7" />
                                <circle cx="532" cy="323" r="20" fill="#fea619" />
                                <path d="M526 324 L532 331 L542 315" fill="none" stroke="#684000" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                <text x="586" y="316" fill="#191b23" font-size="20" font-weight="700">Customer / Admin</text>
                                <text x="586" y="340" fill="#434655" font-size="13">Gallery, billing, monitoring</text>
                            </g>

                            <path data-anime="flow-line" pathLength="34" d="M182 186 C226 168 246 168 286 182" fill="none" stroke="url(#flow-blue)" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" />
                            <path data-anime="flow-line" pathLength="34" d="M448 178 C484 145 506 134 536 136" fill="none" stroke="url(#flow-blue)" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" />
                            <path data-anime="flow-line" pathLength="34" d="M612 228 C606 250 598 264 588 280" fill="none" stroke="url(#flow-blue)" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" />
                            <path data-anime="flow-line" pathLength="34" d="M488 324 C412 344 336 330 282 284" fill="none" stroke="#10B981" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" opacity="0.9" />

                            <circle data-anime="flow-pulse" cx="214" cy="176" r="9" fill="#004ac6" />
                            <circle data-anime="flow-pulse" cx="492" cy="146" r="9" fill="#8B5CF6" />
                            <circle data-anime="flow-pulse" cx="604" cy="252" r="9" fill="#fea619" />
                            <circle data-anime="flow-pulse" cx="388" cy="330" r="8" fill="#10B981" />
                        </svg>
                    </section>

                    <article
                        v-for="card in advantageCards"
                        :key="card.label"
                        data-anime="card"
                        class="rounded-xl border border-[#c3c6d7] bg-white p-5 shadow-sm"
                    >
                        <p class="text-xs font-bold uppercase tracking-wide text-[#004ac6]">{{ card.label }}</p>
                        <h2 class="mt-3 text-lg font-bold leading-snug">{{ card.title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-[#434655]">{{ card.body }}</p>
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
        </section>
    </main>
</template>
