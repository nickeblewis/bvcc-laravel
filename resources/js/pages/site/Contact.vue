<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CircleCheck, Mail } from '@lucide/vue';
import FormField from '@/components/site/FormField.vue';
import PageHero from '@/components/site/PageHero.vue';

const props = defineProps<{
    topics: { id: number; title: string; description: string | null }[];
    selected: number | null;
    email: string;
    social: Record<string, string>;
    sent: boolean;
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    committee_role_id: props.selected ?? props.topics[0]?.id ?? null,
    message: '',
    website: '',
});

const submit = () =>
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
    });
</script>

<template>
    <PageHero
        title="Contact us"
        summary="Ask us anything. The club is run by volunteers, so replies can take a day or two."
    />

    <div class="shell grid gap-10 py-10 lg:grid-cols-[1.2fr_0.8fr] lg:py-14">
        <section aria-labelledby="message" class="card p-5 sm:p-7">
            <h2 id="message" class="text-heading">Send us a message</h2>

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
                        >Thanks, your message is on its way.</strong
                    >
                    One of our volunteers will reply by email.
                </p>
            </div>

            <form class="mt-5 space-y-5" novalidate @submit.prevent="submit">
                <FormField
                    v-if="topics.length"
                    v-slot="field"
                    label="What is it about?"
                    :error="form.errors.committee_role_id"
                >
                    <select
                        v-model="form.committee_role_id"
                        v-bind="field"
                        class="field-input"
                    >
                        <option
                            v-for="topic in topics"
                            :key="topic.id"
                            :value="topic.id"
                        >
                            {{ topic.title }}
                        </option>
                    </select>
                </FormField>
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
                    hint="So we can reply."
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
                <FormField
                    v-slot="field"
                    label="Message"
                    :error="form.errors.message"
                >
                    <textarea
                        v-model="form.message"
                        v-bind="field"
                        rows="6"
                        required
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

                <button
                    type="submit"
                    class="btn btn-gold w-full sm:w-auto"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Sending…' : 'Send message' }}
                </button>
            </form>
        </section>

        <aside class="space-y-4" aria-label="Other ways to reach us">
            <div class="card p-5">
                <h2
                    class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                >
                    Email
                </h2>
                <a
                    :href="`mailto:${email}`"
                    class="mt-2 flex min-h-11 items-center gap-2 text-lg font-semibold break-all hover:text-link"
                >
                    <Mail class="size-5 flex-none" aria-hidden="true" />
                    {{ email }}
                </a>
            </div>
            <div class="card p-5">
                <h2
                    class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                >
                    Social
                </h2>
                <ul class="mt-2">
                    <li v-if="social.facebook">
                        <a
                            :href="social.facebook"
                            rel="noopener"
                            class="link flex min-h-11 items-center"
                            >Facebook page</a
                        >
                    </li>
                    <li v-if="social.facebook_group">
                        <a
                            :href="social.facebook_group"
                            rel="noopener"
                            class="link flex min-h-11 items-center"
                            >Facebook group for members</a
                        >
                    </li>
                    <li v-if="social.vimeo">
                        <a
                            :href="social.vimeo"
                            rel="noopener"
                            class="link flex min-h-11 items-center"
                            >Videos on Vimeo</a
                        >
                    </li>
                </ul>
            </div>
            <div class="card p-5">
                <h2
                    class="font-sans text-sm font-semibold tracking-widest text-link uppercase"
                >
                    Worried about someone's welfare?
                </h2>
                <p class="mt-2">
                    Choose "Welfare officer" above, or see our
                    <a href="/club/safeguarding" class="link"
                        >safeguarding page</a
                    >. If someone is in immediate danger, call 999.
                </p>
            </div>
        </aside>
    </div>
</template>
