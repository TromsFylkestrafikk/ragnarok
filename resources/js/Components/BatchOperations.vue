<script setup>
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { permissionProps } from '@/composables/permissions';
import { computed, onMounted, reactive, ref } from 'vue';
import { reduce, forEach } from 'lodash';

const props = defineProps({
    sinkId: { type: String, default: null },
    ...permissionProps,
});
const batches = reactive({});
const hasBatches = computed(() => reduce(batches, () => true, false));
const confirmDiag = ref(false);

function replaceBatch(batch) {
    batches[batch.id] = batch;
    if (batch.finishedAt) {
        setTimeout(() => delete batches[batch.id], 5000);
    }
}

function cancelBatch(batchId) {
    axios.delete(`/api/batch/${batchId}`).then((result) => replaceBatch(result.data.batch));
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

onMounted(() => {
    axios.get('/api/batch', { params: props.sinkId ? { sinkId: props.sinkId } : {} }).then((result) => {
        forEach(result.data, (batch) => {
            batches[batch.id] = batch;
        });
    });

    Echo.private('sinks').listen(
        'ChunkOperationUpdate',
        (event) => {
            if (event.batch.totalJobs < 2) {
                return;
            }
            if (props.sinkId && !event.batch.name.startsWith(`${props.sinkId}: `)) {
                return;
            }
            replaceBatch(event.batch);
        }
    );
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
            {{ batch.name }}
          </v-col>
          <v-col v-if="props.permissions.deleteBatches" cols="2">
            <v-btn
              variant="text"
              :disabled="batch.finishedAt"
            >
              Cancel
              <confirm-dialog v-model="confirmDiag" activator="parent" @confirmed="cancelBatch(batch.id)">
                Confirm operation abort
              </confirm-dialog>
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-slide-y-transition>
</template>
