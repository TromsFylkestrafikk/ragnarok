<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
    createApiTokenForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null),
    });
};
</script>

<template>
  <div>
    <!-- Generate API Token -->
    <FormSection @submitted="createApiToken">
      <template #title>
        Create API Token
      </template>

      <template #description>
        API tokens allow third-party services to authenticate with our application on your behalf.
      </template>

      <template #form>
        <!-- Token Name -->
        <div class="tw-col-span-6 sm:tw-col-span-4">
          <InputLabel for="name" value="Name" />
          <TextInput
            id="name"
            v-model="createApiTokenForm.name"
            type="text"
            class="tw-mt-1 tw-block tw-w-full"
            autofocus
          />
          <InputError :message="createApiTokenForm.errors.name" class="tw-mt-2" />
        </div>

        <!-- Token Permissions -->
        <div v-if="availablePermissions.length > 0" class="tw-col-span-6">
          <InputLabel for="permissions" value="Permissions" />

          <div class="tw-mt-2 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div v-for="permission in availablePermissions" :key="permission">
              <label class="tw-flex tw-items-center">
                <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                <span class="tw-ml-2 tw-text-sm tw-text-gray-600">{{ permission }}</span>
              </label>
            </div>
          </div>
        </div>
      </template>

      <template #actions>
        <ActionMessage :on="createApiTokenForm.recentlySuccessful" class="tw-mr-3">
          Created.
        </ActionMessage>

        <PrimaryButton :class="{ 'tw-opacity-25': createApiTokenForm.processing }" :disabled="createApiTokenForm.processing">
          Create
        </PrimaryButton>
      </template>
    </FormSection>

    <div v-if="tokens.length > 0">
      <SectionBorder />

      <!-- Manage API Tokens -->
      <div class="tw-mt-10 sm:tw-mt-0">
        <ActionSection>
          <template #title>
            Manage API Tokens
          </template>

          <template #description>
            You may delete any of your existing tokens if they are no longer needed.
          </template>

          <!-- API Token List -->
          <template #content>
            <div class="tw-space-y-6">
              <div v-for="token in tokens" :key="token.id" class="tw-flex tw-items-center tw-justify-between">
                <div class="break-all">
                  {{ token.name }}
                </div>

                <div class="tw-flex tw-items-center tw-ml-2">
                  <div v-if="token.last_used_ago" class="text-sm text-gray-400">
                    Last used {{ token.last_used_ago }}
                  </div>

                  <button
                    v-if="availablePermissions.length > 0"
                    class="tw-cursor-pointer tw-ml-6 tw-text-sm tw-text-gray-400 tw-underline"
                    @click="manageApiTokenPermissions(token)"
                  >
                    Permissions
                  </button>

                  <button
                    class="tw-cursor-pointer tw-ml-6 tw-text-sm tw-text-red-500"
                    @click="confirmApiTokenDeletion(token)"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </template>
        </ActionSection>
      </div>
    </div>

    <!-- Token Value Modal -->
    <DialogModal :show="displayingToken" @close="displayingToken = false">
      <template #title>
        API Token
      </template>

      <template #content>
        <div>
          Please copy your new API token. For your security, it won't be shown again.
        </div>

        <div
          v-if="$page.props.jetstream.flash.token"
          class="tw-mt-4 tw-bg-gray-100 tw-px-4 tw-py-2 tw-rounded tw-font-mono tw-text-sm tw-text-gray-500 tw-break-all"
        >
          {{ $page.props.jetstream.flash.token }}
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="displayingToken = false">
          Close
        </SecondaryButton>
      </template>
    </DialogModal>

    <!-- API Token Permissions Modal -->
    <DialogModal :show="managingPermissionsFor != null" @close="managingPermissionsFor = null">
      <template #title>
        API Token Permissions
      </template>

      <template #content>
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div v-for="permission in availablePermissions" :key="permission">
            <label class="tw-flex tw-items-center">
              <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
              <span class="tw-ml-2 tw-text-sm tw-text-gray-600">{{ permission }}</span>
            </label>
          </div>
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="managingPermissionsFor = null">
          Cancel
        </SecondaryButton>

        <PrimaryButton
          class="tw-ml-3"
          :class="{ 'tw-opacity-25': updateApiTokenForm.processing }"
          :disabled="updateApiTokenForm.processing"
          @click="updateApiToken"
        >
          Save
        </PrimaryButton>
      </template>
    </DialogModal>

    <!-- Delete Token Confirmation Modal -->
    <ConfirmationModal :show="apiTokenBeingDeleted != null" @close="apiTokenBeingDeleted = null">
      <template #title>
        Delete API Token
      </template>

      <template #content>
        Are you sure you would like to delete this API token?
      </template>

      <template #footer>
        <SecondaryButton @click="apiTokenBeingDeleted = null">
          Cancel
        </SecondaryButton>

        <DangerButton
          class="tw-ml-3"
          :class="{ 'tw-opacity-25': deleteApiTokenForm.processing }"
          :disabled="deleteApiTokenForm.processing"
          @click="deleteApiToken"
        >
          Delete
        </DangerButton>
      </template>
    </ConfirmationModal>
  </div>
</template>
