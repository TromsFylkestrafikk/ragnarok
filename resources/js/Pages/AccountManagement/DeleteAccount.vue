<script setup>
import { ref } from 'vue';

const emit = defineEmits(['refresh', 'error', 'close']);

const props = defineProps({
    user: {
        type: Object,
        required: true,
        default() {
            return {};
        },
    },
});

const loading = ref(false);
const notification = ref(true);

const deleteUserAccount = () => {
    loading.value = true;
    const payload = { notify: notification.value };
    axios.delete(`/account/${props.user.id}`, { data: payload })
        .catch((error) => emit('error', `ERROR: ${error.message}`))
        .finally(() => emit('refresh'));
};
</script>

<template>
  <v-card :title="`Delete user account - ${props.user.name} (${props.user.mail})`">
    <v-card-text>
      The selected user account will be deleted. Please confirm.
      <v-checkbox
        v-model="notification"
        label="Send notification e-mail to user"
        style="max-width: max-content;"
        class="mt-4"
        hide-details
      />
    </v-card-text>
    <v-card-actions class="mr-6">
      <v-spacer />
      <v-btn
        class="mr-6"
        color="secondary"
        variant="elevated"
        @click="$emit('close')"
      >
        Cancel
      </v-btn>
      <v-btn
        :disabled="!props.user.id"
        :loading="loading"
        class="mr-6"
        color="primary"
        variant="elevated"
        @click="deleteUserAccount"
      >
        Delete account
      </v-btn>
    </v-card-actions>
  </v-card>
</template>
