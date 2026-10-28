<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import FormField from '@/components/site/FormField.vue';
import PageHero from '@/components/site/PageHero.vue';

defineProps<{
    tiers: {
        id: number;
        name: string;
        price: string;
        monthly: string | null;
        eligibility: string | null;
        description: string | null;
        band: string;
    }[];
    passwordRules: string;
}>();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    date_of_birth: '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    town: '',
    postcode: '',
    paddle_uk_number: '',
    experience: '',
    emergency_name: '',
    emergency_relationship: '',
    emergency_phone: '',
    medical_notes: '',
    photo_consent: false,
    membership_tier_id: null as number | null,
    password: '',
    password_confirmation: '',
});

const submit = () =>
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
</script>

<template>
    <PageHero
        title="Register"
        summary="Create your account and choose a membership. We use these details to keep you safe on the water."
    />

    <div class="shell py-10 lg:py-14">
        <form
            class="card mx-auto max-w-3xl space-y-10 p-5 sm:p-7"
            novalidate
            @submit.prevent="submit"
        >
            <p>
                Already registered?
                <Link href="/login" class="link">Log in</Link>.
            </p>

            <fieldset class="space-y-5">
                <legend class="text-heading">About you</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField
                        v-slot="field"
                        label="First name"
                        :error="form.errors.first_name"
                    >
                        <input
                            v-model="form.first_name"
                            v-bind="field"
                            type="text"
                            autocomplete="given-name"
                            required
                            class="field-input"
                        />
                    </FormField>
                    <FormField
                        v-slot="field"
                        label="Last name"
                        :error="form.errors.last_name"
                    >
                        <input
                            v-model="form.last_name"
                            v-bind="field"
                            type="text"
                            autocomplete="family-name"
                            required
                            class="field-input"
                        />
                    </FormField>
                </div>
                <FormField
                    v-slot="field"
                    label="Email"
                    hint="You log in with this."
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
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField
                        v-slot="field"
                        label="Date of birth"
                        :error="form.errors.date_of_birth"
                    >
                        <input
                            v-model="form.date_of_birth"
                            v-bind="field"
                            type="date"
                            autocomplete="bday"
                            required
                            class="field-input"
                        />
                    </FormField>
                    <FormField
                        v-slot="field"
                        label="Phone"
                        :error="form.errors.phone"
                    >
                        <input
                            v-model="form.phone"
                            v-bind="field"
                            type="tel"
                            autocomplete="tel"
                            inputmode="tel"
                            required
                            class="field-input"
                        />
                    </FormField>
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-heading">Address</legend>
                <FormField
                    v-slot="field"
                    label="Address line 1"
                    :error="form.errors.address_line_1"
                >
                    <input
                        v-model="form.address_line_1"
                        v-bind="field"
                        type="text"
                        autocomplete="address-line1"
                        required
                        class="field-input"
                    />
                </FormField>
                <FormField
                    v-slot="field"
                    label="Address line 2"
                    optional
                    :error="form.errors.address_line_2"
                >
                    <input
                        v-model="form.address_line_2"
                        v-bind="field"
                        type="text"
                        autocomplete="address-line2"
                        class="field-input"
                    />
                </FormField>
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField
                        v-slot="field"
                        label="Town"
                        :error="form.errors.town"
                    >
                        <input
                            v-model="form.town"
                            v-bind="field"
                            type="text"
                            autocomplete="address-level2"
                            required
                            class="field-input"
                        />
                    </FormField>
                    <FormField
                        v-slot="field"
                        label="Postcode"
                        :error="form.errors.postcode"
                    >
                        <input
                            v-model="form.postcode"
                            v-bind="field"
                            type="text"
                            autocomplete="postal-code"
                            required
                            class="field-input"
                        />
                    </FormField>
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-heading">Paddling</legend>
                <FormField
                    v-slot="field"
                    label="Paddle UK membership number"
                    optional
                    :error="form.errors.paddle_uk_number"
                >
                    <input
                        v-model="form.paddle_uk_number"
                        v-bind="field"
                        type="text"
                        class="field-input"
                    />
                </FormField>
                <FormField
                    v-slot="field"
                    label="Your paddling experience"
                    optional
                    hint="Boats you have paddled, awards you hold, or 'none yet'."
                    :error="form.errors.experience"
                >
                    <textarea
                        v-model="form.experience"
                        v-bind="field"
                        rows="3"
                        class="field-input"
                    ></textarea>
                </FormField>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-heading">Emergency contact</legend>
                <p class="text-soft">
                    Someone we can call if something happens while you are with
                    the club.
                </p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField
                        v-slot="field"
                        label="Their name"
                        :error="form.errors.emergency_name"
                    >
                        <input
                            v-model="form.emergency_name"
                            v-bind="field"
                            type="text"
                            required
                            class="field-input"
                        />
                    </FormField>
                    <FormField
                        v-slot="field"
                        label="Their phone"
                        :error="form.errors.emergency_phone"
                    >
                        <input
                            v-model="form.emergency_phone"
                            v-bind="field"
                            type="tel"
                            inputmode="tel"
                            required
                            class="field-input"
                        />
                    </FormField>
                </div>
                <FormField
                    v-slot="field"
                    label="How they are related to you"
                    optional
                    :error="form.errors.emergency_relationship"
                >
                    <input
                        v-model="form.emergency_relationship"
                        v-bind="field"
                        type="text"
                        class="field-input"
                    />
                </FormField>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-heading">Health and consent</legend>
                <FormField
                    v-slot="field"
                    label="Medical conditions, allergies or medication"
                    optional
                    hint="Only the welfare officer and your session leader can see this."
                    :error="form.errors.medical_notes"
                >
                    <textarea
                        v-model="form.medical_notes"
                        v-bind="field"
                        rows="3"
                        class="field-input"
                    ></textarea>
                </FormField>
                <label class="flex min-h-11 items-start gap-3">
                    <input
                        v-model="form.photo_consent"
                        type="checkbox"
                        class="mt-0.5 size-5 flex-none accent-river-700"
                    />
                    <span
                        >The club may use photos or video of me in its news,
                        website and social media.
                        <Link
                            href="/club/photography-and-filming-policy"
                            class="link"
                            >Photography policy</Link
                        ></span
                    >
                </label>
            </fieldset>

            <fieldset>
                <legend class="text-heading">Membership</legend>
                <p class="mt-2 text-soft">
                    Prices and what each one includes are on
                    <Link href="/join" class="link">membership and fees</Link>.
                </p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label
                        v-for="tier in tiers"
                        :key="tier.id"
                        class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 has-[:checked]:border-river-700 has-[:checked]:bg-river-50 dark:has-[:checked]:bg-ink-800"
                    >
                        <input
                            v-model="form.membership_tier_id"
                            type="radio"
                            name="membership_tier_id"
                            :value="tier.id"
                            class="mt-1 size-5 flex-none accent-river-700"
                        />
                        <span>
                            <strong class="block">{{ tier.name }}</strong>
                            <span class="block"
                                >{{ tier.price
                                }}<template v-if="tier.monthly">
                                    then {{ tier.monthly }} a month</template
                                ></span
                            >
                            <span
                                v-if="tier.eligibility"
                                class="mt-1 block text-sm text-soft"
                                >{{ tier.eligibility }}</span
                            >
                        </span>
                    </label>
                </div>
                <p
                    v-if="form.errors.membership_tier_id"
                    class="mt-2 text-sm font-semibold text-red-700 dark:text-red-300"
                >
                    {{ form.errors.membership_tier_id }}
                </p>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-heading">Password</legend>
                <FormField
                    v-slot="field"
                    label="Password"
                    hint="A long one you do not use anywhere else."
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
                    label="Password again"
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
            </fieldset>

            <div>
                <button
                    type="submit"
                    class="btn btn-gold w-full sm:w-auto"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Registering…' : 'Register and join' }}
                </button>
                <p class="mt-3 text-sm text-soft">
                    We will email you a link to confirm your address. Paid
                    memberships start once the membership secretary has your
                    payment.
                </p>
            </div>
        </form>
    </div>
</template>
