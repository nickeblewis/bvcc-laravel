<script setup lang="ts">
import CtaBand from '@/components/site/CtaBand.vue';
import LinkCard from '@/components/site/LinkCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import QuickFacts from '@/components/site/QuickFacts.vue';
import type { PageLink } from '@/types';

defineProps<{
    pages: PageLink[];
    roles: {
        title: string;
        holder: string | null;
        email: string | null;
        description: string | null;
    }[];
    documentCount: number;
}>();

const facts = [
    { label: 'Founded', value: '1986' },
    { label: 'Home', value: 'Aldershot Lido, Hampshire' },
    { label: 'Run by', value: 'Volunteers, elected by the members' },
    { label: 'Accreditation', value: 'Clubmark, and affiliated to Paddle UK' },
];
</script>

<template>
    <PageHero
        title="About the club"
        summary="A friendly, community canoe club run entirely by volunteers who love paddling, since 1986."
    />

    <section class="shell py-10 lg:py-14" aria-labelledby="who">
        <QuickFacts :facts="facts" />
        <div class="prose-site mt-10">
            <h2 id="who">Who are we?</h2>
            <p>
                Blackwater Valley Canoe Club was founded in 1986 and has its
                home at Aldershot Lido. We welcome individuals and families of
                any level, aged 8 and over: whether you have never paddled
                before or have years of experience, there is something here for
                you.
            </p>
            <p>
                The club has many qualified coaches with experience across a
                wide range of disciplines, and a large fleet of club boats:
                recreational kayaks and sit-on-tops, open canoes, playboats,
                whitewater kayaks, paddleboards, slalom boats, polo boats and a
                few more specialised craft.
            </p>
            <h2>What do we do?</h2>
            <p>
                We run weekly paddling sessions all year round, and trips most
                months: gentle touring on local rivers, whitewater weekends, sea
                paddles and a trip abroad most years. Our canoe polo section
                trains year-round and plays in national and regional leagues.
            </p>
        </div>
    </section>

    <section class="bg-mist dark:bg-ink-900" aria-labelledby="committee">
        <div class="section shell">
            <h2 id="committee" class="text-heading">Who runs the club?</h2>
            <p class="mt-3 max-w-2xl text-lg text-soft">
                A committee of volunteers elected by the members each year. Each
                role has its own email address.
            </p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="role in roles" :key="role.title" class="card p-5">
                    <h3 class="text-lg font-bold">{{ role.title }}</h3>
                    <p v-if="role.holder" class="font-semibold text-link">
                        {{ role.holder }}
                    </p>
                    <p v-if="role.description" class="mt-1 text-soft">
                        {{ role.description }}
                    </p>
                    <a
                        v-if="role.email"
                        :href="`mailto:${role.email}`"
                        class="link mt-2 inline-flex min-h-11 items-center text-sm break-all"
                        >{{ role.email }}</a
                    >
                </li>
            </ul>
        </div>
    </section>

    <section class="section shell" aria-labelledby="run">
        <h2 id="run" class="text-heading">How the club is run</h2>
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <LinkCard
                title="Documents and policies"
                :summary="
                    documentCount
                        ? `${documentCount} documents to download: policies, risk assessments and minutes.`
                        : 'Policies, risk assessments and meeting minutes.'
                "
                href="/club/documents"
            />
            <LinkCard
                v-for="link in pages"
                :key="link.url"
                :title="link.title"
                :summary="link.summary"
                :href="link.url"
            />
        </div>
    </section>

    <CtaBand />
</template>
