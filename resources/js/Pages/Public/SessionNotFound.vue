<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { animate, stagger } from 'animejs';

defineProps({
    sessionCode: {
        type: String,
        required: true,
    },
    homeUrl: {
        type: String,
        default: '/',
    },
});

const pageRoot = ref(null);
const runningAnimations = [];

onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || !pageRoot.value) {
        return;
    }

    runningAnimations.push(
        animate(pageRoot.value.querySelectorAll('[data-anime="piece"]'), {
            translateY: [-7, 7],
            rotate: [-1.5, 1.5],
            duration: 1400,
            delay: stagger(120),
            alternate: true,
            loop: true,
            ease: 'inOutSine',
        }),
        animate(pageRoot.value.querySelector('[data-anime="crack"]'), {
            opacity: [0.45, 1],
            duration: 900,
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
    <Head title="Kode Gallery Salah | Dafydio Photobooth">
        <meta head-key="robots" name="robots" content="noindex, nofollow">
        <meta head-key="description" name="description" content="Kode gallery Dafydio Photobooth tidak ditemukan.">
    </Head>

    <main ref="pageRoot" class="grid min-h-screen place-items-center bg-[#F8FAFC] px-4 py-10 text-[#191b23]">
        <section class="w-full max-w-md rounded-xl border border-[#c3c6d7] bg-white p-6 text-center shadow-sm">
            <div class="mx-auto mb-6 grid size-40 place-items-center rounded-xl bg-[#f3f3fe]">
                <svg class="h-32 w-32" viewBox="0 0 180 180" fill="none" aria-hidden="true">
                    <rect x="28" y="30" width="124" height="120" rx="18" fill="#ffffff" stroke="#004ac6" stroke-width="6" />
                    <path data-anime="piece" d="M44 120 L72 92 L92 112 L112 82 L138 120 Z" fill="#dbe1ff" stroke="#004ac6" stroke-width="4" stroke-linejoin="round" />
                    <circle data-anime="piece" cx="67" cy="64" r="12" fill="#fea619" />
                    <path data-anime="crack" d="M91 34 L78 66 L97 83 L82 111 L101 148" stroke="#ba1a1a" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
                    <path data-anime="piece" d="M100 82 L121 67 L113 90 L136 99" stroke="#ba1a1a" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                    <rect x="42" y="142" width="96" height="8" rx="4" fill="#c3c6d7" />
                </svg>
            </div>

            <p class="text-xs font-extrabold uppercase tracking-wide text-[#004ac6]">Gallery tidak ditemukan</p>
            <h1 class="mt-3 text-3xl font-black leading-tight">Kode gallery salah</h1>
            <p class="mt-3 text-sm leading-7 text-[#434655]">
                Kode <span class="font-extrabold text-[#191b23]">{{ sessionCode }}</span> tidak cocok dengan gallery yang tersimpan di Dafydio Cloud.
            </p>
            <p class="mt-2 text-xs leading-6 text-[#737686]">
                Periksa kembali kode dari WhatsApp atau minta operator photobooth mengirim ulang link gallery.
            </p>

            <div class="mt-6 grid gap-3">
                <Link class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#004ac6] px-5 text-sm font-extrabold text-white transition hover:bg-[#2563eb]" :href="homeUrl">
                    Kembali ke halaman depan
                </Link>
                <Link class="inline-flex min-h-12 items-center justify-center rounded-xl border border-[#c3c6d7] bg-white px-5 text-sm font-extrabold text-[#004ac6] transition hover:bg-[#f3f3fe]" href="/login?mode=customer">
                    Masuk Customer
                </Link>
            </div>
        </section>
    </main>
</template>
