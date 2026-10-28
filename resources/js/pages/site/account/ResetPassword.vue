<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FormField from '@/components/site/FormField.vue';
import PageHero from '@/components/site/PageHero.vue';

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () =>
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
</script>

<template>
    <PageHero title="Choose a new password" />

    <div class="shell py-10 lg:py-14">
        <section class="card mx-auto max-w-xl p-5 sm:p-7">
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
                        readonly
                        class="field-input"
                    />
                </FormField>
                <FormField
                    v-slot="field"
                    label="New password"
                    :error="form.errors.password"
                >
                    <input
                        v-model="form.password"
                        v-bind="field"
                        type="password"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        required
                        class="field-input"
                    />
                </FormField>
                <FormField
                    v-slot="field"
                    label="New password again"
                    :error="form.errors.password_confirmation"
                >
                    <input
                        v-model="form.password_confirmation"
                        v-bind="field"
                        type="password"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        required
                        class="field-input"
                    />
                </FormField>
                <button
                    type="submit"
                    class="btn btn-gold w-full sm:w-auto"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save new password' }}
                </button>
            </form>
        </section>
    </div>
</template>
