<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import FaqList from '@/components/site/FaqList.vue';
import FormField from '@/components/site/FormField.vue';
import PageHero from '@/components/site/PageHero.vue';
import QuickFacts from '@/components/site/QuickFacts.vue';
import type { ContentPage, FaqItem } from '@/types';

defineProps<{
    page: ContentPage;
    faqs: FaqItem[];
    options: {
        items: string[];
        craftTypes: string[];
        useTypes: Record<string, string>;
    };
    sent: boolean;
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    use_type: 'club',
    collect_on: '',
    return_on: '',
    participants: 1,
    items: [] as string[],
    craft_type: '',
    intended_use: '',
    notes: '',
    terms: false,
    website: '',
});

const submit = () =>
    form.post('/join/hire', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
</script>

<template>
    <PageHero :title="page.title" :summary="page.summary" eyebrow="Join" />

    <div class="shell grid gap-10 py-10 lg:grid-cols-[1fr_1fr] lg:py-14">
        <article class="min-w-0">
            <QuickFacts :facts="page.facts" class="mb-8 !grid-cols-1" />
            <!-- eslint-disable-next-line vue/no-v-html -- rendered and escaped on the server by App\Support\Markdown -->
            <div class="prose-site" v-html="page.html"></div>
            <p class="mt-6">
                <Link href="/join/hire-terms" class="link"
                    >Read the hire terms</Link
                >
            </p>
            <section v-if="faqs.length" class="mt-10" aria-labelledby="faqs">
                <h2 id="faqs" class="text-heading mb-5">Common questions</h2>
                <FaqList :faqs="faqs" />
            </section>
        </article>

        <section aria-labelledby="request" class="card p-5 sm:p-7">
            <h2 id="request" class="text-heading">Hire request</h2>

            <div
                v-if="sent"
                role="status"
                class="mt-5 flex gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-950"
            >
                <CircleCheck
                    class="mt-0.5 size-6 flex-none"
                    aria-hidden="true"
                />
                <p>
                    <strong class="block"
                        >Thanks, your request is with the equipment
                        team.</strong
                    >
                    They will email you to confirm what is available.
                </p>
            </div>

            <form class="mt-5 space-y-5" novalidate @submit.prevent="submit">
                <FormField
                    v-slot="field"
                    label="Your name"
                    :error="form.errors.name"
                >
                    <input
                        v-model="form.name"
                        v-bind="field"
                        type="text"
                        autocomplete="name"
                        required
                        class="field-input"
                    />
                </FormField>
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
                <FormField
                    v-slot="field"
                    label="Phone"
                    optional
                    :error="form.errors.phone"
                >
                    <input
                        v-model="form.phone"
                        v-bind="field"
                        type="tel"
                        autocomplete="tel"
                        inputmode="tel"
                        class="field-input"
                    />
                </FormField>

                <fieldset>
                    <legend class="mb-1.5 font-semibold">
                        What is it for?
                    </legend>
                    <label
                        v-for="(label, value) in options.useTypes"
                        :key="value"
                        class="flex min-h-11 items-center gap-3"
                    >
                        <input
                            v-model="form.use_type"
                            type="radio"
                            name="use_type"
                            :value="value"
                            class="size-5 accent-river-700"
                        />
                        {{ label }}
                    </label>
                    <p
                        v-if="form.errors.use_type"
                        class="mt-1 text-sm font-semibold text-red-700 dark:text-red-300"
                    >
                        {{ form.errors.use_type }}
                    </p>
                </fieldset>

                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField
                        v-slot="field"
                        label="Collect on"
                        :error="form.errors.collect_on"
                    >
                        <input
                            v-model="form.collect_on"
                            v-bind="field"
                            type="date"
                            required
                            class="field-input"
                        />
                    </FormField>
                    <FormField
                        v-slot="field"
                        label="Return on"
                        hint="Up to 4 weeks later."
                        :error="form.errors.return_on"
                    >
                        <input
                            v-model="form.return_on"
                            v-bind="field"
                            type="date"
                            required
                            class="field-input"
                        />
                    </FormField>
                </div>

                <FormField
                    v-slot="field"
                    label="How many people?"
                    :error="form.errors.participants"
                >
                    <input
                        v-model.number="form.participants"
                        v-bind="field"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        max="30"
                        required
                        class="field-input max-w-32"
                    />
                </FormField>

                <fieldset>
                    <legend class="mb-1.5 font-semibold">
                        What do you need?
                    </legend>
                    <div class="grid sm:grid-cols-2">
                        <label
                            v-for="item in options.items"
                            :key="item"
                            class="flex min-h-11 items-center gap-3"
                        >
                            <input
                                v-model="form.items"
                                type="checkbox"
                                :value="item"
                                class="size-5 accent-river-700"
                            />
                            {{ item }}
                        </label>
                    </div>
                    <p
                        v-if="form.errors.items"
                        class="mt-1 text-sm font-semibold text-red-700 dark:text-red-300"
                    >
                        {{ form.errors.items }}
                    </p>
                </fieldset>

                <FormField
                    v-slot="field"
                    label="Type of boat or board"
                    optional
                    :error="form.errors.craft_type"
                >
                    <select
                        v-model="form.craft_type"
                        v-bind="field"
                        class="field-input"
                    >
                        <option value="">Not sure, or not needed</option>
                        <option v-for="type in options.craftTypes" :key="type">
                            {{ type }}
                        </option>
                    </select>
                </FormField>

                <FormField
                    v-slot="field"
                    label="Where and what will you be paddling?"
                    optional
                    :error="form.errors.intended_use"
                >
                    <textarea
                        v-model="form.intended_use"
                        v-bind="field"
                        rows="3"
                        class="field-input"
                    ></textarea>
                </FormField>

                <FormField
                    v-slot="field"
                    label="Sizes and anything else we should know"
                    optional
                    :error="form.errors.notes"
                >
                    <textarea
                        v-model="form.notes"
                        v-bind="field"
                        rows="3"
                        class="field-input"
                    ></textarea>
                </FormField>

                <!-- Hidden from people; bots that fill it in are ignored. -->
                <div class="hidden" aria-hidden="true">
                    <label
                        >Website
                        <input
                            v-model="form.website"
                            type="text"
                            tabindex="-1"
                            autocomplete="off"
                    /></label>
                </div>

                <div>
                    <label class="flex min-h-11 items-start gap-3">
                        <input
                            v-model="form.terms"
                            type="checkbox"
                            class="mt-1 size-5 flex-none accent-river-700"
                        />
                        <span
                            >I have read and agree to the
                            <Link href="/join/hire-terms" class="link"
                                >hire terms</Link
                            >.</span
                        >
                    </label>
                    <p
                        v-if="form.errors.terms"
                        class="mt-1 text-sm font-semibold text-red-700 dark:text-red-300"
                    >
                        {{ form.errors.terms }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="btn btn-gold w-full sm:w-auto"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Sending…' : 'Send hire request' }}
                </button>
            </form>
        </section>
    </div>
</template>
