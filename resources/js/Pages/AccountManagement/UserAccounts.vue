<script setup>
import { ref, computed, onBeforeMount } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddUserAccount from '@/Pages/AccountManagement/AddUserAccount.vue';
import EditUserRoles from '@/Pages/AccountManagement/EditUserRoles.vue';
import DeleteAccount from '@/Pages/AccountManagement/DeleteAccount.vue';

const users = ref([]);
const ownId = ref(-1);
const selectedUser = ref(null);
const errorMessage = ref(null);
const showErrorMsg = ref(false);
const editRoleDialog = ref(false);
const deleteUserDialog = ref(false);
const addUserDialog = ref(false);
const roles = ref(null);
const roleNames = ref([]);
const permissions = ref([]);
const canEditUsers = ref(false);
const canCreateUsers = ref(false);
const canDeleteUsers = ref(false);

const showDialog = computed(() => editRoleDialog.value || deleteUserDialog.value || addUserDialog.value);

const closeDialog = () => {
    editRoleDialog.value = false;
    deleteUserDialog.value = false;
    addUserDialog.value = false;
};

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

const getAllUserAccounts = () => {
    axios.get('/api/usersWithRoles').then((response) => {
        users.value = response.data.users;
        ownId.value = response.data.userId;
        roleNames.value = response.data.roles;
        canEditUsers.value = response.data.canEditUsers;
        canCreateUsers.value = response.data.canCreateUsers;
        canDeleteUsers.value = response.data.canDeleteUsers;
    }).catch((error) => handleErrorResponse(error))
        .finally(() => closeDialog());
};

onBeforeMount(() => {
    getAllUserAccounts();
});

const showRoleDlg = (id) => {
    axios.get('/api/userRolesWithPermissions').then((response) => {
        roles.value = response.data.roles;
        permissions.value = response.data.permissions;
        selectedUser.value = users.value.find((user) => user.id === id);
        editRoleDialog.value = true;
    }).catch((error) => handleErrorResponse(error));
};

const showDeleteAccountDlg = (id) => {
    selectedUser.value = users.value.find((user) => user.id === id);
    deleteUserDialog.value = true;
};

const showErrorMessage = (msg) => {
    errorMessage.value = msg;
    showErrorMsg.value = true;
};
</script>

<template>
  <AppLayout title="User accounts">
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
        v-model="showDialog"
        width="850px"
        persistent
      >
        <!-- Add user account -->
        <add-user-account
          v-if="addUserDialog"
          :roles="roleNames"
          @close="addUserDialog = false"
          @error="showErrorMessage"
          @refresh="getAllUserAccounts"
        />

        <!-- Edit user role -->
        <edit-user-roles
          v-if="editRoleDialog"
          :user="selectedUser"
          :roles="roles"
          :permissions="permissions"
          @close="editRoleDialog = false"
          @error="showErrorMessage"
          @refresh="getAllUserAccounts"
        />

        <!-- Delete user account -->
        <delete-account
          v-if="deleteUserDialog"
          :user="selectedUser"
          @close="deleteUserDialog = false"
          @error="showErrorMessage"
          @refresh="getAllUserAccounts"
        />
      </v-dialog>

      <v-container>
        <v-row v-if="canCreateUsers" justify="end">
          <v-col cols="auto" class="px-0">
            <v-btn color="success" @click="addUserDialog = true">
              <v-icon icon="mdi-account-plus" class="mr-4" />
              Add user account
            </v-btn>
          </v-col>
        </v-row>
        <v-row>
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
                <th />
              </tr>
            </thead>
            <tbody>
              <tr v-if="users.length === 0">
                <td>No users found.</td>
              </tr>
              <tr v-for="user in users" :key="user.id">
                <td>
                  {{ user.name }}
                </td>
                <td>
                  {{ user.mail }}
                </td>
                <td class="text-capitalize">
                  {{ user.role }}
                </td>
                <td class="px-0">
                  <v-btn
                    v-if="canEditUsers"
                    :disabled="user.id === ownId"
                    variant="text"
                    icon
                    @click="showRoleDlg(user.id)"
                  >
                    <v-icon icon="mdi-account-edit-outline" />
                    <v-tooltip activator="parent" class="custom-tooltip">
                      Edit user role
                    </v-tooltip>
                  </v-btn>
                  <v-btn
                    v-if="canDeleteUsers"
                    :disabled="user.id === ownId"
                    variant="text"
                    icon
                    @click="showDeleteAccountDlg(user.id)"
                  >
                    <v-icon icon="mdi-delete-forever" />
                    <v-tooltip activator="parent" class="custom-tooltip">
                      Delete account
                    </v-tooltip>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-row>
      </v-container>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .custom-tooltip :deep(.v-overlay__content) {
    background: rgba(66, 66, 66, 1) !important;
  }
</style>
