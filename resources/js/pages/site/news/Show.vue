<script setup lang="ts">
import ArticleCard from '@/components/site/ArticleCard.vue';
import CtaBand from '@/components/site/CtaBand.vue';
import PageHero from '@/components/site/PageHero.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import type { ArticleSummary } from '@/types';

defineProps<{
    article: ArticleSummary & { html: string; author: string | null };
    more: ArticleSummary[];
}>();
</script>

<template>
    <PageHero :title="article.title" :eyebrow="article.category ?? 'Club news'">
        <p class="text-river-100">
            <time v-if="article.datetime" :datetime="article.datetime">{{
                article.date
            }}</time>
            <template v-if="article.author"> · {{ article.author }}</template>
        </p>
    </PageHero>

    <article class="shell py-10 lg:py-14">
        <SitePicture
            v-if="article.image"
            :image="article.image"
            eager
            sizes="(min-width: 1024px) 60vw, 100vw"
            class="mb-8 aspect-[16/9] w-full max-w-3xl rounded-2xl"
        />
        <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
        <div class="prose-site" v-html="article.html"></div>
    </article>

    <section
        v-if="more.length"
        class="bg-mist dark:bg-ink-900"
        aria-labelledby="more"
    >
        <div class="section shell">
            <h2 id="more" class="text-heading">More news</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard
                    v-for="item in more"
                    :key="item.url"
                    :article="item"
                />
            </div>
        </div>
    </section>

    <CtaBand />
</template>
