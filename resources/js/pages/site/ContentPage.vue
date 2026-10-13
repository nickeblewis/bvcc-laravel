<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CtaBand from '@/components/site/CtaBand.vue';
import FaqList from '@/components/site/FaqList.vue';
import PageHero from '@/components/site/PageHero.vue';
import QuickFacts from '@/components/site/QuickFacts.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import type { ContentPage, FaqItem, PageLink } from '@/types';

/** The template for every editable page under Paddle, Join and Club. */
defineProps<{
    page: ContentPage;
    faqs: FaqItem[];
    section: { label: string; url: string };
    siblings: PageLink[];
}>();
</script>

<template>
    <PageHero
        :title="page.title"
        :summary="page.summary"
        :eyebrow="section.label"
    />

    <div class="shell grid gap-10 py-10 lg:grid-cols-[1fr_18rem] lg:py-14">
        <article class="min-w-0">
            <QuickFacts :facts="page.facts" class="mb-8" />
            <SitePicture
                v-if="page.image"
                :image="page.image"
                eager
                sizes="(min-width: 1024px) 60vw, 100vw"
                class="mb-8 aspect-[16/9] w-full rounded-2xl"
            />
            <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
            <div v-if="page.html" class="prose-site" v-html="page.html"></div>
            <p v-else class="text-soft">
                This page is being moved over from our old website.
            </p>

            <section v-if="faqs.length" class="mt-12" aria-labelledby="faqs">
                <h2 id="faqs" class="text-heading mb-5">Common questions</h2>
                <FaqList :faqs="faqs" />
            </section>

            <p v-if="page.reviewed" class="mt-10 text-sm text-soft">
                Last reviewed {{ page.reviewed }}.
            </p>
        </article>

        <aside v-if="siblings.length" :aria-label="`More in ${section.label}`">
            <div class="card p-5 lg:sticky lg:top-6">
                <h2
                    class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                >
                    More in {{ section.label }}
                </h2>
                <ul class="mt-2 divide-y divide-line">
                    <li v-for="link in siblings" :key="link.url">
                        <Link
                            :href="link.url"
                            class="flex min-h-11 items-center py-2 leading-snug font-medium hover:text-link"
                            >{{ link.title }}</Link
                        >
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    <CtaBand />
</template>
