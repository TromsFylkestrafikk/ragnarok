<script setup>
import { ref, watch } from 'vue';
import SinkSchema from '@/Pages/Partials/SinkSchema.vue';

const props = defineProps({
    sinkId: { type: String, required: true },
    activator: { type: [String, Object], default: undefined },
});

const menuValue = ref(false);
const schemas = ref(null);
const ajaxing = ref(false);

function getSchemas() {
    ajaxing.value = true;
    axios.get(`/api/sinks/${props.sinkId}/schemas`)
        .then((result) => schemas.value = result.data.schemas)
        .finally(() => ajaxing.value = false);
}

watch(menuValue, (newVal) => {
    if (newVal && schemas.value === null && !ajaxing.value) {
        getSchemas();
    }
});

const table = ref(null);
const schemaDialog = ref(false);

function showSchema(tableName) {
    menuValue.value = false;
    table.value = tableName;
    schemaDialog.value = true;
}

function scanLocalFiles() {
    menuValue.value = false;
    axios.get(`/api/sinks/${props.sinkId}/scan`);
}

</script>

<template>
  <v-menu v-model="menuValue" :activator="activator" :close-on-content-click="false">
    <template #activator="slotProps">
      <slot name="activator" v-bind="slotProps ?? {}" />
    </template>
    <v-list>
      <v-list-group>
        <template #activator="{ props }">
          <v-list-item v-bind="props" title="View schemas" />
        </template>
        <v-list-item
          v-for="desc, tableName in schemas"
          :key="tableName"
          :title="tableName"
          prepend-icon="mdi-table"
          @click="showSchema(tableName)"
        />
      </v-list-group>
      <v-list-item title="Scan local disk for existing files" @click="scanLocalFiles" />
    </v-list>
  </v-menu>

  <v-dialog v-model="schemaDialog" max-width="90%" max-height="80%">
    <v-card>
      <v-toolbar color="primary">
        <v-toolbar-title>Schema for table '{{ table }}'</v-toolbar-title>
        <v-spacer />
        <v-btn icon="mdi-close" @click="schemaDialog = false" />
      </v-toolbar>
      <v-card-subtitle class="py-4">
        {{ schemas[table] }}
      </v-card-subtitle>
      <v-card-text>
        <sink-schema :sink-id="sinkId" :table="table" />
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
