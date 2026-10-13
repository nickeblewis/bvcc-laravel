<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DisciplineIcon from '@/components/site/DisciplineIcon.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import type { DisciplineSummary } from '@/types';

defineProps<{ discipline: DisciplineSummary }>();
</script>

<template>
    <Link
        :href="discipline.url"
        class="group card flex flex-col overflow-hidden transition-shadow hover:shadow-md"
    >
        <SitePicture
            v-if="discipline.image"
            :image="discipline.image"
            sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
            class="aspect-[16/9] w-full"
        />
        <span class="flex flex-1 gap-4 p-5">
            <span
                class="flex size-12 flex-none items-center justify-center rounded-full bg-river-100 text-river-800 dark:bg-ink-700 dark:text-river-200"
            >
                <DisciplineIcon :slug="discipline.slug" class="size-6" />
            </span>
            <span>
                <span
                    class="block font-display text-xl leading-tight font-bold group-hover:text-link"
                    >{{ discipline.name }}</span
                >
                <span class="mt-1 block text-soft">{{
                    discipline.summary
                }}</span>
            </span>
        </span>
    </Link>
</template>
