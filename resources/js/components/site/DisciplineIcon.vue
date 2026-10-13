<script setup lang="ts">
import {
    Anchor,
    Compass,
    Kayak,
    Sparkles,
    Sun,
    Trophy,
    Waves,
} from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ slug: string }>();

const icon = computed(
    () =>
        ({
            whitewater: Waves,
            'canoe-polo': Trophy,
            touring: Compass,
            freestyle: Sparkles,
            'sea-kayaking': Anchor,
            'stand-up-paddleboarding': Sun,
        })[props.slug] ?? Kayak,
);
</script>

<template>
    <component :is="icon" aria-hidden="true" />
</template>
