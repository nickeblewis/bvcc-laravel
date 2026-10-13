<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarPlus, Mail, MapPin, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import EventCard from '@/components/site/EventCard.vue';
import GradeBadge from '@/components/site/GradeBadge.vue';
import PageHero from '@/components/site/PageHero.vue';
import QuickFacts from '@/components/site/QuickFacts.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import type { EventSummary, Fact, VenueSummary } from '@/types';

const props = defineProps<{
    event: EventSummary & {
        html: string;
        prices: { band: string; price: string }[];
        extra_costs: string | null;
        cancel_reason: string | null;
        booking_open: boolean;
        booking_closes: string | null;
        is_past: boolean;
        book_url: string;
        ical_url: string;
        venue_detail: VenueSummary | null;
    };
    related: EventSummary[];
}>();

// The first things anyone asks about an event, answered before the detail.
const facts = computed<Fact[]>(() =>
    [
        {
            label: 'When',
            value: `${props.event.date_label}, ${props.event.time_label.toLowerCase()}`,
        },
        props.event.venue
            ? { label: 'Where', value: props.event.venue.name }
            : null,
        props.event.price_label
            ? { label: 'Club fee', value: props.event.price_label }
            : null,
        props.event.suitability
            ? { label: 'Suitable for', value: props.event.suitability }
            : null,
    ].filter((fact): fact is Fact => fact !== null),
);
</script>

<template>
    <PageHero
        :title="event.title"
        :summary="event.summary"
        :eyebrow="
            event.discipline
                ? `${event.category.name} · ${event.discipline}`
                : event.category.name
        "
    >
        <GradeBadge v-if="event.grade" :grade="event.grade" />
    </PageHero>

    <div class="shell grid gap-10 py-10 lg:grid-cols-[1fr_22rem] lg:py-14">
        <article class="min-w-0">
            <div
                v-if="event.cancelled"
                role="alert"
                class="mb-8 flex gap-3 rounded-2xl border-2 border-red-700 bg-red-50 p-5 text-red-950"
            >
                <TriangleAlert
                    class="mt-0.5 size-6 flex-none"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-lg font-bold">
                        This event has been cancelled.
                    </p>
                    <p v-if="event.cancel_reason" class="mt-1">
                        {{ event.cancel_reason }}
                    </p>
                </div>
            </div>

            <QuickFacts :facts="facts" class="mb-8" />

            <SitePicture
                v-if="event.image"
                :image="event.image"
                eager
                sizes="(min-width: 1024px) 60vw, 100vw"
                class="mb-8 aspect-[16/9] w-full rounded-2xl"
            />

            <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
            <div class="prose-site" v-html="event.html"></div>

            <section
                v-if="event.prices.length"
                class="mt-10"
                aria-labelledby="cost"
            >
                <h2 id="cost" class="text-heading">What does it cost?</h2>
                <dl class="card mt-4 max-w-md divide-y divide-line">
                    <div
                        v-for="row in event.prices"
                        :key="row.band"
                        class="flex items-center justify-between gap-4 px-5 py-3"
                    >
                        <dt>{{ row.band }}</dt>
                        <dd class="font-display text-xl font-bold">
                            {{ row.price }}
                        </dd>
                    </div>
                </dl>
                <p v-if="event.extra_costs" class="mt-3 text-soft">
                    {{ event.extra_costs }}
                </p>
                <p class="mt-3 text-soft">
                    Not sure which applies to you?
                    <Link href="/join" class="link"
                        >See membership and fees</Link
                    >.
                </p>
            </section>
        </article>

        <aside class="space-y-4" aria-label="Booking and location">
            <div v-if="!event.cancelled" class="card p-5 lg:sticky lg:top-6">
                <h2 class="font-display text-2xl font-bold">
                    {{
                        event.is_past
                            ? 'This event has been and gone'
                            : event.booking_open
                              ? 'Book a place'
                              : 'Booking has closed'
                    }}
                </h2>
                <template v-if="event.booking_open">
                    <p v-if="event.is_full" class="mt-2 font-semibold">
                        This event is full. Email us to join the waiting list.
                    </p>
                    <p
                        v-else-if="event.places_left !== null"
                        class="mt-2 text-soft"
                    >
                        {{ event.places_left }}
                        {{ event.places_left === 1 ? 'place' : 'places' }} left.
                    </p>
                    <p class="mt-2 text-soft">
                        Email the bookings team with the names of everyone
                        coming. We will hold your places and send payment
                        details.
                    </p>
                    <p
                        v-if="event.booking_closes"
                        class="mt-2 text-sm font-semibold"
                    >
                        Book by {{ event.booking_closes }}.
                    </p>
                    <a :href="event.book_url" class="btn btn-gold mt-4 w-full">
                        <Mail class="size-5" aria-hidden="true" />
                        {{
                            event.is_full
                                ? 'Join the waiting list'
                                : 'Email to book'
                        }}
                    </a>
                    <a
                        :href="event.ical_url"
                        class="btn btn-outline mt-2 w-full"
                    >
                        <CalendarPlus class="size-5" aria-hidden="true" />
                        Add to my calendar
                    </a>
                </template>
                <Link v-else href="/events" class="btn btn-ink mt-4 w-full"
                    >See what is coming up</Link
                >
            </div>

            <div v-if="event.venue_detail" class="card p-5">
                <h2
                    class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                >
                    Where
                </h2>
                <p class="mt-2 text-lg font-bold">
                    <Link
                        :href="event.venue_detail.url"
                        class="hover:text-link"
                        >{{ event.venue_detail.name }}</Link
                    >
                </p>
                <p v-if="event.venue_detail.address" class="text-soft">
                    {{ event.venue_detail.address }}
                </p>
                <a
                    :href="event.venue_detail.map_url"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-outline mt-3 w-full"
                >
                    <MapPin class="size-5" aria-hidden="true" />
                    Directions<span class="sr-only">
                        (opens a map in a new tab)</span
                    >
                </a>
            </div>
        </aside>
    </div>

    <section
        v-if="related.length"
        class="bg-mist dark:bg-ink-900"
        aria-labelledby="related"
    >
        <div class="section shell">
            <h2 id="related" class="text-heading">
                More {{ event.category.name.toLowerCase() }}
            </h2>
            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                <EventCard
                    v-for="item in related"
                    :key="item.url"
                    :event="item"
                />
            </div>
        </div>
    </section>
</template>
