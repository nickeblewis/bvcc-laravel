<script setup lang="ts">
import { MapPin } from '@lucide/vue';
import { computed } from 'vue';
import CtaBand from '@/components/site/CtaBand.vue';
import EventCard from '@/components/site/EventCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import type { EventSummary, VenueSummary } from '@/types';

const props = defineProps<{
    venue: VenueSummary & { html: string; access_notes: string | null };
    events: EventSummary[];
}>();

// An OpenStreetMap embed, once the club has added the venue's map pin.
const mapSrc = computed(() => {
    const { latitude: lat, longitude: lng } = props.venue;

    if (lat === null || lng === null) {
        return null;
    }

    const box = [lng - 0.01, lat - 0.006, lng + 0.01, lat + 0.006].join(',');

    return `https://www.openstreetmap.org/export/embed.html?bbox=${box}&layer=mapnik&marker=${lat},${lng}`;
});
</script>

<template>
    <PageHero
        :title="venue.name"
        :summary="venue.summary"
        eyebrow="Where we paddle"
    >
        <a
            :href="venue.map_url"
            target="_blank"
            rel="noopener"
            class="btn btn-gold"
        >
            <MapPin class="size-5" aria-hidden="true" />
            Directions<span class="sr-only"> (opens a map in a new tab)</span>
        </a>
    </PageHero>

    <div class="shell grid gap-10 py-10 lg:grid-cols-[1fr_22rem] lg:py-14">
        <article class="min-w-0">
            <SitePicture
                v-if="venue.image"
                :image="venue.image"
                eager
                sizes="(min-width: 1024px) 60vw, 100vw"
                class="mb-8 aspect-[16/9] w-full rounded-2xl"
            />
            <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
            <div class="prose-site" v-html="venue.html"></div>

            <section v-if="venue.access_notes" class="mt-8">
                <h2 class="text-heading">Parking and access</h2>
                <p class="mt-3 max-w-prose whitespace-pre-line">
                    {{ venue.access_notes }}
                </p>
            </section>

            <section v-if="events.length" class="mt-12" aria-labelledby="here">
                <h2 id="here" class="text-heading mb-5">Coming up here</h2>
                <div class="grid gap-4">
                    <EventCard
                        v-for="event in events"
                        :key="event.url"
                        :event="event"
                    />
                </div>
            </section>
        </article>

        <aside aria-label="Address and map">
            <div class="card overflow-hidden">
                <iframe
                    v-if="mapSrc"
                    :src="mapSrc"
                    :title="`Map showing ${venue.name}`"
                    loading="lazy"
                    class="aspect-[4/3] w-full border-0"
                ></iframe>
                <div class="p-5">
                    <h2
                        class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                    >
                        Address
                    </h2>
                    <p class="mt-2 font-semibold">{{ venue.name }}</p>
                    <p v-if="venue.address" class="text-soft">
                        {{ venue.address }}
                    </p>
                </div>
            </div>
        </aside>
    </div>

    <CtaBand />
</template>
