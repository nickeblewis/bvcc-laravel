<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import WaveDivider from '@/components/site/WaveDivider.vue';

/** The closing call to action: every page ends by inviting people along. */
withDefaults(
    defineProps<{
        title?: string;
        text?: string;
    }>(),
    {
        title: 'Come and try it',
        text: 'Turn up to any weekly session. We will lend you a boat, a paddle and a buoyancy aid, and a volunteer coach will get you started.',
    },
);
</script>

<template>
    <section class="relative isolate mt-12 bg-river-700 text-white sm:mt-16">
        <WaveDivider flip class="-mt-px text-page" />
        <div class="contours absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="shell py-10 text-center sm:py-14">
            <h2 class="text-heading">{{ title }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-lg text-river-50">
                {{ text }}
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <Link href="/paddle/beginners" class="btn btn-gold"
                    >How to get started</Link
                >
                <Link href="/events" class="btn btn-outline"
                    >See what is on</Link
                >
            </div>
        </div>
    </section>
</template>
