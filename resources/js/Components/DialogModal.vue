<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
  <Modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    @close="close"
  >
    <div class="tw-px-6 tw-py-4">
      <div class="tw-text-lg tw-font-medium tw-text-gray-900">
        <slot name="title" />
      </div>

      <div class="tw-mt-4 tw-text-sm tw-text-gray-600">
        <slot name="content" />
      </div>
    </div>

    <div class="tw-flex tw-flex-row tw-justify-end tw-px-6 tw-py-4 tw-bg-gray-100 tw-text-right">
      <slot name="footer" />
    </div>
  </Modal>
</template>
