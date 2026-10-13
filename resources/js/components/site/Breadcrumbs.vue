<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const crumbs = computed(() => page.props.meta?.breadcrumbs ?? []);
</script>

<template>
    <nav v-if="crumbs.length" aria-label="Breadcrumb" class="text-sm">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <li>
                <Link href="/" class="underline-offset-4 hover:underline"
                    >Home</Link
                >
            </li>
            <li
                v-for="(crumb, i) in crumbs"
                :key="crumb.href"
                class="flex items-center gap-2"
            >
                <span aria-hidden="true" class="opacity-50">/</span>
                <span v-if="i === crumbs.length - 1" aria-current="page">{{
                    crumb.label
                }}</span>
                <Link
                    v-else
                    :href="crumb.href"
                    class="underline-offset-4 hover:underline"
                    >{{ crumb.label }}</Link
                >
            </li>
        </ol>
    </nav>
</template>
