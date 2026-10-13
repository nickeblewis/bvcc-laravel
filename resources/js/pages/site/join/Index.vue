<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FaqList from '@/components/site/FaqList.vue';
import LinkCard from '@/components/site/LinkCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import type { FaqItem, PageLink } from '@/types';

defineProps<{
    tiers: {
        name: string;
        price: string;
        monthly: string | null;
        eligibility: string | null;
        description: string | null;
        band: string;
    }[];
    feeTable: {
        columns: string[];
        rows: { band: string; prices: string[] }[];
    };
    pages: PageLink[];
    faqs: FaqItem[];
    membershipsEmail: string;
}>();

const steps = [
    {
        title: 'Come and try a session',
        text: 'Turn up to any weekly session. There is no need to join first: register as a non-member for free and pay the session fee.',
    },
    {
        title: 'Pick a membership',
        text: 'If you like it, choose the membership that suits how often you will paddle. It lasts a year.',
    },
    {
        title: 'Tell us and pay',
        text: 'Contact the membership team. We take your details, including an emergency contact, and send you how to pay.',
    },
];
</script>

<template>
    <PageHero
        title="Join the club"
        summary="Try a session first, then pick the membership that fits how often you paddle. Membership runs for a year."
    >
        <Link href="/contact?topic=memberships" class="btn btn-gold"
            >Ask about joining</Link
        >
        <Link href="/paddle/beginners" class="btn btn-outline"
            >Try a session first</Link
        >
    </PageHero>

    <section class="shell py-10 lg:py-14" aria-labelledby="how">
        <h2 id="how" class="text-heading">How do I join?</h2>
        <ol class="mt-6 grid gap-4 lg:grid-cols-3">
            <li v-for="(step, i) in steps" :key="step.title" class="card p-5">
                <span
                    class="flex size-10 items-center justify-center rounded-full bg-gold-400 font-display text-lg font-extrabold text-ink-950"
                    >{{ i + 1 }}</span
                >
                <h3 class="mt-3 text-xl font-bold">{{ step.title }}</h3>
                <p class="mt-2 text-soft">{{ step.text }}</p>
            </li>
        </ol>
    </section>

    <section class="bg-mist dark:bg-ink-900" aria-labelledby="tiers">
        <div class="section shell">
            <h2 id="tiers" class="text-heading">What does membership cost?</h2>
            <p class="mt-3 max-w-2xl text-lg text-soft">
                Pay as you paddle members pay a small annual fee and a reduced
                fee each time they come. Monthly subscribers pay monthly and
                nothing per session.
            </p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li
                    v-for="tier in tiers"
                    :key="tier.name"
                    class="card flex flex-col p-5"
                >
                    <h3 class="text-lg leading-snug font-bold">
                        {{ tier.name }}
                    </h3>
                    <p class="mt-3 font-display text-4xl font-extrabold">
                        {{ tier.price }}
                        <span
                            v-if="tier.price !== 'Free'"
                            class="font-sans text-base font-semibold text-soft"
                            >{{ tier.monthly ? 'to join' : 'a year' }}</span
                        >
                    </p>
                    <p v-if="tier.monthly" class="font-semibold">
                        then {{ tier.monthly }} a month
                    </p>
                    <p v-if="tier.description" class="mt-3 text-soft">
                        {{ tier.description }}
                    </p>
                    <p v-if="tier.eligibility" class="mt-auto pt-4 text-sm">
                        <span class="font-semibold">For:</span>
                        {{ tier.eligibility }}
                    </p>
                </li>
            </ul>
        </div>
    </section>

    <section class="section shell" aria-labelledby="fees">
        <h2 id="fees" class="text-heading">
            What do sessions and events cost?
        </h2>
        <p class="mt-3 max-w-2xl text-lg text-soft">
            Club fees for each session or event, per person. Trips and courses
            can have extra costs, such as accommodation, which are shown on the
            event.
        </p>

        <!-- A table on wider screens; one card per membership type on phones. -->
        <div class="card mt-8 hidden overflow-hidden md:block">
            <table class="w-full text-left">
                <thead class="bg-ink-800 text-white">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold">
                            Membership
                        </th>
                        <th
                            v-for="column in feeTable.columns"
                            :key="column"
                            scope="col"
                            class="px-5 py-3 font-semibold"
                        >
                            {{ column }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="row in feeTable.rows" :key="row.band">
                        <th scope="row" class="px-5 py-4 font-semibold">
                            {{ row.band }}
                        </th>
                        <td
                            v-for="(price, i) in row.prices"
                            :key="i"
                            class="px-5 py-4 font-display text-lg font-bold"
                        >
                            {{ price }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ul class="mt-8 grid gap-4 md:hidden">
            <li
                v-for="row in feeTable.rows"
                :key="row.band"
                class="card overflow-hidden"
            >
                <h3
                    class="bg-ink-800 px-5 py-3 font-sans text-lg font-semibold text-white"
                >
                    {{ row.band }}
                </h3>
                <dl class="divide-y divide-line">
                    <div
                        v-for="(price, i) in row.prices"
                        :key="i"
                        class="flex items-center justify-between gap-4 px-5 py-3"
                    >
                        <dt>{{ feeTable.columns[i] }}</dt>
                        <dd class="font-display text-lg font-bold">
                            {{ price }}
                        </dd>
                    </div>
                </dl>
            </li>
        </ul>
        <p class="mt-4 max-w-prose text-soft">
            Volunteers helping to run a session or event do not pay the club fee
            for it.
        </p>
    </section>

    <section
        v-if="faqs.length"
        class="bg-mist dark:bg-ink-900"
        aria-labelledby="faqs"
    >
        <div class="section shell grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <h2 id="faqs" class="text-heading">Membership questions</h2>
                <p class="mt-3 text-lg text-soft">
                    Something else? Email
                    <a
                        :href="`mailto:${membershipsEmail}`"
                        class="link break-all"
                        >{{ membershipsEmail }}</a
                    >.
                </p>
            </div>
            <FaqList :faqs="faqs" />
        </div>
    </section>

    <section class="section shell" aria-labelledby="more">
        <h2 id="more" class="text-heading">Also useful</h2>
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <LinkCard
                v-for="link in pages"
                :key="link.url"
                :title="link.title"
                :summary="link.summary"
                :href="link.url"
            />
        </div>
    </section>
</template>
