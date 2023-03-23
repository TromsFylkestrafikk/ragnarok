<script setup>
import SectionTitle from './SectionTitle.vue';
</script>

<template>
  <div class="md:tw-grid md:tw-grid-cols-3 md:tw-gap-6">
    <SectionTitle>
      <template #title>
        <slot name="title" />
      </template>
      <template #description>
        <slot name="description" />
      </template>
    </SectionTitle>

    <div class="tw-mt-5 md:tw-mt-0 md:tw-col-span-2">
      <div class="tw-px-4 tw-py-5 sm:tw-p-6 tw-bg-white tw-shadow sm:tw-rounded-lg">
        <slot name="content" />
      </div>
    </div>
  </div>
</template>
