<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CalendarPlus,
    ChevronLeft,
    ChevronRight,
    List,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CtaBand from '@/components/site/CtaBand.vue';
import EventCard from '@/components/site/EventCard.vue';
import FaqList from '@/components/site/FaqList.vue';
import PageHero from '@/components/site/PageHero.vue';
import { categoryColour } from '@/lib/site';
import type { EventSummary, FaqItem } from '@/types';

const props = defineProps<{
    events: EventSummary[];
    categories: { name: string; slug: string; colour: string }[];
    faqs: FaqItem[];
}>();

const category = ref<string | null>(null);
const view = ref<'list' | 'calendar'>('list');

const filtered = computed(() =>
    category.value
        ? props.events.filter((e) => e.category.slug === category.value)
        : props.events,
);

// The list: events grouped under month headings.
const months = computed(() => {
    const groups = new Map<string, { label: string; events: EventSummary[] }>();

    for (const event of filtered.value) {
        if (!groups.has(event.month_key)) {
            groups.set(event.month_key, {
                label: event.month_label,
                events: [],
            });
        }

        groups.get(event.month_key)?.events.push(event);
    }

    return [...groups.entries()].map(([key, group]) => ({ key, ...group }));
});

// The calendar: one month at a time, stepping through months that have events.
const monthKeys = computed(() => [
    ...new Set(props.events.map((e) => e.month_key)),
]);
const monthIndex = ref(0);
const currentKey = computed(() => monthKeys.value[monthIndex.value] ?? null);
const currentLabel = computed(
    () =>
        props.events.find((e) => e.month_key === currentKey.value)
            ?.month_label ?? '',
);

// Worked out from the "YYYY-MM" key with plain arithmetic in UTC, so the grid
// is the same on the server and in any visitor's time zone.
const weeks = computed(() => {
    if (!currentKey.value) {
        return [];
    }

    const [year, month] = currentKey.value.split('-').map(Number);
    const daysInMonth = new Date(Date.UTC(year, month, 0)).getUTCDate();
    const offset = (new Date(Date.UTC(year, month - 1, 1)).getUTCDay() + 6) % 7;
    const cells: ({ day: number; key: string } | null)[] = Array.from(
        { length: offset },
        () => null,
    );

    for (let day = 1; day <= daysInMonth; day++) {
        cells.push({
            day,
            key: `${currentKey.value}-${String(day).padStart(2, '0')}`,
        });
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return Array.from({ length: cells.length / 7 }, (_, i) =>
        cells.slice(i * 7, i * 7 + 7),
    );
});

const eventsOn = (key: string) =>
    filtered.value.filter((e) => e.date_key === key);
</script>

<template>
    <PageHero
        title="Events and trips"
        summary="Weekly sessions, river trips, courses, polo and socials. Pick something and come along."
    >
        <a href="/events/calendar.ics" class="btn btn-outline">
            <CalendarPlus class="size-5" aria-hidden="true" />
            Add to my calendar
        </a>
    </PageHero>

    <section class="shell py-8 lg:py-12" aria-label="Upcoming events">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Filter chips scroll sideways on a phone rather than wrapping into a wall. -->
            <div
                class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0"
                role="group"
                aria-label="Filter by type"
            >
                <button
                    type="button"
                    class="min-h-11 flex-none rounded-full border-2 px-4 font-semibold whitespace-nowrap transition-colors"
                    :class="
                        category === null
                            ? 'border-ink-800 bg-ink-800 text-white dark:border-river-200 dark:bg-river-200 dark:text-ink-950'
                            : 'border-line bg-panel hover:border-current'
                    "
                    :aria-pressed="category === null"
                    @click="category = null"
                >
                    Everything
                </button>
                <button
                    v-for="item in categories"
                    :key="item.slug"
                    type="button"
                    class="flex min-h-11 flex-none items-center gap-2 rounded-full border-2 px-4 font-semibold whitespace-nowrap transition-colors"
                    :class="
                        category === item.slug
                            ? 'border-ink-800 bg-ink-800 text-white dark:border-river-200 dark:bg-river-200 dark:text-ink-950'
                            : 'border-line bg-panel hover:border-current'
                    "
                    :aria-pressed="category === item.slug"
                    @click="category = item.slug"
                >
                    <span
                        class="size-3 rounded-full"
                        :style="{
                            backgroundColor: categoryColour(item.colour),
                        }"
                        aria-hidden="true"
                    ></span>
                    {{ item.name }}
                </button>
            </div>

            <!-- The month grid needs room, so the switch only appears on wider screens. -->
            <div
                class="hidden rounded-full border-2 border-line bg-panel p-1 md:flex"
                role="group"
                aria-label="View"
            >
                <button
                    type="button"
                    class="flex min-h-9 items-center gap-2 rounded-full px-4 font-semibold"
                    :class="{
                        'bg-ink-800 text-white dark:bg-river-200 dark:text-ink-950':
                            view === 'list',
                    }"
                    :aria-pressed="view === 'list'"
                    @click="view = 'list'"
                >
                    <List class="size-4" aria-hidden="true" /> List
                </button>
                <button
                    type="button"
                    class="flex min-h-9 items-center gap-2 rounded-full px-4 font-semibold"
                    :class="{
                        'bg-ink-800 text-white dark:bg-river-200 dark:text-ink-950':
                            view === 'calendar',
                    }"
                    :aria-pressed="view === 'calendar'"
                    @click="view = 'calendar'"
                >
                    <CalendarDays class="size-4" aria-hidden="true" /> Month
                </button>
            </div>
        </div>

        <p v-if="!filtered.length" class="card mt-8 p-6 text-lg">
            Nothing in the calendar just now. New sessions and trips are added
            through the year, so check back soon or
            <Link href="/contact" class="link">ask us what is planned</Link>.
        </p>

        <!-- List: always used on phones. -->
        <div :class="{ 'md:hidden': view === 'calendar' }">
            <section
                v-for="month in months"
                :key="month.key"
                class="mt-8"
                :aria-labelledby="`month-${month.key}`"
            >
                <h2
                    :id="`month-${month.key}`"
                    class="text-heading sticky top-0 z-10 -mx-4 bg-page/95 px-4 py-2 backdrop-blur sm:mx-0 sm:px-0"
                >
                    {{ month.label }}
                </h2>
                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                    <EventCard
                        v-for="event in month.events"
                        :key="event.url"
                        :event="event"
                    />
                </div>
            </section>
        </div>

        <!-- Month grid: wider screens only. -->
        <div
            v-if="view === 'calendar' && currentKey"
            class="mt-8 hidden md:block"
        >
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-heading">{{ currentLabel }}</h2>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="flex size-11 items-center justify-center rounded-full border-2 border-line bg-panel disabled:opacity-40"
                        :disabled="monthIndex === 0"
                        @click="monthIndex--"
                    >
                        <ChevronLeft class="size-5" aria-hidden="true" />
                        <span class="sr-only">Previous month</span>
                    </button>
                    <button
                        type="button"
                        class="flex size-11 items-center justify-center rounded-full border-2 border-line bg-panel disabled:opacity-40"
                        :disabled="monthIndex >= monthKeys.length - 1"
                        @click="monthIndex++"
                    >
                        <ChevronRight class="size-5" aria-hidden="true" />
                        <span class="sr-only">Next month</span>
                    </button>
                </div>
            </div>
            <table
                class="card mt-4 w-full table-fixed border-separate border-spacing-0 overflow-hidden"
            >
                <thead>
                    <tr>
                        <th
                            v-for="name in [
                                'Mon',
                                'Tue',
                                'Wed',
                                'Thu',
                                'Fri',
                                'Sat',
                                'Sun',
                            ]"
                            :key="name"
                            scope="col"
                            class="border-b border-line bg-mist p-2 text-sm font-semibold dark:bg-ink-700"
                        >
                            {{ name }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(week, w) in weeks" :key="w">
                        <td
                            v-for="(cell, d) in week"
                            :key="d"
                            class="h-28 border-b border-l border-line p-1.5 align-top first:border-l-0"
                            :class="{ 'bg-mist/50 dark:bg-ink-900/40': !cell }"
                        >
                            <template v-if="cell">
                                <span
                                    class="block text-right text-sm font-semibold text-soft"
                                    >{{ cell.day }}</span
                                >
                                <Link
                                    v-for="event in eventsOn(cell.key)"
                                    :key="event.url"
                                    :href="event.url"
                                    class="mt-1 block rounded-md px-2 py-1 text-sm leading-tight font-semibold text-white hover:opacity-90"
                                    :class="{
                                        'line-through opacity-70':
                                            event.cancelled,
                                    }"
                                    :style="{
                                        backgroundColor: categoryColour(
                                            event.category.colour,
                                        ),
                                    }"
                                >
                                    {{ event.title }}
                                </Link>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-mist dark:bg-ink-900" aria-labelledby="booking">
        <div class="section shell grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <h2 id="booking" class="text-heading">How booking works</h2>
                <ol class="mt-5 space-y-4">
                    <li
                        v-for="(step, i) in [
                            'Open the event and use the booking button to tell us who is coming.',
                            'We hold your places and send you the payment details.',
                            'Your booking is confirmed once you have paid. For most weekly sessions you can also pay on arrival.',
                        ]"
                        :key="i"
                        class="flex gap-4"
                    >
                        <span
                            class="flex size-9 flex-none items-center justify-center rounded-full bg-gold-400 font-display font-extrabold text-ink-950"
                            >{{ i + 1 }}</span
                        >
                        <span class="pt-1">{{ step }}</span>
                    </li>
                </ol>
                <p class="mt-5">
                    <Link href="/club/booking-terms" class="link"
                        >Event booking terms</Link
                    >
                </p>
            </div>
            <FaqList v-if="faqs.length" :faqs="faqs" />
        </div>
    </section>

    <CtaBand />
</template>
