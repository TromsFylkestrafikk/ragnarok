<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppAnon from '@/Layouts/AppAnon.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: { type: String, default: null },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
  <AppAnon title="Email Verification">
    <div class="tw-mb-4 tw-text-sm tw-text-gray-600">
      Before continuing, could you verify your email address by clicking on the
      link we just emailed to you? If you didn't receive the email, we will
      gladly send you another.
    </div>

    <div v-if="verificationLinkSent" class="tw-mb-4 tw-font-medium tw-text-sm tw-text-green-600">
      A new verification link has been sent to the email address you provided in
      your profile settings.
    </div>

    <form @submit.prevent="submit">
      <div class="tw-mt-4 tw-flex tw-items-center tw-justify-between">
        <PrimaryButton :class="{ 'tw-opacity-25': form.processing }" :disabled="form.processing">
          Resend Verification Email
        </PrimaryButton>

        <div>
          <Link
            :href="route('profile.show')"
            class="tw-underline tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-indigo-500"
          >
            Edit Profile
          </Link>

          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="tw-underline tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-indigo-500 tw-ml-2"
          >
            Log Out
          </Link>
        </div>
      </div>
    </form>
  </AppAnon>
</template>
