<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import FormField from '@/components/site/FormField.vue';
import PageHero from '@/components/site/PageHero.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({ email: '' });

const submit = () => form.post('/forgot-password');
</script>

<template>
    <PageHero
        title="Forgotten your password?"
        summary="Tell us your email and we will send you a link to choose a new one."
    />

    <div class="shell py-10 lg:py-14">
        <section class="card mx-auto max-w-xl p-5 sm:p-7">
            <div
                v-if="status"
                role="status"
                class="mb-5 flex gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-950"
            >
                <CircleCheck
                    class="mt-0.5 size-6 flex-none"
                    aria-hidden="true"
                />
                <p>{{ status }}</p>
            </div>

            <form class="space-y-5" novalidate @submit.prevent="submit">
                <FormField
                    v-slot="field"
                    label="Email"
                    :error="form.errors.email"
                >
                    <input
                        v-model="form.email"
                        v-bind="field"
                        type="email"
                        autocomplete="email"
                        inputmode="email"
                        required
                        class="field-input"
                    />
                </FormField>
                <button
                    type="submit"
                    class="btn btn-gold w-full sm:w-auto"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Sending…' : 'Email me a link' }}
                </button>
            </form>

            <p class="mt-6">
                Remembered it?
                <Link href="/login" class="link">Log in</Link>.
            </p>
        </section>
    </div>
</template>
