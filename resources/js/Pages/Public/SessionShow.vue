<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    assets: {
        type: Array,
        default: () => [],
    },
});

const originalAssets = computed(() => props.assets.filter((asset) => asset.type === 'original'));
const framedAssets = computed(() => props.assets.filter((asset) => asset.type === 'framed'));
const galleryAssets = computed(() => [...framedAssets.value, ...originalAssets.value]);
const totalAssets = computed(() => props.assets.length);
const selectedAsset = ref(null);
const selectedList = ref([]);
const selectedIndex = ref(0);
const galleryIndex = ref(0);
const touchStartX = ref(0);

const currentGalleryAsset = computed(() => galleryAssets.value[galleryIndex.value] || null);

const formatBytes = (value) => {
    const bytes = Number(value || 0);

    if (bytes < 1) return '-';
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const assetTypeIndex = (asset, index) => galleryAssets.value
    .slice(0, index + 1)
    .filter((item) => item.type === asset.type).length;

const assetTitle = (asset, index) => `${asset.type === 'framed' ? 'Frame' : 'Original'} ${String(assetTypeIndex(asset, index)).padStart(2, '0')}`;
const selectedTitle = computed(() => (selectedAsset.value ? assetTitle(selectedAsset.value, selectedIndex.value) : ''));

const selectAsset = (assets, index) => {
    selectedList.value = assets;
    selectedIndex.value = index;
    selectedAsset.value = {
        ...assets[index],
    };
};

const moveCarousel = (assets, currentIndex, step) => {
    if (assets.length < 1) return 0;

    return (currentIndex + step + assets.length) % assets.length;
};

const moveGallery = (step) => {
    galleryIndex.value = moveCarousel(galleryAssets.value, galleryIndex.value, step);
};

const moveSelected = (step) => {
    selectedIndex.value = moveCarousel(selectedList.value, selectedIndex.value, step);
    selectedAsset.value = {
        ...selectedList.value[selectedIndex.value],
    };
};

const whatsappShareUrl = (asset) => {
    const text = [
        'Dafydio Photobooth',
        props.session.title,
        props.session.code,
        asset.file_url,
    ].filter(Boolean).join('\n');

    return `https://wa.me/?text=${encodeURIComponent(text)}`;
};

const whatsappGalleryShareUrl = computed(() => {
    const text = [
        'Dafydio Photobooth',
        props.session.title,
        `Session: ${props.session.code}`,
        props.session.share_url,
    ].filter(Boolean).join('\n');

    return `https://wa.me/?text=${encodeURIComponent(text)}`;
});

const startTouch = (event) => {
    touchStartX.value = event.changedTouches?.[0]?.clientX || 0;
};

const finishTouch = (event, callback) => {
    const endX = event.changedTouches?.[0]?.clientX || 0;
    const diff = touchStartX.value - endX;

    if (Math.abs(diff) < 45) return;

    callback(diff > 0 ? 1 : -1);
};
</script>

<template>
    <main class="min-h-[100dvh] bg-[#F8FAFC] pb-10 text-[#191b23]">
        <section class="mx-auto max-w-5xl px-4 pt-5">
            <header class="mb-4 flex items-center justify-between gap-3">
                <a class="leading-tight" href="/">
                    <span class="flex items-center gap-2"><img class="size-8 rounded-lg object-cover" :src="'/images/dafydio-booth-icon.png'" alt="Dafydio app icon"><span class="block text-lg font-black text-[#004ac6]">Dafydio</span></span>
                    <span class="block text-[11px] font-semibold uppercase tracking-wide text-[#737686]">Photobooth</span>
                </a>
                <span class="max-w-[56vw] truncate rounded-full bg-[#dbe1ff] px-3 py-1 text-xs font-bold text-[#003ea8]">{{ session.code }}</span>
            </header>

            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-[#737686]">Geser untuk melihat semua foto</p>
                        <h2 class="text-xl font-black">Gallery Photo</h2>
                    </div>
                    <span class="shrink-0 text-sm font-bold text-[#434655]">{{ totalAssets }} file</span>
                </div>

                <div v-if="currentGalleryAsset" class="overflow-hidden rounded-xl border border-[#c3c6d7] bg-white shadow-sm">
                    <div class="relative bg-[#f3f3fe]" @touchstart.passive="startTouch" @touchend.passive="finishTouch($event, moveGallery)">
                        <button v-if="currentGalleryAsset.file_url" class="block w-full" type="button" @click="selectAsset(galleryAssets, galleryIndex)">
                            <img class="max-h-[72vh] min-h-96 w-full object-contain" :src="currentGalleryAsset.file_url" :alt="assetTitle(currentGalleryAsset, galleryIndex)" loading="lazy">
                        </button>
                        <div v-else class="flex min-h-96 items-center justify-center text-sm text-[#737686]">File belum tersedia</div>

                        <div class="absolute inset-x-0 top-0 bg-gradient-to-b from-black/70 to-transparent p-4 text-white">
                            <p class="text-xs font-bold uppercase tracking-wide">Dafydio Photobooth</p>
                            <p class="mt-1 truncate text-sm font-semibold">{{ session.code }}</p>
                        </div>
                        <button v-if="galleryAssets.length > 1" class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-xl font-black text-[#004ac6] shadow" type="button" @click="moveGallery(-1)">&lt;</button>
                        <button v-if="galleryAssets.length > 1" class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-xl font-black text-[#004ac6] shadow" type="button" @click="moveGallery(1)">&gt;</button>
                    </div>

                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-lg font-black">{{ assetTitle(currentGalleryAsset, galleryIndex) }}</p>
                                <p class="mt-1 text-xs font-semibold text-[#737686]">{{ currentGalleryAsset.width && currentGalleryAsset.height ? `${currentGalleryAsset.width} x ${currentGalleryAsset.height}` : 'Resolusi tersimpan' }} - {{ formatBytes(currentGalleryAsset.size_bytes) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-[#f3f3fe] px-3 py-1 text-xs font-black text-[#004ac6]">{{ galleryIndex + 1 }}/{{ galleryAssets.length }}</span>
                        </div>

                        <a v-if="currentGalleryAsset.file_url" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-[#004ac6] px-4 text-sm font-black text-white" :href="currentGalleryAsset.file_url" :download="currentGalleryAsset.download_name">
                            Download Foto
                        </a>
                        <a v-if="currentGalleryAsset.file_url" class="flex min-h-12 w-full items-center justify-center rounded-xl border border-[#25d366] px-4 text-sm font-black text-[#128c3a]" :href="whatsappShareUrl(currentGalleryAsset)" target="_blank" rel="noopener noreferrer">
                            Share WhatsApp
                        </a>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <a v-if="session.download_all_url" class="flex min-h-12 w-full items-center justify-center rounded-xl border border-[#004ac6] px-4 text-sm font-black text-[#004ac6]" :href="session.download_all_url">
                                Download Semua
                            </a>
                            <a v-if="session.share_url" class="flex min-h-12 w-full items-center justify-center rounded-xl border border-[#25d366] px-4 text-sm font-black text-[#128c3a]" :href="whatsappGalleryShareUrl" target="_blank" rel="noopener noreferrer">
                                Share Link Gallery
                            </a>
                        </div>
                    </div>
                </div>
                <p v-else class="rounded-xl border border-[#c3c6d7] bg-white p-5 text-sm text-[#434655]">Belum ada foto uploaded.</p>
            </section>

            <p v-if="totalAssets === 0" class="rounded-xl border border-[#c3c6d7] bg-white p-5 text-sm text-[#434655]">
                Belum ada foto yang siap di-download untuk session ini.
            </p>
        </section>

        <div v-if="selectedAsset" class="fixed inset-0 z-50 bg-[#090b12] text-white">
            <div class="flex h-[100dvh] flex-col">
                <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black">{{ selectedTitle }}</p>
                        <p class="text-xs font-semibold text-white/65">Dafydio Photobooth</p>
                    </div>
                    <button class="min-h-11 rounded-xl bg-white/10 px-4 text-sm font-bold" type="button" @click="selectedAsset = null">Tutup</button>
                </div>
                <div class="relative flex min-h-0 flex-1 items-center justify-center bg-black" @touchstart.passive="startTouch" @touchend.passive="finishTouch($event, moveSelected)">
                    <img class="max-h-full max-w-full object-contain" :src="selectedAsset.file_url" :alt="selectedTitle">
                    <button v-if="selectedList.length > 1" class="absolute left-3 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl font-black text-white backdrop-blur" type="button" @click="moveSelected(-1)">&lt;</button>
                    <button v-if="selectedList.length > 1" class="absolute right-3 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl font-black text-white backdrop-blur" type="button" @click="moveSelected(1)">&gt;</button>
                    <span v-if="selectedList.length > 1" class="absolute bottom-4 rounded-full bg-black/55 px-3 py-1 text-xs font-black text-white">{{ selectedIndex + 1 }}/{{ selectedList.length }}</span>
                </div>
                <div class="space-y-3 border-t border-white/10 bg-[#090b12] p-4">
                    <a class="flex min-h-12 w-full items-center justify-center rounded-xl bg-white px-4 text-sm font-black text-[#004ac6]" :href="selectedAsset.file_url" :download="selectedAsset.download_name">
                        Download Foto
                    </a>
                    <a class="flex min-h-12 w-full items-center justify-center rounded-xl border border-[#25d366] px-4 text-sm font-black text-[#5df28a]" :href="whatsappShareUrl(selectedAsset)" target="_blank" rel="noopener noreferrer">
                        Share WhatsApp
                    </a>
                    <a v-if="session.share_url" class="flex min-h-11 w-full items-center justify-center rounded-xl border border-[#25d366] px-4 text-sm font-bold text-[#5df28a]" :href="whatsappGalleryShareUrl" target="_blank" rel="noopener noreferrer">
                        Share Link Gallery
                    </a>
                </div>
            </div>
        </div>
    </main>
</template>
