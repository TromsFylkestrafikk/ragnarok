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

function cancelBatch(batchId) {
    axios.delete(`/api/batch/${batchId}`).then(() => batches[batchId].finishedAt = true);
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
            batches[event.batch.id] = event.batch;
            if (event.batch.finishedAt && event.batch.progress === 100) {
                setTimeout(() => delete batches[event.batch.id], 5000);
            }
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
            <v-progress-linear :model-value="batch.progress" height="35" color="amber">
              {{ batch.processedJobs }} / {{ batch.totalJobs }} ({{ batch.progress }} %)
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
