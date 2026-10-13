<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CtaBand from '@/components/site/CtaBand.vue';
import DisciplineCard from '@/components/site/DisciplineCard.vue';
import EventCard from '@/components/site/EventCard.vue';
import LinkCard from '@/components/site/LinkCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import VenueCard from '@/components/site/VenueCard.vue';
import type {
    DisciplineSummary,
    EventSummary,
    PageLink,
    VenueSummary,
} from '@/types';

defineProps<{
    nextSession: EventSummary | null;
    pages: PageLink[];
    disciplines: DisciplineSummary[];
    venues: VenueSummary[];
}>();
</script>

<template>
    <PageHero
        title="Paddle with us"
        summary="We paddle every Wednesday evening, all year round, and head off on trips most months. Come as a complete beginner or bring years of experience."
    >
        <Link href="/paddle/beginners" class="btn btn-gold"
            >I am new to this</Link
        >
        <Link href="/events" class="btn btn-outline">See the calendar</Link>
    </PageHero>

    <section class="shell py-10 lg:py-14" aria-labelledby="start">
        <h2 id="start" class="text-heading">Start here</h2>
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <LinkCard
                v-for="link in pages"
                :key="link.url"
                :title="link.title"
                :summary="link.summary"
                :href="link.url"
            />
        </div>

        <div v-if="nextSession" class="mt-10">
            <h2 class="mb-4 text-xl font-bold">The next session</h2>
            <div class="max-w-xl">
                <EventCard :event="nextSession" />
            </div>
        </div>
    </section>

    <section class="bg-mist dark:bg-ink-900" aria-labelledby="disciplines">
        <div class="section shell">
            <h2 id="disciplines" class="text-heading">
                What kind of paddling do you fancy?
            </h2>
            <p class="mt-3 max-w-2xl text-lg text-soft">
                Most members try a bit of everything. Each has its own group
                within the club who will be glad to show you the ropes.
            </p>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <DisciplineCard
                    v-for="discipline in disciplines"
                    :key="discipline.slug"
                    :discipline="discipline"
                />
            </div>
        </div>
    </section>

    <section class="section shell" aria-labelledby="venues">
        <h2 id="venues" class="text-heading">Where we paddle</h2>
        <p class="mt-3 max-w-2xl text-lg text-soft">
            Aldershot Lido is home. In winter we move indoors to a warm pool,
            and in spring and autumn we paddle the Basingstoke Canal.
        </p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <VenueCard
                v-for="venue in venues"
                :key="venue.url"
                :venue="venue"
            />
        </div>
    </section>

    <CtaBand />
</template>
