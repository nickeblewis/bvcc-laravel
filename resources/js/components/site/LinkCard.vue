<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps<{ title: string; summary?: string | null; href: string }>();
</script>

<template>
    <Link
        :href="href"
        class="group card flex items-start justify-between gap-4 p-5 transition-shadow hover:shadow-md"
    >
        <span>
            <span
                class="block text-lg leading-snug font-bold group-hover:text-link"
                >{{ title }}</span
            >
            <span v-if="summary" class="mt-1 block text-soft">{{
                summary
            }}</span>
        </span>
        <ArrowRight
            class="mt-1 size-5 flex-none text-link transition-transform group-hover:translate-x-1"
            aria-hidden="true"
        />
    </Link>
</template>
