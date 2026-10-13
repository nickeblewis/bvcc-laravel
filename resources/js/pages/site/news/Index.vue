<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Rss } from '@lucide/vue';
import ArticleCard from '@/components/site/ArticleCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import type { ArticleSummary } from '@/types';

defineProps<{
    articles: ArticleSummary[];
    pagination: {
        current: number;
        last: number;
        prev: string | null;
        next: string | null;
    };
    categories: { name: string; slug: string }[];
    current: string | null;
}>();
</script>

<template>
    <PageHero
        title="Club news"
        summary="Session updates, trip reports and what is coming up."
    >
        <a href="/news/feed.xml" class="btn btn-outline">
            <Rss class="size-5" aria-hidden="true" />
            RSS feed
        </a>
    </PageHero>

    <section class="shell py-10 lg:py-14" aria-label="News">
        <nav
            v-if="categories.length"
            aria-label="Filter by category"
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0"
        >
            <Link
                href="/news"
                class="flex min-h-11 flex-none items-center rounded-full border-2 px-4 font-semibold whitespace-nowrap"
                :class="
                    current === null
                        ? 'border-ink-800 bg-ink-800 text-white dark:border-river-200 dark:bg-river-200 dark:text-ink-950'
                        : 'border-line bg-panel hover:border-current'
                "
                :aria-current="current === null ? 'page' : undefined"
                >All news</Link
            >
            <Link
                v-for="category in categories"
                :key="category.slug"
                :href="`/news?category=${category.slug}`"
                class="flex min-h-11 flex-none items-center rounded-full border-2 px-4 font-semibold whitespace-nowrap"
                :class="
                    current === category.slug
                        ? 'border-ink-800 bg-ink-800 text-white dark:border-river-200 dark:bg-river-200 dark:text-ink-950'
                        : 'border-line bg-panel hover:border-current'
                "
                :aria-current="current === category.slug ? 'page' : undefined"
                >{{ category.name }}</Link
            >
        </nav>

        <p v-if="!articles.length" class="card mt-8 p-6 text-lg">
            No news here yet.
        </p>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <ArticleCard
                v-for="article in articles"
                :key="article.url"
                :article="article"
            />
        </div>

        <nav
            v-if="pagination.last > 1"
            aria-label="Pages"
            class="mt-10 flex items-center justify-between gap-4"
        >
            <Link
                v-if="pagination.prev"
                :href="pagination.prev"
                class="btn btn-outline"
                rel="prev"
                >Newer</Link
            >
            <span v-else></span>
            <span class="text-soft"
                >Page {{ pagination.current }} of {{ pagination.last }}</span
            >
            <Link
                v-if="pagination.next"
                :href="pagination.next"
                class="btn btn-outline"
                rel="next"
                >Older</Link
            >
            <span v-else></span>
        </nav>
    </section>
</template>
