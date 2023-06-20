<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    sinks: { type: Array, required: true },
});

const headers = ref([
    { title: 'Source', key: 'name' },
    { title: 'Last import', key: 'lastImport.started_at' },
    { title: 'Last import status', key: 'lastImport.status' },
    { key: 'actions' },
]);
const page = ref(1);
const ajaxing = ref(false);

const sinksKeyed = computed(() => {
    const ret = {};
    props.sinks.forEach((sink) => ret[sink.name] = sink);
    return ret;
});

function naIfEmpty(item, key) {
    return item.columns[key] || 'N/A';
}

function importSingle(sinkName) {
    ajaxing.value = true;
    axios.post(
        'api/sink/import',
        { sink_name: sinkName }
    ).then((result) => {
        sinksKeyed.value[sinkName].lastImport = result.data;
    }).catch((error) => {
        console.warn(error.response.data);
    }).finally(() => ajaxing.value = false);
}

onMounted(() => {
    Echo.private('App.Models.SinkImport').listen('.SinkImportUpdated', (event) => {
        const sinkImport = event.model;
        sinksKeyed.value[sinkImport.sink_name].lastImport = sinkImport;
    });
});

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
      <template #item.lastImport.status="{ item }">
        {{ naIfEmpty(item, 'lastImport.status') }}
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
