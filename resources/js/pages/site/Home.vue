<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Clock,
    HeartHandshake,
    Kayak,
    MapPin,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import ArticleCard from '@/components/site/ArticleCard.vue';
import CtaBand from '@/components/site/CtaBand.vue';
import DisciplineCard from '@/components/site/DisciplineCard.vue';
import EventCard from '@/components/site/EventCard.vue';
import FaqList from '@/components/site/FaqList.vue';
import SitePicture from '@/components/site/SitePicture.vue';
import VenueCard from '@/components/site/VenueCard.vue';
import WaveDivider from '@/components/site/WaveDivider.vue';
import type {
    ArticleSummary,
    DisciplineSummary,
    EventSummary,
    FaqItem,
    SiteImage,
    VenueSummary,
} from '@/types';

defineProps<{
    nextSession: EventSummary | null;
    events: EventSummary[];
    disciplines: DisciplineSummary[];
    articles: ArticleSummary[];
    venues: VenueSummary[];
    faqs: FaqItem[];
    heroImage: SiteImage;
}>();

const reasons = [
    {
        icon: Users,
        title: 'All levels, age 8 and up',
        text: 'Individuals and families, complete beginners and old hands. You do not need to have sat in a boat before.',
    },
    {
        icon: Kayak,
        title: 'Boats and kit provided',
        text: 'Kayaks, canoes and paddleboards for nearly every kind of paddling, plus paddles and buoyancy aids.',
    },
    {
        icon: ShieldCheck,
        title: 'Qualified volunteer coaches',
        text: 'Learn in the clean, calm water of the Lido or a warm pool, with coaches who do this because they love it.',
    },
    {
        icon: HeartHandshake,
        title: 'Run by its members',
        text: 'A friendly community club since 1986. Fees only cover the running costs and keeping the kit in good order.',
    },
];
</script>

<template>
    <!-- Hero: deep water, drifting waves, and the one thing to do next. -->
    <section class="relative isolate overflow-hidden bg-ink-900 text-white">
        <div
            class="absolute inset-0 -z-10 bg-linear-to-b from-ink-950 via-ink-900 to-river-800"
            aria-hidden="true"
        ></div>
        <div class="contours absolute inset-0 -z-10" aria-hidden="true"></div>

        <div
            class="shell grid items-center gap-8 pt-10 pb-24 sm:pt-16 sm:pb-32 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12 lg:pt-20"
        >
            <div>
                <p class="eyebrow !text-gold-400">
                    Kayaking and canoeing in Aldershot
                </p>
                <h1 class="text-hero mt-3">
                    Find your water with
                    <span class="text-river-300">Blackwater Valley</span>
                    Canoe Club
                </h1>
                <p class="mt-5 max-w-xl text-lg text-river-100 sm:text-xl">
                    A friendly, volunteer-run club for ages 8 and up. Weekly
                    sessions, whitewater and touring trips, canoe polo and
                    coaching. Beginners welcome, kit provided.
                </p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <Link href="/paddle/beginners" class="btn btn-gold"
                        >Come and try it
                        <ArrowRight class="size-5" aria-hidden="true"
                    /></Link>
                    <Link href="/join" class="btn btn-outline"
                        >Membership and fees</Link
                    >
                </div>
                <ul
                    class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold text-river-200"
                >
                    <li>Every Wednesday evening</li>
                    <li>Since 1986</li>
                    <li>Clubmark accredited</li>
                </ul>
            </div>

            <!-- A photo when the club has added one, framed like a porthole on the river. -->
            <div v-if="heroImage" class="relative">
                <SitePicture
                    :image="heroImage"
                    eager
                    sizes="(min-width: 1024px) 45vw, 100vw"
                    class="aspect-[4/3] w-full rounded-[2.5rem_2.5rem_2.5rem_0.75rem] shadow-2xl ring-4 shadow-ink-950/50 ring-white/10"
                />
            </div>
            <div
                v-else
                class="relative mx-auto hidden aspect-square w-full max-w-sm items-center justify-center lg:flex"
                aria-hidden="true"
            >
                <div
                    class="absolute inset-0 rounded-full border border-river-300/20"
                ></div>
                <div
                    class="absolute inset-8 rounded-full border border-river-300/30"
                ></div>
                <div
                    class="absolute inset-16 rounded-full bg-river-700/40"
                ></div>
                <img
                    src="/images/brand/logo.png"
                    alt=""
                    width="400"
                    height="293"
                    class="relative w-3/5 rounded-[2rem] shadow-2xl ring-4 shadow-ink-950/60 ring-white/10"
                />
            </div>
        </div>

        <!-- Three layers of water. The back two drift; all stop for reduced motion. -->
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0"
            aria-hidden="true"
        >
            <div class="flex w-[200%] animate-drift-slow text-river-600/40">
                <WaveDivider /><WaveDivider />
            </div>
            <div
                class="-mt-6 flex w-[200%] animate-drift text-river-400/40 sm:-mt-9 lg:-mt-12"
            >
                <WaveDivider /><WaveDivider />
            </div>
            <WaveDivider class="-mt-5 -mb-px text-page sm:-mt-8 lg:-mt-10" />
        </div>
    </section>

    <!-- Next session: the answer to "when can I come?" -->
    <section
        v-if="nextSession"
        class="shell relative z-10 -mt-10 sm:-mt-14"
        aria-labelledby="next-session"
    >
        <div
            class="card grid gap-4 p-5 shadow-lg sm:grid-cols-[1fr_auto] sm:items-center sm:p-7"
        >
            <div>
                <h2 id="next-session" class="eyebrow">Next session</h2>
                <p
                    class="mt-1 font-display text-2xl leading-tight font-bold sm:text-3xl"
                >
                    {{ nextSession.date_label }}
                </p>
                <ul
                    class="mt-3 flex flex-col gap-x-6 gap-y-1.5 text-soft sm:flex-row sm:flex-wrap"
                >
                    <li class="flex items-center gap-2">
                        <Clock class="size-5 flex-none" aria-hidden="true" />
                        {{ nextSession.time_label }}
                    </li>
                    <li
                        v-if="nextSession.venue"
                        class="flex items-center gap-2"
                    >
                        <MapPin class="size-5 flex-none" aria-hidden="true" />
                        {{ nextSession.venue.name }}
                    </li>
                    <li v-if="nextSession.price_label">
                        {{ nextSession.price_label }}, kit included
                    </li>
                </ul>
            </div>
            <Link :href="nextSession.url" class="btn btn-ink"
                >Session details</Link
            >
        </div>
    </section>

    <section class="section shell" aria-labelledby="disciplines">
        <p class="eyebrow">What we paddle</p>
        <h2 id="disciplines" class="text-heading mt-2">
            Flat water to whitewater
        </h2>
        <p class="mt-3 max-w-2xl text-lg text-soft">
            Start in the pool, then follow whichever current takes you. The club
            has the boats, the coaches and the trips for all of it.
        </p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <DisciplineCard
                v-for="discipline in disciplines"
                :key="discipline.slug"
                :discipline="discipline"
            />
        </div>
    </section>

    <section
        v-if="events.length"
        class="bg-mist dark:bg-ink-900"
        aria-labelledby="events"
    >
        <div class="section shell">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Coming up</p>
                    <h2 id="events" class="text-heading mt-2">
                        Trips and events
                    </h2>
                </div>
                <Link href="/events" class="link">Full calendar</Link>
            </div>
            <div class="mt-8 grid gap-4 lg:grid-cols-3">
                <EventCard
                    v-for="event in events"
                    :key="event.url"
                    :event="event"
                />
            </div>
        </div>
    </section>

    <section class="section shell" aria-labelledby="why">
        <p class="eyebrow">Why paddle with us</p>
        <h2 id="why" class="text-heading mt-2">
            Everything you need to get afloat
        </h2>
        <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <li v-for="reason in reasons" :key="reason.title">
                <span
                    class="flex size-12 items-center justify-center rounded-full bg-gold-400 text-ink-950"
                >
                    <component
                        :is="reason.icon"
                        class="size-6"
                        aria-hidden="true"
                    />
                </span>
                <h3 class="mt-4 text-xl font-bold">{{ reason.title }}</h3>
                <p class="mt-2 text-soft">{{ reason.text }}</p>
            </li>
        </ul>
    </section>

    <section class="bg-mist dark:bg-ink-900" aria-labelledby="venues">
        <div class="section shell">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Where we paddle</p>
                    <h2 id="venues" class="text-heading mt-2">
                        A venue for every season
                    </h2>
                </div>
                <Link href="/venues" class="link">All venues</Link>
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <VenueCard
                    v-for="venue in venues"
                    :key="venue.url"
                    :venue="venue"
                />
            </div>
        </div>
    </section>

    <section
        v-if="faqs.length"
        class="section shell"
        aria-labelledby="questions"
    >
        <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="eyebrow">Good to know</p>
                <h2 id="questions" class="text-heading mt-2">
                    First-time questions
                </h2>
                <p class="mt-3 text-lg text-soft">
                    The things people ask before their first session.
                </p>
                <Link href="/faqs" class="link mt-4 inline-block"
                    >All questions and answers</Link
                >
            </div>
            <FaqList :faqs="faqs" />
        </div>
    </section>

    <section v-if="articles.length" class="shell pb-4" aria-labelledby="news">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">From the club</p>
                <h2 id="news" class="text-heading mt-2">Latest news</h2>
            </div>
            <Link href="/news" class="link">All news</Link>
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <ArticleCard
                v-for="article in articles"
                :key="article.url"
                :article="article"
            />
        </div>
    </section>

    <CtaBand />
</template>
