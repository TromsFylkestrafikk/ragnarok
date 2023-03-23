<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const show = ref(true);
const style = computed(() => usePage().props.jetstream.flash?.bannerStyle || 'success');
const message = computed(() => usePage().props.jetstream.flash?.banner || '');

watch(message, async () => {
    show.value = true;
});
</script>

<template>
  <div>
    <div v-if="show && message" :class="{ 'bg-indigo-500': style == 'success', 'bg-red-700': style == 'danger' }">
      <div class="tw-max-w-screen-xl tw-mx-auto tw-py-2 tw-px-3 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
          <div class="tw-w-0 tw-flex-1 tw-flex tw-items-center tw-min-w-0">
            <span
              class="tw-flex tw-p-2 tw-rounded-lg"
              :class="{ 'tw-bg-indigo-600': style == 'success', 'tw-bg-red-600': style == 'danger' }"
            >
              <svg
                v-if="style == 'success'"
                class="tw-h-5 tw-w-5 tw-text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>

              <svg
                v-if="style == 'danger'"
                class="tw-h-5 tw-w-5 tw-text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                />
              </svg>
            </span>

            <p class="tw-ml-3 tw-font-medium tw-text-sm tw-text-white tw-truncate">
              {{ message }}
            </p>
          </div>

          <div class="tw-shrink-0 sm:tw-ml-3">
            <button
              type="button"
              class="-tw-mr-1 tw-flex tw-p-2 tw-rounded-md focus:tw-outline-none sm:-tw-mr-2 tw-transition"
              :class="{ 'hover:bg-indigo-600 focus:bg-indigo-600': style == 'success', 'hover:bg-red-600 focus:bg-red-600': style == 'danger' }"
              aria-label="Dismiss"
              @click.prevent="show = false"
            >
              <svg
                class="tw-h-5 tw-w-5 tw-text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
