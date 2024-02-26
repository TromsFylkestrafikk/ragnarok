<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import BatchOperations from '@/Components/BatchOperations.vue';
import ChunkError from '@/Pages/Partials/ChunkError.vue';
import ChunkMenu from '@/Pages/Partials/ChunkMenu.vue';
import { permissionProps, usePermissions } from '@/composables/permissions';
import useStatus from '@/composables/chunks';
import {
    computed,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import { filesize } from 'filesize';
import { forEach, debounce } from 'lodash';
import dayjs from 'dayjs';

const props = defineProps({
    sink: { type: Object, required: true },
    ...permissionProps,
});

// -----------------------------------------------------------------------------
// Data table entries
// -----------------------------------------------------------------------------
const headers = ref([
    { title: 'Chunk ID', key: 'chunk_id', sortable: true },
    { title: 'Fetch status', key: 'fetch_status', sortable: true },
    { title: 'Fetch size', key: 'sink_file.size', sortable: true },
    { title: 'Import status', key: 'import_status', sortable: true },
    { title: 'Imported rows', key: 'import_size', sortable: true },
]);

const expanded = ref([]);
const chunks = ref([]);
const chunksCount = ref(0);
const chunksKeyed = computed(() => {
    const ret = {};
    chunks.value.forEach((chunk) => {
        ret[chunk.id] = chunk;
    });
    return ret;
});

// -----------------------------------------------------------------------------
// Operation form and input selection
// -----------------------------------------------------------------------------
const { haveOperations } = usePermissions(props);
const operationItems = ref([
    { value: 'fetch', title: 'Fetch from sink' },
    { value: 'deleteFetched', title: 'Delete fetched' },
    { value: 'import', title: 'Import to DB' },
    { value: 'deleteImported', title: 'Delete imported' },
]);

const execParams = reactive({
    selection: [],
    operation: null,
    forceFetch: false,
    forceImport: false,
    targetSet: 'selection',
});

const selectionCount = computed(() => (execParams.targetSet === 'selection' ? execParams.selection.length : chunksCount.value));
const execForm = ref(null);
const execRules = reactive({
    operation: [
        (value) => !!value || 'Please select an operation',
        (value) => !props.sink.single_state || selectionCount.value < 2 || value !== 'import' || 'Import of several chunks not allowed for single state sinks.',
    ],
    targetSet: [
        (value) => ((value === 'selection' && selectionCount.value === 0)
            ? 'Cannot perform operation on empty selection'
            : true),
        (value) => !!value || 'Please select a target',
    ],
});

const showOp = ref(false);
const showSelectCheckboxes = computed(() => showOp.value && execParams.targetSet === 'selection');

function resetOperationForm() {
    execForm.value.reset();
    execParams.selection = [];
    execParams.forceFetch = false;
    execParams.forceImport = false;
    execParams.targetSet = 'selection';
}

// -----------------------------------------------------------------------------
// Filter form
// -----------------------------------------------------------------------------
const selectStates = ref([
    { value: 'new', title: 'New' },
    { value: 'in_progress', title: 'In progress' },
    { value: 'finished', title: 'Finished' },
    { value: 'failed', title: 'Failed' },
]);

const filterParams = reactive({
    chunk_id: null,
    fetch_status: null,
    fetch_size: null,
    import_status: null,
    import_size: null,
});

function filterInputFactory(param) {
    return debounce((val) => filterParams[param] = val, 600);
}

const filterChunkIdInput = filterInputFactory('chunk_id');
const filterFetchSizeInput = filterInputFactory('fetch_size');
const filterImportSizeInput = filterInputFactory('import_size');

const searchDummy = ref(null);

function touchSearch() {
    searchDummy.value = String(Date.now());
}

// Reducer for triggering <v-data-table-remote :search=..> entry
watch(filterParams, touchSearch);

function clearChunkId() {
    filterChunkIdInput(null);
    filterChunkIdInput.flush();
}

function resetSelection() {
    resetOperationForm();
    clearChunkId();
    filterParams.fetch_status = null;
    filterParams.fetch_size = null;
    filterParams.import_status = null;
    filterParams.import_size = null;
}

const { statusColor } = useStatus();

function getStatusColor(item, stage) {
    const batchColumn = `${stage}_batch`;
    const statusColumn = `${stage}_status`;
    return item[batchColumn] && item[statusColumn] !== 'in_progress' ? 'gray' : statusColor.value[item[statusColumn]];
}

// -----------------------------------------------------------------------------
// Confirmation dialogs and feedback (snackbar)
// -----------------------------------------------------------------------------
const confDiags = reactive({ execOp: false, rmChunk: false, delImport: false });
const targetChunkId = ref(null);

function confirmChunkDeletion(chunkId) {
    targetChunkId.value = chunkId;
    confDiags.rmChunk = true;
}

function confirmImportDeletion(chunkId) {
    targetChunkId.value = chunkId;
    confDiags.delImport = true;
}

const needConfirmation = computed(
    () => ['deleteFetched', 'deleteImported'].includes(execParams.operation) || selectionCount.value >= 20
);

const confirmOpText = computed(() => {
    const destructive = ['deleteFetched', 'deleteImported'].includes(execParams.operation);
    return selectionCount.value > 20
        ? `You are about to perform a ${destructive ? 'destructive' : 'resource hungry'} operation on a large set (${selectionCount.value})`
        : 'This is a destructive operation and will permanetly erase data from storage';
});

const snackProps = reactive({
    color: null,
    location: 'top',
    model: false,
    message: null,
});

// -----------------------------------------------------------------------------
// Helpers.
// -----------------------------------------------------------------------------
function updateChunk(src, dest) {
    forEach(src, (val, key) => {
        dest[key] = val;
    });
}

function prettyDate(dateStr) {
    if (!dateStr) {
        return '';
    }
    return dayjs(dateStr).format('YYYY-MM-DD HH:mm:ss');
}

function toggleExpand(chunk, statusProperty) {
    if (chunk[statusProperty] !== 'failed') {
        return;
    }
    expanded.value = expanded.value.includes(chunk.id) ? [] : [chunk.id];
}

function findAndUpdate(newChunk) {
    const found = chunksKeyed.value[newChunk.id] || null;
    if (found) {
        updateChunk(newChunk, found);
    }
}

function removeFromSelection(idToRemove) {
    const idx = execParams.selection.indexOf(idToRemove);
    if (idx !== -1) {
        execParams.selection.splice(idx, 1);
    }
}

// -----------------------------------------------------------------------------
// Loading and operation execution!
// -----------------------------------------------------------------------------
const loading = ref(true);
const ajaxing = ref(false);

async function loadChunks({ page, itemsPerPage, sortBy }) {
    loading.value = true;
    const state = await axios.get(`/api/sinks/${props.sink.id}/chunks`, {
        params: {
            page,
            itemsPerPage,
            sortBy,
            ...filterParams,
        },
    }).finally(() => loading.value = false);
    chunks.value = state.data.chunks;
    chunksCount.value = state.data.meta.total;
}

function singleChunkOperation(id, operation) {
    ajaxing.value = true;
    return axios.patch(`/api/sinks/${props.sink.id}/chunks/${id}`, { operation })
        .then((result) => findAndUpdate(result.data.chunk))
        .finally(() => ajaxing.value = false);
}

/**
 * Perform actual operation on chunk selection
 */
async function execChunkOperation() {
    ajaxing.value = true;
    return axios.patch(`/api/sinks/${props.sink.id}`, {
        ...filterParams,
        ...execParams,
    }).then((result) => {
        snackProps.color = result.data.status ? null : 'warning';
        snackProps.message = `Server said: ${result.data.message}`;
        snackProps.model = true;
        // Reload chunks to retrieve new state. Mass-updated chunks aren't
        // broadcasted so we need to force it.
        touchSearch();
    }).finally(() => {
        ajaxing.value = false;
        resetOperationForm();
    });
}

async function submitChunkOperation(event) {
    const validation = await event;
    if (!validation.valid) {
        return;
    }
    if (needConfirmation.value) {
        confDiags.execOp = true;
        return;
    }
    execChunkOperation();
}

onMounted(() => {
    Echo.private('App.Models.Chunk').listen('.ChunkUpdated', (event) => {
        findAndUpdate(event.model);
        removeFromSelection(event.model.id);
    });
    Echo.private('sinks').listen('ChunkOperationUpdate', (event) => {
        // Re-load chunks on completed cancellation. Cancelled batches will
        // mass-update chunks which aren't broadcasted.
        if (event.batch.progress >= 100 && event.batch.cancelledAt) {
            touchSearch();
        }
    }).listen('SinkUpdate', (event) => {
        if (event.sinkId === props.sink.id && event.what === 'local-scan-complete') {
            touchSearch();
            snackProps.color = null;
            snackProps.message = event.message;
            snackProps.model = true;
        }
    });
});
</script>

<template>
  <app-layout title="Sink status">
    <v-snackbar v-model="snackProps.model" :color="snackProps.color">
      {{ snackProps.message }}
      <template #actions>
        <v-btn
          variant="text"
          @click="snackProps.model = false"
        >
          OK
        </v-btn>
      </template>
    </v-snackbar>
    <v-data-table-server
      v-model="execParams.selection"
      v-model:expanded="expanded"
      :headers="headers"
      :items="chunks"
      :items-length="chunksCount"
      items-per-page="10"
      :loading="loading"
      :search="searchDummy"
      :show-select="showSelectCheckboxes"
      @update:options="loadChunks"
    >
      <template #top>
        <v-card color="grey-lighten-4" elevation="0">
          <v-toolbar>
            <v-btn icon>
              <v-icon>mdi-menu</v-icon>
              <chunk-menu activator="parent" :sink-id="sink.id" />
            </v-btn>
            <v-toolbar-title>{{ sink.title }}</v-toolbar-title>
            <v-spacer />
            <v-col v-if="showOp" class="text-grey">
              {{ selectionCount }} selected
            </v-col>
            <v-btn v-if="haveOperations" icon @click="showOp = !showOp">
              <v-icon :icon="showOp ? 'mdi-chevron-up' : 'mdi-chevron-down'" />
            </v-btn>
            <v-btn icon variant="plain" @click="resetSelection()">
              <v-icon icon="mdi-select-remove" />
              <v-tooltip activator="parent" location="top">
                Clear all selections and filters
              </v-tooltip>
            </v-btn>
          </v-toolbar>
          <v-expand-transition>
            <v-card-text v-if="haveOperations" v-show="showOp">
              <v-form
                ref="execForm"
                :dislabled="ajaxing"
                validate-on="submit"
                @submit.prevent="submitChunkOperation"
              >
                <v-row align="center">
                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="execParams.operation"
                      clearable
                      label="Operation"
                      :rules="execRules.operation"
                      :items="operationItems"
                    />
                  </v-col>
                  <v-col cols="12" sm="6" class="text-center">
                    <v-btn
                      :loading="ajaxing"
                      type="submit"
                      color="primary"
                      rounded="0"
                      text="Execute!"
                      variant="flat"
                    />
                  </v-col>
                </v-row>
                <v-row>
                  <v-col cols="12" sm="6">
                    <v-radio-group
                      v-model="execParams.targetSet"
                      label="Perform operation on"
                      :rules="execRules.targetSet"
                    >
                      <v-radio
                        :label="`Selected chunks in filtered set (${execParams.selection.length})`"
                        value="selection"
                      />
                      <v-radio :label="`Entire filtered set (${chunksCount})`" value="range" />
                    </v-radio-group>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-switch
                      v-show="['fetch', 'import'].includes(execParams.operation)"
                      v-model="execParams.forceFetch"
                      label="Force re-fetch"
                      color="red"
                      hide-details
                    />
                    <v-switch
                      v-show="execParams.operation === 'import'"
                      v-model="execParams.forceImport"
                      label="Force re-import"
                      color="red"
                      hide-details
                    />
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
          </v-expand-transition>
        </v-card>
        <batch-operations :sink-id="sink.id" :permissions="props.permissions" />
      </template>
      <template #thead>
        <tr>
          <td v-if="showSelectCheckboxes" />
          <td>
            <v-text-field
              :model-value="filterParams.chunk_id"
              append-inner-icon="mdi-magnify"
              class="mr-4 mt-2"
              clearable
              placeholder="E.g >= ID"
              @click:clear="clearChunkId"
              @update:model-value="filterChunkIdInput"
            />
          </td>
          <td>
            <v-select
              v-model="filterParams.fetch_status"
              class="mr-4 mt-2"
              :items="selectStates"
              clearable
            />
          </td>
          <td>
            <v-text-field
              :model-value="filterParams.fetch_size"
              class="mr-4 mt-2"
              placeholder="E.g: < 3000"
              clearable
              @update:model-value="filterFetchSizeInput"
            />
          </td>
          <td>
            <v-select
              v-model="filterParams.import_status"
              class="mr-4 mt-2"
              :items="selectStates"
              clearable
            />
          </td>
          <td>
            <v-text-field
              :model-value="filterParams.import_size"
              class="mr-4 mt-2"
              placeholder="E.g: > 5000 < 10000"
              clearable
              @update:model-value="filterImportSizeInput"
            />
          </td>
        </tr>
      </template>
      <template #item.chunk_id="{ item, value }">
        <a
          v-if="props.permissions.downloadChunks && item.fetch_status === 'finished'"
          :href="`/api/sinks/${item.sink_id}/chunks/${item.id}/download`"
        >
          {{ value }}
        </a>
        <span v-else>
          {{ value }}
        </span>
      </template>
      <template #item.fetch_status="{ item, value }">
        <v-badge :model-value="item.is_modified" color="warning" content="!">
          <v-chip
            :color="getStatusColor(item, 'fetch')"
            :prepend-icon="item.fetch_status === 'failed' ? 'mdi-skull' : null"
            :append-icon="item.fetch_batch ? 'mdi-clock-outline' : null"
            @click="toggleExpand(item, 'fetch_status')"
          >
            {{ value }}
            <v-tooltip v-if="item.fetched_at" activator="parent">
              {{ prettyDate(item.fetched_at) }}
            </v-tooltip>
          </v-chip>
        </v-badge>
        <v-btn
          v-if="props.permissions.operations.fetch && item.need_fetch"
          icon
          variant="plain"
          @click="singleChunkOperation(item.id, 'fetch')"
        >
          <v-icon icon="mdi-tray-arrow-down" />
          <v-tooltip activator="parent">
            Stage 1: Fetch chunk to local storage from sink
          </v-tooltip>
        </v-btn>
        <v-btn
          v-if="props.permissions.operations.deleteFetched && item.can_delete_fetched"
          icon
          variant="plain"
          @click="confirmChunkDeletion(item.id)"
        >
          <v-icon icon="mdi-tray-remove" />
          <v-tooltip activator="parent">
            Stage 1: Remove chunk from local storage
          </v-tooltip>
        </v-btn>
      </template>
      <template #item.sink_file.size="{ item }">
        {{ item.sink_file?.size ? filesize(item.sink_file.size) : '-' }}
      </template>
      <template #item.import_status="{ item }">
        <v-chip
          :color="getStatusColor(item, 'import')"
          :prepend-icon="item.import_status === 'failed' ? 'mdi-skull' : null"
          :append-icon="item.import_batch ? 'mdi-clock-outline' : null"
          @click="toggleExpand(item, 'import_status')"
        >
          {{ item.import_status }}
          <v-tooltip v-if="item.imported_at" activator="parent">
            {{ prettyDate(item.imported_at) }}
          </v-tooltip>
        </v-chip>
        <v-btn
          v-if="props.permissions.operations.import && item.need_import"
          icon
          variant="plain"
          @click="singleChunkOperation(item.id, 'import')"
        >
          <v-icon icon="mdi-database-arrow-down" />
          <v-tooltip activator="parent">
            Stage 2: Import chunk to database
          </v-tooltip>
        </v-btn>
        <v-btn
          v-if="props.permissions.operations.deleteImported && item.can_delete_imported"
          icon
          variant="plain"
          @click="confirmImportDeletion(item.id)"
        >
          <v-icon icon="mdi-database-remove" />
          <v-tooltip activator="parent">
            Stage 2: Delete chunk from database
          </v-tooltip>
        </v-btn>
      </template>
      <template #item.import_size="{ value }">
        {{ value === null ? '-' : Intl.NumberFormat().format(value) }}
      </template>
      <template #expanded-row="{ columns, item }">
        <tr>
          <td :colspan="columns.length">
            <chunk-error :chunk="item" stage="fetch" @close="expanded = []" />
            <chunk-error :chunk="item" stage="import" @close="expanded = []" />
          </td>
        </tr>
      </template>
    </v-data-table-server>

    <confirm-dialog v-model="confDiags.rmChunk" @confirmed="singleChunkOperation(targetChunkId, 'deleteFetched')">
      <p>This will permamently remove local copy of raw, stage 1 data.</p>
      <p>Are you sure you want to erase it?</p>
    </confirm-dialog>
    <confirm-dialog v-model="confDiags.delImport" @confirmed="singleChunkOperation(targetChunkId, 'deleteImported')">
      <p>This will delete the processed, imported data from database storage.</p>
      <p>Proceed?</p>
    </confirm-dialog>
    <confirm-dialog v-model="confDiags.execOp" @confirmed="execChunkOperation">
      <p>{{ confirmOpText }}</p>
      <p>Really continue?</p>
    </confirm-dialog>
  </app-layout>
</template>
