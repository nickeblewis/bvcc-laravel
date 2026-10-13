<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SitePicture from '@/components/site/SitePicture.vue';
import type { ArticleSummary } from '@/types';

defineProps<{ article: ArticleSummary }>();
</script>

<template>
    <article
        class="group card relative flex flex-col overflow-hidden transition-shadow hover:shadow-md"
    >
        <SitePicture
            v-if="article.image"
            :image="article.image"
            sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
            class="aspect-[16/9] w-full"
        />
        <div class="flex flex-1 flex-col p-5">
            <p class="text-sm font-semibold text-soft">
                <time v-if="article.datetime" :datetime="article.datetime">{{
                    article.date
                }}</time>
                <template v-if="article.category">
                    · {{ article.category }}</template
                >
            </p>
            <h3 class="mt-1 text-xl leading-snug font-bold">
                <Link
                    :href="article.url"
                    class="group-hover:text-link after:absolute after:inset-0"
                    >{{ article.title }}</Link
                >
            </h3>
            <p class="mt-2 text-soft">{{ article.excerpt }}</p>
        </div>
    </article>
</template>
