<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CtaBand from '@/components/site/CtaBand.vue';
import FaqList from '@/components/site/FaqList.vue';
import PageHero from '@/components/site/PageHero.vue';
import type { FaqItem } from '@/types';

defineProps<{ groups: { label: string; slug: string; faqs: FaqItem[] }[] }>();
</script>

<template>
    <PageHero
        title="Frequently asked questions"
        summary="Short answers to the things people ask us most."
    />

    <div class="shell py-10 lg:py-14">
        <nav
            aria-label="Jump to a topic"
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0"
        >
            <a
                v-for="group in groups"
                :key="group.slug"
                :href="`#${group.slug}`"
                class="flex min-h-11 flex-none items-center rounded-full border-2 border-line bg-panel px-4 font-semibold whitespace-nowrap hover:border-current"
                >{{ group.label }}</a
            >
        </nav>

        <section
            v-for="group in groups"
            :id="group.slug"
            :key="group.slug"
            class="mt-10 scroll-mt-6"
            :aria-labelledby="`${group.slug}-heading`"
        >
            <h2 :id="`${group.slug}-heading`" class="text-heading mb-5">
                {{ group.label }}
            </h2>
            <FaqList :faqs="group.faqs" class="max-w-3xl" />
        </section>

        <p class="mt-10 text-lg">
            Still not sure? <Link href="/contact" class="link">Ask us</Link>.
        </p>
    </div>

    <CtaBand />
</template>
