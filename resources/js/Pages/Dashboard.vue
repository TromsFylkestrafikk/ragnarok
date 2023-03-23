<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header>
      <h2 class="tw-font-semibold tw-text-xl tw-text-gray-800 tw-leading-tight">
        Dashboard
      </h2>
    </template>
    <VCard text="We're live. And live update doesn't work. Yes it does." />
  </AppLayout>
</template>
