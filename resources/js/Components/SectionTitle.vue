<template>
  <div class="md:tw-col-span-1 tw-flex tw-justify-between">
    <div class="tw-px-4 sm:tw-px-0">
      <h3 class="tw-text-lg tw-font-medium tw-text-gray-900">
        <slot name="title" />
      </h3>

      <p class="tw-mt-1 tw-text-sm tw-text-gray-600">
        <slot name="description" />
      </p>
    </div>

    <div class="tw-px-4 sm:tw-px-0">
      <slot name="aside" />
    </div>
  </div>
</template>
