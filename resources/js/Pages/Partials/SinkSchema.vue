<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    sinkId: { type: String, required: true },
    table: { type: String, required: true },
});

const schema = ref([]);
const headers = ref([
    { title: '#', key: 'number' },
    { title: 'Field', key: 'Field' },
    { title: 'Type', key: 'Type' },
    { title: 'Default', key: 'Default' },
    { title: 'Key', key: 'Key' },
    { title: 'Comment', key: 'Comment', sortable: false },
]);

onMounted(async () => {
    const result = await axios.get(`/api/sinks/${props.sinkId}/schemas/${props.table}`);
    schema.value = result.data.schema;
    let nr = 1;
    schema.value.forEach((item) => {
        item.number = nr;
        nr += 1;
    });
});
</script>

<template>
  <v-data-table
    :headers="headers"
    :items="schema"
    item-key="title"
    items-per-page="100"
  >
    <template #item.Field="{ value }">
      <span class="font-weight-bold">{{ value }}</span>
    </template>
    <template #item.Comment="{ value }">
      <span class="text-medium-emphasis">{{ value }}</span>
    </template>
  </v-data-table>
</template>
