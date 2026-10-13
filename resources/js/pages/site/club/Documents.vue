<script setup lang="ts">
import { FileText } from '@lucide/vue';
import LinkCard from '@/components/site/LinkCard.vue';
import PageHero from '@/components/site/PageHero.vue';
import type { PageLink } from '@/types';

defineProps<{
    groups: {
        label: string;
        documents: {
            title: string;
            url: string;
            date: string | null;
            type: string;
        }[];
    }[];
    pages: PageLink[];
}>();
</script>

<template>
    <PageHero
        title="Documents and policies"
        summary="How the club is run, and the policies that keep everyone safe."
        eyebrow="Club"
    />

    <div class="shell py-10 lg:py-14">
        <section aria-labelledby="read">
            <h2 id="read" class="text-heading">Read online</h2>
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

        <section v-for="group in groups" :key="group.label" class="mt-12">
            <h2 class="text-heading">{{ group.label }}</h2>
            <ul class="card mt-5 max-w-3xl divide-y divide-line">
                <li v-for="document in group.documents" :key="document.url">
                    <a
                        :href="document.url"
                        class="flex min-h-14 items-center gap-3 px-5 py-3 hover:text-link"
                    >
                        <FileText
                            class="size-5 flex-none text-link"
                            aria-hidden="true"
                        />
                        <span class="flex-1 font-semibold">{{
                            document.title
                        }}</span>
                        <span class="flex-none text-sm text-soft">
                            {{ document.type
                            }}<template v-if="document.date"
                                >, {{ document.date }}</template
                            >
                        </span>
                    </a>
                </li>
            </ul>
        </section>

        <p v-if="!groups.length" class="mt-10 max-w-prose text-soft">
            Downloads are being moved over from our old website.
        </p>
    </div>
</template>
