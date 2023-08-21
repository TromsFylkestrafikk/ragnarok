<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    sinks: { type: Array, required: true },
});

const headers = ref([
    { title: 'Source', key: 'id' },
    { title: 'Last import', key: 'lastImport.started_at' },
    { title: 'Last import status', key: 'lastImport.status' },
    { key: 'actions' },
]);
const page = ref(1);
const ajaxing = ref(false);

const sinksKeyed = computed(() => {
    const ret = {};
    props.sinks.forEach((sink) => ret[sink.id] = sink);
    return ret;
});

function naIfEmpty(item, key) {
    return item.columns[key] || 'N/A';
}

function importSingle(sinkId) {
    ajaxing.value = true;
    axios.post(
        'api/sink',
        { sink_id: sinkId }
    ).then((result) => {
        sinksKeyed.value[sinkId].lastImport = result.data;
    }).catch((error) => {
        console.warn(error.response.data);
    }).finally(() => ajaxing.value = false);
}

onMounted(() => {
    Echo.private('App.Models.SinkImport').listen('.SinkImportUpdated', (event) => {
        const sinkImport = event.model;
        sinksKeyed.value[sinkImport.sink_id].lastImport = sinkImport;
    });
});

</script>

<template>
  <app-layout title="Import status">
    <v-data-table
      :headers="headers"
      :items="sinks"
      items-per-page="100"
      item-value="id"
      no-filter
    >
      <template #item.lastImport.status="{ item }">
        {{ naIfEmpty(item, 'lastImport.status') }}
      </template>
      <template #item.id="{ item }">
        <Link :href="`/sink/${item.value}`">
          {{ item.raw.title }}
        </Link>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon flat @click="importSingle(item.value)">
          <v-icon icon="mdi-import" />
          <v-tooltip activator="parent">
            Import
          </v-tooltip>
        </v-btn>
      </template>
      <template #bottom>
        <div class="text-center pt-2">
          <v-pagination v-model="page" />
        </div>
      </template>
    </v-data-table>
  </app-layout>
</template>
