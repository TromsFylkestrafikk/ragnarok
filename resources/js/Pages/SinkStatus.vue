<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, ref } from 'vue';
import { forEach } from 'lodash';
import dayjs from 'dayjs';

const props = defineProps({
    sink: { type: Object, required: true },
});

const loading = ref(true);
const headers = ref([
    { title: 'Chunk ID', key: 'chunk_id', sortable: true },
    { title: 'Fetch status', key: 'fetch_status', sortable: true },
    { title: 'Import status', key: 'import_status', sortable: true },
    { title: 'Imported at', key: 'imported_at', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false },
]);

const items = ref([]);
const itemsKeyed = computed(() => {
    const ret = {};
    items.value.forEach((chunk) => {
        ret[chunk.id] = chunk;
    });
    return ret;
});
const selection = ref([]);
const statusColor = ref({
    new: 'blue',
    pending: 'grey',
    in_progress: 'orange',
    finished: 'green',
    failed: 'red',
});

async function loadItems({ page, itemsPerPage, sortBy }) {
    loading.value = true;
    const state = await axios
        .get(`/api/sink/${props.sink.id}/chunk`, { params: { page, itemsPerPage, sortBy } })
        .finally(() => loading.value = false);
    items.value = state.data;
}

function prettyDate(dateStr) {
    if (!dateStr) {
        return '';
    }
    return dayjs(dateStr).format('YYYY-MM-DD HH:mm:ss');
}

function fetchChunk(chunkId) {
    itemsKeyed.value[chunkId].fetch_status = 'pending';
    axios.post(`/api/sink/${props.sink.id}/chunk/fetch`, { ids: [chunkId] });
    // Fetch status update is done through broadcast events
}

function fetchSelection() {
    selection.value.forEach((chunkId) => itemsKeyed.value[chunkId] && (itemsKeyed.value[chunkId].fetch_status = 'pending'));
    axios.post(`/api/sink/${props.sink.id}/chunk/fetch`, { ids: selection.value.sort() });
}

function resetSelection() {
    selection.value = [];
}

function updateChunk(src, dest) {
    forEach(src, (val, key) => dest[key] = val);
}

function findAndUpdate(newChunk) {
    const found = itemsKeyed.value[newChunk.id] || null;
    if (found) {
        updateChunk(newChunk, found);
    }
}

function removeFromSelection(idToRemove) {
    const idx = selection.value.indexOf(idToRemove);
    if (idx !== -1) {
        selection.value.splice(idx, 1);
    }
}

onMounted(() => {
    Echo.private('App.Models.Chunk').listen('.ChunkUpdated', (event) => {
        findAndUpdate(event.model);
        removeFromSelection(event.model.id);
    });
});
</script>

<template>
  <app-layout title="Sink status">
    <v-data-table-server
      v-model="selection"
      :headers="headers"
      :items-length="sink.chunksCount"
      :items="items"
      items-per-page="20"
      :loading="loading"
      :search="''"
      show-select
      @update:options="loadItems"
    >
      <template #top>
        <v-toolbar flat>
          <v-toolbar-title>{{ sink.title }}</v-toolbar-title>
          <v-spacer />
          <v-col class="text-grey">{{ selection.length }} selected</v-col>
          <v-btn icon variant="plain" @click="resetSelection()">
            <v-icon icon="mdi-select-remove" />
            <v-tooltip activator="parent" location="top">
              Remove selection on all pages
            </v-tooltip>
          </v-btn>
          <v-btn icon variant="plain" @click="fetchSelection()">
            <v-icon icon="mdi-download" />
            <v-tooltip activator="parent" location="top">
              Fetch chunks to local storage (Stage 1)
            </v-tooltip>
          </v-btn>
        </v-toolbar>
      </template>
      <template #item.fetch_status="{ item }">
        <v-chip :color="statusColor[item.columns.fetch_status]">
          {{ item.columns.fetch_status }}
          <v-tooltip v-if="item.raw.fetched_at" activator="parent">
            {{ prettyDate(item.raw.fetched_at) }}
          </v-tooltip>
        </v-chip>
      </template>
      <template #item.import_status="{ item }">
        <v-chip :color="statusColor[item.columns.import_status]">
          {{ item.columns.import_status }}
        </v-chip>
      </template>
      <template #item.imported_at="{ item }">
        {{ prettyDate(item.columns.imported_at) }}
      </template>
      <template #item.actions="{ item }">
        <v-btn icon variant="plain" @click="fetchChunk(item.raw.id)">
          <v-icon icon="mdi-download" />
          <v-tooltip activator="parent">
            Fetch chunk to local storage
          </v-tooltip>
        </v-btn>
      </template>
    </v-data-table-server>
  </app-layout>
</template>
