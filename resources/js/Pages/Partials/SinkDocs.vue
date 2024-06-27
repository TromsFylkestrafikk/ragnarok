<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    sinkId: { type: String, required: true },
});

const docs = ref();

onMounted(async () => {
    const result = await axios.get(`/api/sinks/${props.sinkId}/getDoc`);
    docs.value = result.status === 200 ? result.data : 'No documentation provided';
});
</script>

<template>
  <!-- eslint-disable vue/no-v-html -->
  <div v-html="docs" />
  <!-- eslint-enable -->
</template>
