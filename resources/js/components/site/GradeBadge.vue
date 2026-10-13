<script setup lang="ts">
import { computed } from 'vue';

/**
 * The international river grade, 1 (easy) to 5 (expert), drawn as wave
 * ticks so the difficulty reads at a glance without relying on colour.
 */
const props = defineProps<{ grade: number }>();

const tone = computed(
    () =>
        ({
            1: 'bg-emerald-100 text-emerald-900',
            2: 'bg-emerald-100 text-emerald-900',
            3: 'bg-gold-300 text-ink-950',
            4: 'bg-orange-200 text-orange-950',
            5: 'bg-red-200 text-red-950',
        })[props.grade] ?? 'bg-mist text-ink-900',
);
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold"
        :class="tone"
    >
        <span class="flex items-end gap-0.5" aria-hidden="true">
            <span
                v-for="n in 5"
                :key="n"
                class="w-1 rounded-full bg-current"
                :class="n <= grade ? 'opacity-100' : 'opacity-25'"
                :style="{ height: `${4 + n * 2}px` }"
            ></span>
        </span>
        Grade {{ grade }}
    </span>
</template>
