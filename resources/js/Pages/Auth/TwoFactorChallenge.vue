<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppAnon from '@/Layouts/AppAnon.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value = !recovery.value;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
  <AppAnon title="Two-factor Confirmation">
    <div class="tw-mb-4 tw-text-sm tw-text-gray-600">
      <template v-if="! recovery">
        Please confirm access to your account by entering the authentication code provided by your Authenticator app.
      </template>

      <template v-else>
        Please confirm access to your account by entering one of your emergency recovery codes.
      </template>
    </div>

    <form @submit.prevent="submit">
      <div v-if="! recovery">
        <InputLabel for="code" value="Code" />
        <TextInput
          id="code"
          ref="codeInput"
          v-model="form.code"
          type="text"
          inputmode="numeric"
          style="border: none;"
          autofocus
          autocomplete="one-time-code"
        />
        <InputError class="tw-mt-2" :message="form.errors.code" />
      </div>

      <div v-else>
        <InputLabel for="recovery_code" value="Recovery Code" />
        <TextInput
          id="recovery_code"
          ref="recoveryCodeInput"
          v-model="form.recovery_code"
          type="text"
          style="border: none;"
          autocomplete="one-time-code"
        />
        <InputError class="tw-mt-2" :message="form.errors.recovery_code" />
      </div>

      <div class="tw-flex tw-items-center tw-justify-end tw-mt-4">
        <button
          type="button"
          class="tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-underline tw-cursor-pointer"
          style="border: none;"
          @click.prevent="toggleRecovery"
        >
          <template v-if="! recovery">
            Use a recovery code
          </template>

          <template v-else>
            Use an authentication code
          </template>
        </button>

        <PrimaryButton class="tw-ml-4" :class="{ 'tw-opacity-25': form.processing }" :disabled="form.processing">
          Log in
        </PrimaryButton>
      </div>
    </form>
  </AppAnon>
</template>
