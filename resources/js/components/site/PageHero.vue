<script setup lang="ts">
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import WaveDivider from '@/components/site/WaveDivider.vue';

/**
 * The dark, river-coloured band at the top of every page below the home
 * page. It holds the page's only h1.
 */
defineProps<{
    title: string;
    summary?: string | null;
    eyebrow?: string | null;
}>();
</script>

<template>
    <header class="relative isolate bg-ink-900 text-white">
        <div class="contours absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="shell pt-6 pb-10 sm:pt-8 sm:pb-14">
            <Breadcrumbs class="text-river-200" />
            <p v-if="eyebrow" class="eyebrow mt-6 !text-gold-400">
                {{ eyebrow }}
            </p>
            <h1 class="text-title" :class="eyebrow ? 'mt-2' : 'mt-6'">
                {{ title }}
            </h1>
            <p
                v-if="summary"
                class="mt-4 max-w-2xl text-lg text-river-100 sm:text-xl"
            >
                {{ summary }}
            </p>
            <div v-if="$slots.default" class="mt-6 flex flex-wrap gap-3">
                <slot />
            </div>
        </div>
        <WaveDivider class="-mb-px text-page" />
    </header>
</template>
