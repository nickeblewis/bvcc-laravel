<script setup lang="ts">
import { useId } from 'vue';

/**
 * A labelled form control. The slot receives the id and the aria attributes
 * to put on the input, so the label, hint and error are all announced.
 */
const props = defineProps<{
    label: string;
    error?: string;
    hint?: string;
    optional?: boolean;
}>();

const id = useId();
const describedBy = () =>
    [props.hint ? `${id}-hint` : null, props.error ? `${id}-error` : null]
        .filter(Boolean)
        .join(' ') || undefined;
</script>

<template>
    <div>
        <label :for="id" class="mb-1.5 block font-semibold">
            {{ label }}
            <span v-if="optional" class="font-normal text-soft"
                >(optional)</span
            >
        </label>
        <p v-if="hint" :id="`${id}-hint`" class="mb-1.5 text-sm text-soft">
            {{ hint }}
        </p>
        <slot
            :id="id"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy()"
        />
        <p
            v-if="error"
            :id="`${id}-error`"
            class="mt-1.5 text-sm font-semibold text-red-700 dark:text-red-300"
        >
            {{ error }}
        </p>
    </div>
</template>
