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
                            viewBox="0 0 980 520"
                            role="img"
                            aria-labelledby="photobooth-flow-title photobooth-flow-desc"
                        >
                            <title id="photobooth-flow-title">Alur aplikasi Dafydio Photobooth</title>
                            <desc id="photobooth-flow-desc">Diagram dari Android Device ke Photobooth Station API, Station Database, Cloud, lalu Customer dan Admin.</desc>

                            <defs>
                                <linearGradient id="flow-blue" x1="0%" x2="100%" y1="0%" y2="0%">
                                    <stop offset="0%" stop-color="#004ac6" />
                                    <stop offset="100%" stop-color="#8B5CF6" />
                                </linearGradient>
                                <linearGradient id="phone-screen" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="#dbe1ff" />
                                    <stop offset="100%" stop-color="#ffffff" />
                                </linearGradient>
                                <marker id="arrow-blue" markerHeight="10" markerWidth="10" orient="auto" refX="9" refY="5">
                                    <path d="M0 0 L10 5 L0 10 Z" fill="#004ac6" />
                                </marker>
                                <marker id="arrow-green" markerHeight="10" markerWidth="10" orient="auto" refX="9" refY="5">
                                    <path d="M0 0 L10 5 L0 10 Z" fill="#0b6b21" />
                                </marker>
                                <filter id="soft-shadow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="8" stdDeviation="9" flood-color="#00174b" flood-opacity="0.12" />
                                </filter>
                            </defs>

                            <rect width="980" height="520" rx="28" fill="#f8faff" />
                            <text x="490" y="48" text-anchor="middle" fill="#111827" font-size="34" font-weight="800">Alur Android - Station - Cloud</text>
                            <line x1="50" y1="76" x2="930" y2="76" stroke="#004ac6" stroke-width="3" />
                            <circle data-anime="flow-pulse" cx="490" cy="76" r="18" fill="#dbe1ff" stroke="#004ac6" stroke-width="3" />
                            <path d="M482 82 L490 66 L498 82 Z" fill="#004ac6" />

                            <g filter="url(#soft-shadow)">
                                <rect x="30" y="114" width="170" height="248" rx="18" fill="#ffffff" stroke="#004ac6" stroke-width="2" />
                                <text x="115" y="148" text-anchor="middle" fill="#003ea8" font-size="20" font-weight="800">Android Device</text>
                                <text x="115" y="173" text-anchor="middle" fill="#003ea8" font-size="15" font-style="italic">Capture Event</text>
                                <rect x="78" y="198" width="76" height="128" rx="14" fill="#111827" />
                                <rect x="86" y="211" width="60" height="96" rx="8" fill="url(#phone-screen)" />
                                <circle cx="116" cy="259" r="22" fill="#66bd10" />
                                <rect x="108" y="237" width="16" height="45" rx="6" fill="#66bd10" />
                                <rect x="93" y="256" width="10" height="28" rx="5" fill="#66bd10" />
                                <rect x="129" y="256" width="10" height="28" rx="5" fill="#66bd10" />
                                <line x1="103" y1="233" x2="96" y2="220" stroke="#66bd10" stroke-width="3" stroke-linecap="round" />
                                <line x1="129" y1="233" x2="136" y2="220" stroke="#66bd10" stroke-width="3" stroke-linecap="round" />
                                <text x="115" y="348" text-anchor="middle" fill="#434655" font-size="13">launch event</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="330" y="114" width="170" height="248" rx="18" fill="#ffffff" stroke="#004ac6" stroke-width="2" />
                                <text x="415" y="146" text-anchor="middle" fill="#003ea8" font-size="20" font-weight="800">Photobooth</text>
                                <text x="415" y="171" text-anchor="middle" fill="#003ea8" font-size="20" font-weight="800">Station API</text>
                                <rect x="374" y="210" width="82" height="28" rx="10" fill="#1d6fbb" stroke="#003ea8" stroke-width="3" />
                                <rect x="374" y="252" width="82" height="28" rx="10" fill="#1d6fbb" stroke="#003ea8" stroke-width="3" />
                                <rect x="374" y="294" width="82" height="28" rx="10" fill="#1d6fbb" stroke="#003ea8" stroke-width="3" />
                                <circle cx="388" cy="224" r="6" fill="#dbeafe" />
                                <circle cx="388" cy="266" r="6" fill="#dbeafe" />
                                <circle cx="388" cy="308" r="6" fill="#dbeafe" />
                                <circle cx="438" cy="224" r="4" fill="#9bd3ff" opacity="0.8" />
                                <circle cx="450" cy="224" r="4" fill="#9bd3ff" opacity="0.8" />
                                <circle cx="438" cy="266" r="4" fill="#9bd3ff" opacity="0.8" />
                                <circle cx="450" cy="266" r="4" fill="#9bd3ff" opacity="0.8" />
                                <circle cx="438" cy="308" r="4" fill="#9bd3ff" opacity="0.8" />
                                <circle cx="450" cy="308" r="4" fill="#9bd3ff" opacity="0.8" />
                                <circle cx="462" cy="305" r="26" fill="#2563eb" stroke="#003ea8" stroke-width="4" />
                                <text x="462" y="313" text-anchor="middle" fill="#ffffff" font-size="20" font-weight="800">API</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="570" y="114" width="170" height="248" rx="18" fill="#f8fff8" stroke="#0b6b21" stroke-width="2" />
                                <text x="655" y="146" text-anchor="middle" fill="#0b6b21" font-size="20" font-weight="800">Station Database</text>
                                <text x="655" y="171" text-anchor="middle" fill="#0b6b21" font-size="15" font-style="italic">local source</text>
                                <ellipse cx="655" cy="228" rx="48" ry="18" fill="#7bd77b" stroke="#0b6b21" stroke-width="4" />
                                <path d="M607 228 V304 C607 314 629 322 655 322 C681 322 703 314 703 304 V228" fill="#58bd58" stroke="#0b6b21" stroke-width="4" />
                                <path d="M607 264 C607 274 629 282 655 282 C681 282 703 274 703 264" fill="none" stroke="#0b6b21" stroke-width="4" />
                                <circle cx="688" cy="256" r="5" fill="#ffffff" />
                                <circle cx="688" cy="295" r="5" fill="#ffffff" />
                                <text x="655" y="350" text-anchor="middle" fill="#0b6b21" font-size="13" font-weight="700">event + session tersimpan lokal</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="800" y="114" width="150" height="248" rx="18" fill="#ffffff" stroke="#004ac6" stroke-width="2" />
                                <text x="875" y="148" text-anchor="middle" fill="#003ea8" font-size="22" font-weight="800">Cloud</text>
                                <path d="M838 252 H900 C918 252 932 240 932 222 C932 206 920 194 904 191 C898 171 880 160 858 166 C842 169 830 181 826 198 C810 201 798 213 798 229 C798 243 814 252 838 252 Z" fill="#60a5fa" stroke="#004ac6" stroke-width="4" />
                                <path d="M858 256 V224 M858 224 L842 240 M858 224 L874 240" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M895 220 V252 M895 252 L879 236 M895 252 L911 236" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                                <text x="875" y="318" text-anchor="middle" fill="#434655" font-size="13">archive, gallery, billing</text>
                            </g>

                            <path data-anime="flow-line" pathLength="34" d="M200 182 H328" fill="none" stroke="#004ac6" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" marker-end="url(#arrow-blue)" />
                            <path data-anime="flow-line" pathLength="34" d="M200 238 H328" fill="none" stroke="#004ac6" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" marker-end="url(#arrow-blue)" />
                            <path data-anime="flow-line" pathLength="34" d="M200 294 H328" fill="none" stroke="#004ac6" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" marker-end="url(#arrow-blue)" />
                            <text x="262" y="165" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">create event</text>
                            <text x="262" y="222" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">update event</text>
                            <text x="262" y="278" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">create session</text>

                            <path data-anime="flow-line" pathLength="34" d="M500 238 H568" fill="none" stroke="#0b6b21" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" marker-end="url(#arrow-green)" />
                            <text x="535" y="219" text-anchor="middle" fill="#0b6b21" font-size="14" font-weight="700">save/update</text>

                            <path data-anime="flow-line" pathLength="34" d="M740 238 H798" fill="none" stroke="#004ac6" stroke-width="5" stroke-linecap="round" stroke-dasharray="10 8" marker-end="url(#arrow-blue)" />
                            <text x="768" y="206" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">sync session</text>
                            <text x="768" y="224" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">upload asset</text>

                            <path data-anime="flow-line" pathLength="34" d="M875 362 V402 H662 V364" fill="none" stroke="#004ac6" stroke-width="4" stroke-dasharray="9 8" marker-end="url(#arrow-blue)" opacity="0.95" />
                            <text x="770" y="392" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">station polling print request</text>

                            <g>
                                <rect x="526" y="396" width="260" height="70" rx="14" fill="#ffffff" stroke="#0b6b21" stroke-width="2" stroke-dasharray="8 6" />
                                <circle cx="558" cy="431" r="19" fill="#0b6b21" />
                                <path d="M551 432 L557 439 L568 421" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                <text x="660" y="424" text-anchor="middle" fill="#0b6b21" font-size="17" font-weight="800">Source of truth</text>
                                <text x="660" y="448" text-anchor="middle" fill="#0b6b21" font-size="15" font-weight="700">event ada di Station DB</text>
                            </g>

                            <g>
                                <rect x="790" y="396" width="160" height="70" rx="14" fill="#ffffff" stroke="#004ac6" stroke-width="2" stroke-dasharray="8 6" />
                                <circle cx="818" cy="431" r="18" fill="#004ac6" />
                                <text x="818" y="439" text-anchor="middle" fill="#ffffff" font-size="26" font-weight="900">!</text>
                                <text x="876" y="423" text-anchor="middle" fill="#003ea8" font-size="15" font-weight="800">Cloud tidak</text>
                                <text x="876" y="446" text-anchor="middle" fill="#003ea8" font-size="15" font-weight="800">membuat event</text>
                            </g>

                            <circle data-anime="flow-pulse" cx="226" cy="182" r="8" fill="#004ac6" />
                            <circle data-anime="flow-pulse" cx="226" cy="238" r="8" fill="#004ac6" />
                            <circle data-anime="flow-pulse" cx="226" cy="294" r="8" fill="#004ac6" />
                            <circle data-anime="flow-pulse" cx="532" cy="238" r="8" fill="#0b6b21" />
                            <circle data-anime="flow-pulse" cx="768" cy="238" r="8" fill="#8B5CF6" />
                            <circle data-anime="flow-pulse" cx="772" cy="402" r="8" fill="#fea619" />
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
