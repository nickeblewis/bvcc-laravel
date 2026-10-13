<script setup lang="ts">
import CtaBand from '@/components/site/CtaBand.vue';
import PageHero from '@/components/site/PageHero.vue';
import VenueCard from '@/components/site/VenueCard.vue';
import type { VenueSummary } from '@/types';

defineProps<{ venues: VenueSummary[] }>();
</script>

<template>
    <PageHero
        title="Where we paddle"
        summary="A clean outdoor pool in summer, a warm indoor pool in winter, a quiet canal in between, and a lake for polo."
        eyebrow="Paddle with us"
    />

    <section class="shell py-10 lg:py-14" aria-label="Venues">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <VenueCard
                v-for="venue in venues"
                :key="venue.url"
                :venue="venue"
            />
        </div>
        <p class="mt-8 max-w-prose text-soft">
            The venue changes with the season and trips go further afield, so
            always check the event for where to meet.
        </p>
    </section>

    <CtaBand />
</template>
