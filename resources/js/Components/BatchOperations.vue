<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { reduce, forEach } from 'lodash';
import { useEcho } from '@laravel/echo-vue';
import { Link } from '@inertiajs/vue3';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { permissionProps } from '@/composables/permissions';

const props = defineProps({
    sinkId: { type: String, default: null },
    ...permissionProps,
});
const batches = reactive({});
const hasBatches = computed(() => reduce(batches, () => true, false));

function replaceBatch(batch) {
    batches[batch.id] = batch;
    if (batch.finishedAt) {
        setTimeout(() => delete batches[batch.id], 5000);
    }
}

const confirmDiag = ref(false);
const confirmBatchId = ref(null);
function openConfirm(batch) {
    confirmDiag.value = true;
    confirmBatchId.value = batch.id;
}

function cancelBatch() {
    axios
        .delete(`/api/batch/${confirmBatchId.value}`)
        .then((result) => replaceBatch(result.data.batch));
}

function progressBarContent(batch) {
    if (batch.cancelledAt) {
        return 'Cancelled ...';
    }
    const failed = batch.failedJobs ? `Failed: ${batch.failedJobs}` : '';
    return `${batch.processedJobs} / ${batch.totalJobs} (${batch.progress} %) ${failed}`;
}

function progressBarColor(batch) {
    if (batch.failedJobs) {
        return 'red';
    }
    if (batch.progress >= 100) {
        return 'green';
    }
    return 'amber';
}

useEcho('sinks', 'ChunkOperationUpdate', (event) => {
    if (event.batch.totalJobs < 2) {
        return;
    }
    if (props.sinkId && !event.batch.name.startsWith(`${props.sinkId}: `)) {
        return;
    }
    replaceBatch(event.batch);
});

onMounted(() => {
    axios
        .get('/api/batch', {
            params: props.sinkId ? { sinkId: props.sinkId } : {},
        })
        .then((result) => {
            forEach(result.data, (batch) => {
                batches[batch.id] = batch;
            });
        });
});
</script>

<template>
  <v-slide-y-transition>
    <v-card v-if="hasBatches" elevation="0" class="my-6">
      <v-card-title>Currently running operations</v-card-title>
      <v-card-text>
        <v-row v-for="batch in batches" :key="batch.id" align="center">
          <v-col :cols="props.permissions.deleteBatches ? 8 : 10">
            <v-progress-linear :model-value="batch.progress" height="35" :color="progressBarColor(batch)">
              {{ progressBarContent(batch) }}
            </v-progress-linear>
          </v-col>
          <v-col cols="2">
            <component :is="props.sinkId ? 'span' : Link" :href="props.sinkId ? null : `/sinks/${batch.sink_id}`">
              {{ batch.name }}
            </component>
          </v-col>
          <v-col v-if="props.permissions.deleteBatches" cols="2">
            <v-btn
              variant="text"
              :disabled="batch.finishedAt"
              @click="openConfirm(batch)"
            >
              Cancel
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-slide-y-transition>
  <confirm-dialog v-model="confirmDiag" @confirmed="cancelBatch">
    Confirm operation abort
  </confirm-dialog>
</template>
