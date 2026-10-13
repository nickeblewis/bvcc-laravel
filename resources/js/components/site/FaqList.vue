<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import type { FaqItem } from '@/types';

/**
 * Questions and answers. Native <details> elements: they work without
 * JavaScript, with a keyboard and with a screen reader, and the answers
 * are in the HTML for search engines either way.
 */
defineProps<{ faqs: FaqItem[] }>();
</script>

<template>
    <div class="card divide-y divide-line">
        <details v-for="faq in faqs" :key="faq.question" class="group">
            <summary
                class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 text-lg leading-snug font-semibold sm:px-6 [&::-webkit-details-marker]:hidden"
            >
                {{ faq.question }}
                <ChevronDown
                    class="size-5 flex-none text-link transition-transform group-open:rotate-180"
                    aria-hidden="true"
                />
            </summary>
            <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
            <div class="prose-site px-4 pb-5 sm:px-6" v-html="faq.html"></div>
        </details>
    </div>
</template>
