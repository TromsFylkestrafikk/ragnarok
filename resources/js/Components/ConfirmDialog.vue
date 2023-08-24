<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    activator: { type: [String, Object], default: undefined },
});
const emit = defineEmits(['update:modelValue', 'confirmed']);

function confirm() {
    emit('confirmed');
    emit('update:modelValue', false);
}
</script>

<template>
  <v-dialog
    :activator="activator"
    width="auto"
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #activator="slotProps">
      <slot name="activator" v-bind="slotProps ?? {}" />
    </template>
    <template #default>
      <v-card>
        <v-card-text>
          <slot />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="$emit('update:modelValue', false)">
            Cancel
          </v-btn>
          <v-btn color="primary" @click="confirm()">
            OK
          </v-btn>
        </v-card-actions>
      </v-card>
    </template>
  </v-dialog>
</template>
