<script setup lang="ts">
import type { Fact } from '@/types';

/**
 * The answers people look for first, as a short list at the top of a page:
 * when, where, cost, who it suits.
 */
defineProps<{ facts: Fact[] }>();
</script>

<template>
    <dl
        v-if="facts.length"
        class="card grid gap-px overflow-hidden bg-line sm:grid-cols-2"
        :class="{ 'lg:grid-cols-4': facts.length >= 4 }"
    >
        <div v-for="fact in facts" :key="fact.label" class="bg-panel p-4">
            <dt class="text-sm font-semibold text-link">{{ fact.label }}</dt>
            <dd class="mt-1 leading-snug font-medium">{{ fact.value }}</dd>
        </div>
    </dl>
</template>
