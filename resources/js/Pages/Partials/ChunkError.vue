<script setup>
import { computed } from 'vue';

const props = defineProps({
    chunk: { type: Object, required: true },
    stage: { type: String, required: true },
});
defineEmits(['close']);

function textToLines(text) {
    return text.split('\n');
}

const statusTitle = computed(() => (props.stage === 'import' ? 'Import message' : 'Fetch message'));
const statusProp = computed(() => `${props.stage}_status`);
const messageProp = computed(() => `${props.stage}_message`);

</script>

<template>
  <v-card
    v-if="props.chunk[statusProp] === 'failed'"
    elevation="0"
    variant="tonal"
    class="mx-n4 my-2"
  >
    <v-card-title>{{ statusTitle }}</v-card-title>
    <v-card-text>
      <code class="text-red">
        <template v-for="(line, idx) in textToLines(props.chunk[messageProp])" :key="idx">
          {{ line }} <br>
        </template>
      </code>
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn variant="text" text="Close" @click="$emit('close')" />
    </v-card-actions>
  </v-card>
</template>
