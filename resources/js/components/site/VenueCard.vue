<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import type { VenueSummary } from '@/types';

defineProps<{ venue: VenueSummary }>();
</script>

<template>
    <article class="card flex flex-col p-5">
        <h3 class="text-lg leading-snug font-bold">
            <Link :href="venue.url" class="hover:text-link">{{
                venue.name
            }}</Link>
        </h3>
        <p v-if="venue.summary" class="mt-1 text-soft">{{ venue.summary }}</p>
        <a
            :href="venue.map_url"
            target="_blank"
            rel="noopener"
            class="link mt-auto inline-flex min-h-11 items-center gap-1.5 pt-3 text-sm"
        >
            <MapPin class="size-4 flex-none" aria-hidden="true" />
            <span
                >{{ venue.address || 'Open in Maps'
                }}<span class="sr-only"> (opens a map in a new tab)</span></span
            >
        </a>
    </article>
</template>
