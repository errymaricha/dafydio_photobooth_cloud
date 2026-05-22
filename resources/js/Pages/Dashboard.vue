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
                            viewBox="0 0 900 520"
                            role="img"
                            aria-labelledby="photobooth-flow-title photobooth-flow-desc"
                        >
                            <title id="photobooth-flow-title">Alur aplikasi Dafydio Photobooth</title>
                            <desc id="photobooth-flow-desc">Diagram sederhana Android Device ke Station lalu Cloud.</desc>

                            <defs>
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

                            <rect width="900" height="520" rx="28" fill="#f8faff" />
                            <text x="450" y="48" text-anchor="middle" fill="#111827" font-size="30" font-weight="800">Alur Kerja Dafydio Booth</text>
                            <text x="450" y="78" text-anchor="middle" fill="#434655" font-size="17">Android untuk capture, Station untuk proses lokal, Cloud untuk gallery dan arsip.</text>

                            <g filter="url(#soft-shadow)">
                                <rect x="48" y="128" width="220" height="210" rx="24" fill="#ffffff" stroke="#004ac6" stroke-width="3" />
                                <circle cx="158" cy="184" r="34" fill="#dbe1ff" stroke="#004ac6" stroke-width="3" />
                                <rect x="143" y="158" width="30" height="54" rx="8" fill="#004ac6" />
                                <circle cx="158" cy="185" r="9" fill="#ffffff" />
                                <text x="158" y="250" text-anchor="middle" fill="#191b23" font-size="23" font-weight="800">1. Android</text>
                                <text x="158" y="280" text-anchor="middle" fill="#434655" font-size="16">Capture foto</text>
                                <text x="158" y="304" text-anchor="middle" fill="#434655" font-size="16">dan mulai event</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="340" y="128" width="220" height="210" rx="24" fill="#ffffff" stroke="#004ac6" stroke-width="3" />
                                <circle cx="450" cy="184" r="34" fill="#dbe1ff" stroke="#004ac6" stroke-width="3" />
                                <rect x="426" y="168" width="48" height="12" rx="6" fill="#004ac6" />
                                <rect x="426" y="188" width="48" height="12" rx="6" fill="#004ac6" opacity="0.65" />
                                <ellipse cx="450" cy="210" rx="25" ry="7" fill="#0b6b21" opacity="0.82" />
                                <text x="450" y="250" text-anchor="middle" fill="#191b23" font-size="23" font-weight="800">2. Station</text>
                                <text x="450" y="280" text-anchor="middle" fill="#434655" font-size="16">Render, local DB,</text>
                                <text x="450" y="304" text-anchor="middle" fill="#434655" font-size="16">dan printer queue</text>
                            </g>

                            <g filter="url(#soft-shadow)">
                                <rect x="632" y="128" width="220" height="210" rx="24" fill="#ffffff" stroke="#004ac6" stroke-width="3" />
                                <circle cx="742" cy="184" r="34" fill="#dbe1ff" stroke="#004ac6" stroke-width="3" />
                                <path d="M719 190 H764 C775 190 783 182 783 172 C783 164 776 157 767 156 C763 145 752 139 740 142 C730 144 724 151 721 160 C710 162 703 169 703 178 C703 186 710 190 719 190 Z" fill="#004ac6" />
                                <text x="742" y="250" text-anchor="middle" fill="#191b23" font-size="23" font-weight="800">3. Cloud</text>
                                <text x="742" y="280" text-anchor="middle" fill="#434655" font-size="16">Gallery, arsip,</text>
                                <text x="742" y="304" text-anchor="middle" fill="#434655" font-size="16">dan admin portal</text>
                            </g>

                            <path data-anime="flow-line" pathLength="34" d="M268 232 H338" fill="none" stroke="#004ac6" stroke-width="6" stroke-linecap="round" stroke-dasharray="12 8" marker-end="url(#arrow-blue)" />
                            <path data-anime="flow-line" pathLength="34" d="M560 232 H630" fill="none" stroke="#004ac6" stroke-width="6" stroke-linecap="round" stroke-dasharray="12 8" marker-end="url(#arrow-blue)" />
                            <text x="303" y="209" text-anchor="middle" fill="#003ea8" font-size="15" font-weight="800">event/session</text>
                            <text x="595" y="209" text-anchor="middle" fill="#003ea8" font-size="15" font-weight="800">sync asset</text>

                            <g>
                                <rect x="58" y="384" width="240" height="84" rx="18" fill="#ffffff" stroke="#0b6b21" stroke-width="2" />
                                <circle cx="93" cy="426" r="18" fill="#0b6b21" />
                                <path d="M86 427 L92 435 L103 416" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                <text x="178" y="419" text-anchor="middle" fill="#0b6b21" font-size="17" font-weight="800">Source of truth</text>
                                <text x="178" y="443" text-anchor="middle" fill="#0b6b21" font-size="14" font-weight="700">Event utama ada di Station</text>
                            </g>

                            <g>
                                <rect x="330" y="384" width="240" height="84" rx="18" fill="#ffffff" stroke="#004ac6" stroke-width="2" />
                                <circle cx="365" cy="426" r="18" fill="#004ac6" />
                                <text x="365" y="434" text-anchor="middle" fill="#ffffff" font-size="24" font-weight="900">!</text>
                                <text x="450" y="419" text-anchor="middle" fill="#003ea8" font-size="17" font-weight="800">Cloud bukan printer</text>
                                <text x="450" y="443" text-anchor="middle" fill="#003ea8" font-size="14" font-weight="700">Station tetap cetak fisik</text>
                            </g>

                            <g>
                                <rect x="602" y="384" width="240" height="84" rx="18" fill="#ffffff" stroke="#8B5CF6" stroke-width="2" />
                                <circle cx="637" cy="426" r="18" fill="#8B5CF6" />
                                <path d="M628 426 H646 M638 417 L648 426 L638 435" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                <text x="722" y="419" text-anchor="middle" fill="#5b21b6" font-size="17" font-weight="800">Link pendek</text>
                                <text x="722" y="443" text-anchor="middle" fill="#5b21b6" font-size="14" font-weight="700">Gallery mudah dibagikan</text>
                            </g>

                            <path data-anime="flow-line" pathLength="34" d="M742 338 V358 H450 V340" fill="none" stroke="#8B5CF6" stroke-width="4" stroke-dasharray="10 8" marker-end="url(#arrow-green)" opacity="0.95" />
                            <text x="596" y="356" text-anchor="middle" fill="#5b21b6" font-size="14" font-weight="800">print request dipolling station</text>

                            <circle data-anime="flow-pulse" cx="303" cy="232" r="8" fill="#004ac6" />
                            <circle data-anime="flow-pulse" cx="595" cy="232" r="8" fill="#8B5CF6" />
                            <circle data-anime="flow-pulse" cx="596" cy="358" r="8" fill="#fea619" />
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
