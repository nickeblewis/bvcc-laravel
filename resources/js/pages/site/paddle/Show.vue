<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, PlayCircle } from '@lucide/vue';
import CtaBand from '@/components/site/CtaBand.vue';
import DisciplineIcon from '@/components/site/DisciplineIcon.vue';
import EventCard from '@/components/site/EventCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import QuickFacts from '@/components/site/QuickFacts.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import type { DisciplineSummary, EventSummary, Fact } from '@/types';

defineProps<{
    discipline: DisciplineSummary & {
        facts: Fact[];
        html: string;
        video_url: string | null;
        contact_email: string | null;
    };
    events: EventSummary[];
    others: DisciplineSummary[];
}>();
</script>

<template>
    <PageHero
        :title="discipline.name"
        :summary="discipline.summary"
        eyebrow="Paddle with us"
    />

    <div class="shell grid gap-10 py-10 lg:grid-cols-[1fr_20rem] lg:py-14">
        <article class="min-w-0">
            <QuickFacts :facts="discipline.facts" class="mb-8" />
            <SitePicture
                v-if="discipline.image"
                :image="discipline.image"
                eager
                sizes="(min-width: 1024px) 60vw, 100vw"
                class="mb-8 aspect-[16/9] w-full rounded-2xl"
            />
            <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
            <div class="prose-site" v-html="discipline.html"></div>

            <div class="mt-8 flex flex-wrap gap-3">
                <a
                    v-if="discipline.video_url"
                    :href="discipline.video_url"
                    rel="noopener"
                    class="btn btn-ink"
                >
                    <PlayCircle class="size-5" aria-hidden="true" />
                    Watch the video
                </a>
                <a
                    v-if="discipline.contact_email"
                    :href="`mailto:${discipline.contact_email}`"
                    class="btn btn-outline"
                >
                    <Mail class="size-5" aria-hidden="true" />
                    {{ discipline.contact_email }}
                </a>
            </div>

            <section v-if="events.length" class="mt-12" aria-labelledby="next">
                <h2 id="next" class="text-heading mb-5">
                    Coming up: {{ discipline.name.toLowerCase() }}
                </h2>
                <div class="grid gap-4">
                    <EventCard
                        v-for="event in events"
                        :key="event.url"
                        :event="event"
                    />
                </div>
            </section>
        </article>

        <aside aria-label="Other kinds of paddling">
            <div class="card p-5 lg:sticky lg:top-6">
                <h2
                    class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                >
                    Also at the club
                </h2>
                <ul class="mt-2 divide-y divide-line">
                    <li v-for="other in others" :key="other.slug">
                        <Link
                            :href="other.url"
                            class="flex min-h-11 items-center gap-3 py-2 font-medium hover:text-link"
                        >
                            <DisciplineIcon
                                :slug="other.slug"
                                class="size-5 flex-none text-link"
                            />
                            {{ other.name }}
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    <CtaBand />
</template>
