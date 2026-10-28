<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import FormField from '@/components/site/FormField.vue';
import PageHero from '@/components/site/PageHero.vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () =>
    form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <PageHero
        title="Log in"
        summary="See your membership and, soon, book your place on sessions and trips."
    />

    <div class="shell grid gap-8 py-10 lg:grid-cols-[1.2fr_0.8fr] lg:py-14">
        <section aria-labelledby="login" class="card p-5 sm:p-7">
            <h2 id="login" class="text-heading">Log in to your account</h2>

            <div
                v-if="status"
                role="status"
                class="mt-5 flex gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-950"
            >
                <CircleCheck
                    class="mt-0.5 size-6 flex-none"
                    aria-hidden="true"
                />
                <p>{{ status }}</p>
            </div>

            <div class="mt-5">
                <PasskeyVerify />
            </div>

            <form class="mt-5 space-y-5" novalidate @submit.prevent="submit">
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
                    label="Password"
                    :error="form.errors.password"
                >
                    <input
                        v-model="form.password"
                        v-bind="field"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="field-input"
                    />
                </FormField>

                <label class="flex min-h-11 items-center gap-3">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="size-5 accent-river-700"
                    />
                    Keep me logged in on this device
                </label>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                    <button
                        type="submit"
                        class="btn btn-gold w-full sm:w-auto"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Logging in…' : 'Log in' }}
                    </button>
                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="link inline-flex min-h-11 items-center"
                        >Forgotten your password?</Link
                    >
                </div>
            </form>
        </section>

        <aside aria-labelledby="new" class="card self-start p-5 sm:p-7">
            <h2 id="new" class="text-heading">New to the club?</h2>
            <p class="mt-3">
                Register to create your account and choose a membership. It
                takes about five minutes.
            </p>
            <Link href="/register" class="btn btn-ink mt-5 w-full sm:w-auto"
                >Register</Link
            >
            <p class="mt-5 text-sm text-soft">
                Not sure which membership is right?
                <Link href="/join" class="link">See membership and fees</Link>.
            </p>
        </aside>
    </div>
</template>
