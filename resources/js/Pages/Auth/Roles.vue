<script setup>
import { ref, computed, onBeforeMount } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const users = ref([]);
const selectedUser = ref(null);
const selectedRole = ref(null);
const errorMessage = ref(null);
const showErrorMsg = ref(false);
const dialog = ref(false);
const roles = ref([]);
const adminCount = ref(0);
const permissions = ref([]);
const canEditUsers = ref(false);

const isLastAdmin = computed(() => (selectedUser.value.role === 'admin') && (adminCount.value === 1));
const canEditRole = computed(() => canEditUsers.value && !isLastAdmin.value);
const roleChanged = computed(() => selectedRole.value !== selectedUser.value.role);

const handleErrorResponse = (error) => {
    if (error.response) {
        errorMessage.value = `ERROR ${error.response.status}: ${error.response.statusText}`;
    } else if (error.request) {
        errorMessage.value = `ERROR: ${error.request.statusText}`;
    } else {
        errorMessage.value = `ERROR: ${error.message}`;
    }
    showErrorMsg.value = true;
};

onBeforeMount(() => {
    axios.get('/api/usersWithRoles').then((response) => {
        users.value = response.data.users;
        adminCount.value = response.data.admins;
        canEditUsers.value = response.data.canEditUsers;
    }).catch((error) => handleErrorResponse(error));
});

const showRoleDlg = (id) => {
    axios.get('/api/userRolesWithPermissions').then((response) => {
        roles.value = response.data.roles;
        permissions.value = response.data.permissions;
        selectedUser.value = users.value.find((user) => user.id === id);
        selectedRole.value = selectedUser.value.role;
        dialog.value = true;
    }).catch((error) => handleErrorResponse(error));
};

const saveUserRole = () => {
    axios.post(`/api/updateUserRole/${selectedUser.value.id}/${selectedRole.value}`).then((response) => {
        if (response.data.success) {
            adminCount.value = response.data.admins;
            canEditUsers.value = response.data.canEditUsers;
            selectedUser.value.role = selectedRole.value;
        }
    }).catch((error) => handleErrorResponse(error))
        .finally(() => dialog.value = false);
};
</script>

<template>
  <AppLayout title="User roles">
    <div>
      <v-snackbar
        v-model="showErrorMsg"
        location="top"
        color="error"
        timeout="-1"
        multi-line
      >
        <p class="text-body-1 pa-4">
          {{ errorMessage }}
        </p>
        <template #actions>
          <v-btn
            color="black"
            class="font-weight-bold mr-4"
            variant="text"
            @click="showErrorMsg = false"
          >
            Close
          </v-btn>
        </template>
      </v-snackbar>

      <v-dialog
        v-model="dialog"
        width="850px"
        persistent
      >
        <v-card :title="`Role and permissions - ${selectedUser.name} (${selectedUser.mail})`">
          <v-card-text>
            <v-container>
              <v-row>
                <v-col>
                  <v-radio-group
                    v-model="selectedRole"
                    label="User role"
                    class="mt-4"
                    :disabled="!canEditRole"
                  >
                    <v-radio
                      v-for="role in roles"
                      :key="role.id"
                      :label="role.name"
                      :value="role.name"
                      class="text-capitalize"
                    />
                  </v-radio-group>
                  <p v-if="!canEditUsers" class="text-red font-weight-bold">
                    Cannot edit user role due to insufficient privileges.
                  </p>
                  <p v-else-if="isLastAdmin" class="text-red font-weight-bold">
                    Cannot edit user role of the last remaining admin.
                  </p>
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
                        <th v-for="role in roles" :key="role.id" class="font-italic font-weight-black text-capitalize">
                          {{ role.name }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(permission, index) in permissions" :key="index">
                        <td class="text-capitalize">
                          {{ permission[0] }}
                        </td>
                        <td v-for="(role, i) in roles" :key="role.id" class="text-center">
                          <v-icon v-if="permission[i + 1]" icon="mdi-check-bold" />
                        </td>
                      </tr>
                    </tbody>
                  </v-table>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
          <v-card-actions class="mr-6">
            <v-spacer />
            <v-btn
              class="mr-6"
              color="secondary"
              @click="dialog = false"
            >
              Cancel
            </v-btn>
            <v-btn
              class="mr-6"
              color="primary"
              :disabled="!roleChanged"
              @click="saveUserRole"
            >
              Save
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-table
        fixed-header
        style="min-width: 800px;"
      >
        <thead>
          <tr style="--v-theme-surface: 215 215 215;">
            <th class="font-italic font-weight-black">
              Name
            </th>
            <th class="font-italic font-weight-black">
              E-mail
            </th>
            <th class="font-italic font-weight-black">
              Role
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="users.length === 0">
            <td>No users found.</td>
          </tr>
          <tr v-for="user in users" :key="user.id" @click="showRoleDlg(user.id)">
            <td style="cursor: pointer;">
              {{ user.name }}
            </td>
            <td style="cursor: pointer;">
              {{ user.mail }}
            </td>
            <td class="text-capitalize" style="cursor: pointer;">
              {{ user.role }}
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>
  </AppLayout>
</template>
