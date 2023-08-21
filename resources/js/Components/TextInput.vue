<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <v-text-field
        ref="input"
        :value="modelValue"
        density="compact"
        variant="solo-filled"
        style="border: black 2px solid;"
        hide-details
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
