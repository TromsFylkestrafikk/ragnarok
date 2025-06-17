<script setup>
import axios from 'axios';
import { assign, forEach, reduce, throttle } from 'lodash';
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BatchOperations from '@/Components/BatchOperations.vue';
import useStatus from '@/composables/chunks';
import { permissionProps } from '@/composables/permissions';

const props = defineProps({
    sinks: { type: Object, required: true },
    ...permissionProps,
});

const headers = ref([
    { title: 'Source', key: 'id' },
    { title: 'Chunks', key: 'chunksCount' },
    { title: 'Not imported', key: 'chunksNewCount' },
    { title: 'Failed', key: 'chunksFailedCount' },
    { title: 'Latest imported chunk', key: 'lastImportedChunk.chunk_id' },
    { key: 'actions', sortable: false },
]);
const page = ref(1);
const { statusColor } = useStatus();

const sinksArray = computed(() => reduce(props.sinks, (ret, sink) => {
    ret.push(sink);
    return ret;
}, []));

const sinkIsBusy = ref({});

const canImport = computed(() => {
    const ret = {};
    forEach(props.sinks, (sink) => {
        ret[sink.id] = sink.is_live && sink.newChunks > 0 && !(sinkIsBusy.value[sink.id] ?? false);
    });
    return ret;
});

function rowProps({ item }) {
    return item.is_live ? null : {
        class: [`status-${item.status}`],
    };
}

function importNew(sinkId) {
    sinkIsBusy.value[sinkId] = true;
    return axios.patch(`/api/sinks/${sinkId}/operation`, { operation: 'importNew' });
}

function setSinkStatus(sinkId, status) {
    const sink = props.sinks[sinkId];
    axios.patch(`/api/sinks/${sinkId}`, { status })
        .then((result) => {
            sink.status = result.data.sink.status;
            sink.is_live = result.data.sink.is_live;
        });
}

const refreshSink = throttle((sinkId) => axios.get(`/api/sinks/${sinkId}`).then((result) => {
    // We cannot replace props. Update the individual sink properties
    // directly.
    assign(props.sinks[result.data.sink.id], result.data.sink);
}), 1000);

onMounted(() => {
    useEcho(
        'sinks',
        'ChunkOperationUpdate',
        (event) => refreshSink(event.sinkId).then(() => {
            if (event.batch.finishedAt) {
                sinkIsBusy.value[event.sinkId] = false;
            }
        })
    );
});

</script>

<template>
  <app-layout title="Import status">
    <v-data-table
      :headers="headers"
      :items="sinksArray"
      items-per-page="100"
      item-value="id"
      :row-props="rowProps"
      no-filter
    >
      <template #item.id="{ item, value }">
        <div class="d-flex justify-space-between">
          <Link :href="`/sinks/${value}`">
            {{ item.title }}
          </Link>
          <v-chip v-if="item.newChunks > 0 && item.is_live" color="blue" class="ml-2">
            {{ item.newChunks }} new
          </v-chip>
          <v-icon v-if="! item.is_live" icon="mdi-pause" />
        </div>
      </template>
      <template #item.lastImportedChunk.chunk_id="{ item, value }">
        {{ value }}
        <v-chip v-if="item.lastImportedChunk" :color="statusColor[item.lastImportedChunk.import_status]">
          {{ item.lastImportedChunk.import_status }}
          <v-tooltip activator="parent">
            Imported at {{ item.lastImportedChunk.imported_at }}
          </v-tooltip>
        </v-chip>
      </template>
      <template #item.actions="{ item, value }">
        <v-btn
          v-if="canImport[item.id]"
          icon
          flat
          @click="importNew(item.id)"
        >
          <v-icon icon="mdi-import" />
          <v-tooltip activator="parent">
            Import new chunks
          </v-tooltip>
        </v-btn>
        <v-btn icon variant="plain">
          <v-icon icon="mdi-dots-vertical" />
          <v-menu activator="parent">
            <v-list>
              <v-list-item v-if="item.is_live" append-icon="mdi-pause" @click="setSinkStatus(item.id, 'suspended')">
                <v-list-item-title>Suspend sink</v-list-item-title>
              </v-list-item>
              <v-list-item v-if="!item.is_live" append-icon="mdi-play" @click="setSinkStatus(item.id, 'live')">
                <v-list-item-title>Resume sink</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </v-btn>
        <v-progress-circular v-if="sinkIsBusy[value]" indeterminate />
      </template>
      <template #bottom>
        <div class="text-center pt-2">
          <v-pagination v-model="page" />
        </div>
        <batch-operations :permissions="props.permissions" />
      </template>
    </v-data-table>
  </app-layout>
</template>

<style lang="scss">
  .status-suspended {
    background-color: rgba(128, 128, 128, 0.1);
  }
</style>
