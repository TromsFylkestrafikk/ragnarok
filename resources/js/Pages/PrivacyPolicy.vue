<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    policy: String,
});
</script>

<template>
  <Head title="Privacy Policy" />

  <div class="tw-font-sans tw-text-gray-900 tw-antialiased">
    <div class="tw-pt-4 tw-bg-gray-100">
      <div class="tw-min-h-screen tw-flex tw-flex-col tw-items-center tw-pt-6 sm:tw-pt-0">
        <div
          class="tw-w-full sm:tw-max-w-2xl tw-mt-6 tw-p-6 tw-bg-white tw-shadow-md tw-overflow-hidden sm:tw-rounded-lg tw-prose"
          v-html="policy"
        />
      </div>
    </div>
  </div>
</template>
