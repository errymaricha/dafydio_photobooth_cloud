<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});

const visibleLinks = computed(() => (props.paginator.links || []).filter((link) => (
    link.label !== '&laquo; Previous'
    && link.label !== 'Next &raquo;'
)));

const previousUrl = computed(() => props.paginator.prev_page_url || props.paginator.prev_page_url === null
    ? props.paginator.prev_page_url
    : (props.paginator.links || [])[0]?.url);

const nextUrl = computed(() => props.paginator.next_page_url || props.paginator.next_page_url === null
    ? props.paginator.next_page_url
    : (props.paginator.links || []).at(-1)?.url);
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="flex flex-col gap-3 border-t border-[#c3c6d7] bg-white px-4 py-4 md:flex-row md:items-center md:justify-between" aria-label="Pagination">
        <p class="text-xs font-semibold text-[#434655]">
            Menampilkan {{ paginator.from || 0 }}-{{ paginator.to || 0 }} dari {{ paginator.total || 0 }} data
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <Link
                class="inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] px-3 text-xs font-black"
                :class="previousUrl ? 'bg-white text-[#004ac6] hover:bg-[#f3f3fe]' : 'cursor-not-allowed bg-[#f3f3fe] text-[#737686]'"
                :href="previousUrl || '#'"
                preserve-scroll
                :disabled="!previousUrl"
            >
                Prev
            </Link>
            <Link
                v-for="link in visibleLinks"
                :key="link.label"
                class="hidden min-h-10 min-w-10 items-center justify-center rounded-lg border px-3 text-xs font-black sm:inline-flex"
                :class="link.active ? 'border-[#004ac6] bg-[#004ac6] text-white' : 'border-[#c3c6d7] bg-white text-[#004ac6] hover:bg-[#f3f3fe]'"
                :href="link.url || '#'"
                preserve-scroll
                :disabled="!link.url"
            >
                {{ link.label }}
            </Link>
            <span class="inline-flex min-h-10 items-center rounded-lg bg-[#f3f3fe] px-3 text-xs font-black text-[#434655] sm:hidden">
                {{ paginator.current_page }} / {{ paginator.last_page }}
            </span>
            <Link
                class="inline-flex min-h-10 items-center rounded-lg border border-[#c3c6d7] px-3 text-xs font-black"
                :class="nextUrl ? 'bg-white text-[#004ac6] hover:bg-[#f3f3fe]' : 'cursor-not-allowed bg-[#f3f3fe] text-[#737686]'"
                :href="nextUrl || '#'"
                preserve-scroll
                :disabled="!nextUrl"
            >
                Next
            </Link>
        </div>
    </nav>
</template>
