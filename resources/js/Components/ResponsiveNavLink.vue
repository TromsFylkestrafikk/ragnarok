<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    active: Boolean,
    href: String,
    as: String,
});

const classes = computed(() => (
    props.active
        ? 'tw-block tw-w-full tw-pl-3 tw-pr-4 tw-py-2 tw-border-l-4 tw-border-indigo-400 tw-text-left tw-text-base tw-font-medium tw-text-indigo-700 tw-bg-indigo-50 focus:tw-outline-none focus:tw-text-indigo-800 focus:tw-bg-indigo-100 focus:tw-border-indigo-700 tw-transition tw-duration-150 tw-ease-in-out'
        : 'tw-block tw-w-full tw-pl-3 tw-pr-4 tw-py-2 tw-border-l-4 tw-border-transparent tw-text-left tw-text-base tw-font-medium tw-text-gray-600 hover:tw-text-gray-800 hover:tw-bg-gray-50 hover:tw-border-gray-300 focus:tw-outline-none focus:tw-text-gray-800 focus:tw-bg-gray-50 focus:tw-border-gray-300 tw-transition tw-duration-150 tw-ease-in-out'
));
</script>

<template>
  <div>
    <button v-if="as == 'button'" :class="classes" class="tw-w-full tw-text-left">
      <slot />
    </button>

    <Link v-else :href="href" :class="classes">
      <slot />
    </Link>
  </div>
</template>
