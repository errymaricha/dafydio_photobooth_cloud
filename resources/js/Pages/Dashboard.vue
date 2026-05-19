<script setup>
const quickActions = [
    ['Lihat Foto', 'Buka arsip sesi terbaru'],
    ['Download', 'Original dan framed photo'],
    ['Edit', 'Pilih template untuk foto'],
    ['Cetak', 'Kirim request ke station'],
];

const statusItems = [
    ['Session', 'Menunggu data customer'],
    ['Subscription', 'Regular / Premium'],
    ['Storage', 'S3/R2 ready'],
];
</script>

<template>
    <main class="min-h-screen bg-stone-50 text-zinc-950">
        <section class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 py-5 sm:px-6 sm:py-8">
            <header class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img class="size-12 rounded-xl object-cover shadow-sm" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Dafydio Cloud</p>
                        <h1 class="mt-1 text-2xl font-semibold leading-tight sm:text-4xl">Portal Foto Photobooth</h1>
                    </div>
                </div>
                <div class="shrink-0 border border-teal-200 bg-teal-50 px-3 py-2 text-right">
                    <p class="text-xs text-teal-800">Status</p>
                    <p class="text-sm font-semibold text-teal-950">Online</p>
                </div>
            </header>

            <section class="mt-6 border border-zinc-200 bg-white p-4 shadow-sm sm:p-6">
                <p class="text-sm leading-6 text-zinc-600">Masuk dengan nomor WhatsApp untuk melihat arsip foto, download hasil, edit dengan template, atau membuat request cetak.</p>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button
                        v-for="[title, body] in quickActions"
                        :key="title"
                        class="min-h-24 border border-zinc-200 bg-zinc-50 p-3 text-left transition hover:border-teal-300 hover:bg-teal-50"
                        type="button"
                    >
                        <span class="block text-base font-semibold">{{ title }}</span>
                        <span class="mt-2 block text-xs leading-5 text-zinc-600">{{ body }}</span>
                    </button>
                </div>
            </section>

            <section class="mt-4 grid gap-3 sm:grid-cols-3">
                <article v-for="[label, value] in statusItems" :key="label" class="border border-zinc-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ label }}</p>
                    <p class="mt-2 text-sm font-semibold leading-6">{{ value }}</p>
                </article>
            </section>

            <section class="mt-4 border border-zinc-200 bg-white p-4 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Alur Cloud dan Station</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            Cloud menyimpan arsip dan request. Station tetap menangani capture, render lokal, queue printer, dan cetak fisik.
                        </p>
                    </div>
                    <a class="inline-flex min-h-12 items-center border border-zinc-900 bg-zinc-950 px-5 text-sm font-semibold text-white" href="/login">
                        Buka Portal
                    </a>
                </div>
            </section>

            <div class="mt-auto pt-8">
                <p class="text-xs leading-5 text-zinc-500">Mobile-first baseline. Data asli akan muncul setelah customer login dan station sync berjalan.</p>
            </div>
        </section>
    </main>
</template>
