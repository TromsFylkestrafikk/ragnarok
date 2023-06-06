<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

defineProps({
    sinks: { type: Array, required: true },
});

const headers = ref([{ title: 'Source', key: 'name' }]);
const page = ref(1);

</script>

<template>
  <app-layout title="Import status">
    <v-data-table
      :headers="headers"
      :items="sinks"
      items-per-page="100"
      item-value="name"
      no-filter
    >
      <template #bottom>
        <div class="text-center pt-2">
          <v-pagination v-model="page" />
        </div>
      </template>
    </v-data-table>
  </app-layout>
</template>
