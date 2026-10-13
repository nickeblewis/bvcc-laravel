<script setup lang="ts">
import type { SiteImage } from '@/types';

/**
 * A responsive photo. The server offers several widths as WebP; `sizes`
 * tells the browser how wide the photo is shown so a phone picks a small one.
 */
withDefaults(
    defineProps<{
        image: NonNullable<SiteImage>;
        sizes?: string;
        eager?: boolean;
    }>(),
    { sizes: '(min-width: 1024px) 50vw, 100vw', eager: false },
);
</script>

<template>
    <img
        :src="image.src"
        :srcset="image.srcset"
        :sizes="sizes"
        :alt="image.alt"
        :loading="eager ? 'eager' : 'lazy'"
        :fetchpriority="eager ? 'high' : undefined"
        decoding="async"
        class="bg-mist object-cover dark:bg-ink-700"
    />
</template>
