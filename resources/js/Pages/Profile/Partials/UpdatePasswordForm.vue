<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
  <FormSection @submitted="updatePassword">
    <template #title>
      Update Password
    </template>

    <template #description>
      Ensure your account is using a long, random password to stay secure.
    </template>

    <template #form>
      <div class="tw-col-span-6 sm:tw-col-span-4">
        <InputLabel for="current_password" value="Current Password" />
        <v-text-field
          id="current_password"
          ref="currentPasswordInput"
          v-model="form.current_password"
          type="password"
          density="compact"
          autocomplete="current-password"
          hide-details
        />
        <InputError :message="form.errors.current_password" class="tw-mt-2" />
      </div>

      <div class="tw-col-span-6 sm:tw-col-span-4">
        <InputLabel for="password" value="New Password" />
        <v-text-field
          id="password"
          ref="passwordInput"
          v-model="form.password"
          type="password"
          density="compact"
          autocomplete="new-password"
          hide-details
        />
        <InputError :message="form.errors.password" class="tw-mt-2" />
      </div>

      <div class="tw-col-span-6 sm:tw-col-span-4">
        <InputLabel for="password_confirmation" value="Confirm Password" />
        <v-text-field
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          density="compact"
          autocomplete="new-password"
          hide-details
        />
        <InputError :message="form.errors.password_confirmation" class="tw-mt-2" />
      </div>
    </template>

    <template #actions>
      <ActionMessage :on="form.recentlySuccessful" class="tw-mr-3">
        Saved.
      </ActionMessage>

      <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        Save
      </PrimaryButton>
    </template>
  </FormSection>
</template>
