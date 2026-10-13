<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, MapPin } from '@lucide/vue';
import GradeBadge from '@/components/site/GradeBadge.vue';
import { categoryColour } from '@/lib/site';
import type { EventSummary } from '@/types';

defineProps<{ event: EventSummary; headingLevel?: 'h2' | 'h3' }>();
</script>

<template>
    <article
        class="group card relative flex gap-4 p-4 transition-shadow hover:shadow-md sm:p-5"
        :class="{ 'opacity-75': event.cancelled }"
    >
        <!-- The date, as a tide-table style block in the category's colour. -->
        <div
            class="flex w-16 flex-none flex-col items-center justify-center self-start rounded-xl py-2 text-white"
            :style="{ backgroundColor: categoryColour(event.category.colour) }"
        >
            <span class="text-xs font-semibold tracking-wide uppercase">{{
                event.weekday
            }}</span>
            <span class="font-display text-2xl leading-none font-extrabold">{{
                event.day
            }}</span>
            <span class="text-xs font-semibold uppercase">{{
                event.month
            }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-soft">
                {{ event.category.name
                }}<template v-if="event.discipline">
                    · {{ event.discipline }}</template
                >
            </p>
            <component
                :is="headingLevel ?? 'h3'"
                class="mt-0.5 text-lg leading-snug font-bold"
            >
                <Link
                    :href="event.url"
                    class="group-hover:text-link after:absolute after:inset-0"
                >
                    <span :class="{ 'line-through': event.cancelled }">{{
                        event.title
                    }}</span>
                </Link>
            </component>
            <ul
                class="mt-2 flex flex-col gap-x-4 gap-y-1 text-sm text-soft sm:flex-row sm:flex-wrap"
            >
                <li class="flex items-center gap-1.5">
                    <Clock class="size-4 flex-none" aria-hidden="true" />
                    {{ event.time_label }}
                </li>
                <li v-if="event.venue" class="flex items-center gap-1.5">
                    <MapPin class="size-4 flex-none" aria-hidden="true" />
                    {{ event.venue.name }}
                </li>
            </ul>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <span
                    v-if="event.cancelled"
                    class="rounded-full bg-red-100 px-2.5 py-1 font-semibold text-red-900"
                    >Cancelled</span
                >
                <template v-else>
                    <GradeBadge v-if="event.grade" :grade="event.grade" />
                    <span
                        v-if="event.price_label"
                        class="rounded-full bg-mist px-2.5 py-1 font-semibold text-ink-900"
                        >{{ event.price_label }}</span
                    >
                    <span
                        v-if="event.is_full"
                        class="rounded-full bg-ink-800 px-2.5 py-1 font-semibold text-white"
                        >Full: waiting list</span
                    >
                    <span
                        v-else-if="
                            event.places_left !== null && event.places_left <= 3
                        "
                        class="rounded-full bg-gold-300 px-2.5 py-1 font-semibold text-ink-950"
                        >{{ event.places_left }}
                        {{ event.places_left === 1 ? 'place' : 'places' }}
                        left</span
                    >
                </template>
            </div>
        </div>
    </article>
</template>
