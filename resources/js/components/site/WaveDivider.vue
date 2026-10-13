<script setup lang="ts">
/**
 * A wave-shaped edge between two sections. It is filled with the current
 * text colour, so set it with a text-* class matching the section it leads
 * into. `flip` turns it over for the bottom of a section.
 */
defineProps<{ flip?: boolean }>();
</script>

<template>
    <svg
        aria-hidden="true"
        viewBox="0 0 1440 80"
        preserveAspectRatio="none"
        class="pointer-events-none block h-8 w-full sm:h-12 lg:h-16"
        :class="{ 'rotate-180': flip }"
    >
        <path
            fill="currentColor"
            d="M0 48c120-32 240-40 360-24s240 48 360 40 240-48 360-52 240 20 360 36v32H0Z"
        />
    </svg>
</template>
