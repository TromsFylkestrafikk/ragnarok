<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['refresh', 'error', 'close']);

const props = defineProps({
    user: Object,
    roles: Array,
    permissions: Array,
});

const userRole = ref(props.user.role);

const roleChanged = computed(() => userRole.value !== props.user.role);

const saveUserRole = () => {
    axios.put(`/account/${props.user.id}`, { newRole: userRole.value }).then((response) => {
        if (!response.data.success) {
            emit('error', `ERROR ${response.data.errorCode}: ${response.data.errorMsg}`);
        }
    }).catch((error) => emit('error', `ERROR: ${error.message}`))
        .finally(() => emit('refresh'));
};
</script>

<template>
  <v-card :title="`Edit user role - ${props.user.name} (${props.user.mail})`">
    <v-card-text>
      <v-container>
        <v-row>
          <v-col>
            <v-radio-group v-model="userRole" label="User role" class="mt-4">
              <v-radio
                v-for="role in props.roles"
                :key="role.id"
                :label="role.name"
                :value="role.name"
                class="text-capitalize"
              />
            </v-radio-group>
          </v-col>
          <v-col>
            <v-table
              density="comfortable"
              style="min-width: 500px;"
              fixed-header
            >
              <thead>
                <tr style="--v-theme-surface: 235 235 235;">
                  <th class="font-italic font-weight-black">
                    Permissions
                  </th>
                  <th v-for="role in props.roles" :key="role.id" class="font-italic font-weight-black text-capitalize">
                    {{ role.name }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(permission, index) in props.permissions" :key="index">
                  <td class="text-capitalize">
                    {{ permission[0] }}
                  </td>
                  <td v-for="(role, i) in props.roles" :key="role.id" class="text-center">
                    <v-icon v-if="permission[i + 1]" icon="mdi-check-bold" />
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-col>
        </v-row>
      </v-container>
    </v-card-text>
    <v-card-actions class="mr-6 mb-2">
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
        class="mr-6"
        color="primary"
        :disabled="!roleChanged"
        variant="elevated"
        @click="saveUserRole"
      >
        Save changes
      </v-btn>
    </v-card-actions>
  </v-card>
</template>
