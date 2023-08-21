<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    requiresConfirmation: Boolean,
});

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const confirmationForm = useForm({
    code: '',
});

const twoFactorEnabled = computed(
    () => !enabling.value && usePage().props.auth.user?.two_factor_enabled
);

watch(twoFactorEnabled, () => {
    if (!twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const showQrCode = () => axios.get(route('two-factor.qr-code')).then((response) => {
    qrCode.value = response.data.svg;
});

const showSetupKey = () => axios.get(route('two-factor.secret-key')).then((response) => {
    setupKey.value = response.data.secretKey;
});

const showRecoveryCodes = () => axios.get(route('two-factor.recovery-codes')).then((response) => {
    recoveryCodes.value = response.data;
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;

    router.post(route('two-factor.enable'), {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
        },
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(route('two-factor.confirm'), {
        errorBag: 'confirmTwoFactorAuthentication',
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post(route('two-factor.recovery-codes'))
        .then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;

    router.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
        },
    });
};
</script>

<template>
  <ActionSection>
    <template #title>
      Two Factor Authentication
    </template>

    <template #description>
      Add additional security to your account using two factor authentication.
    </template>

    <template #content>
      <h3 v-if="twoFactorEnabled && ! confirming" class="tw-text-lg tw-font-medium tw-text-gray-900">
        You have enabled two factor authentication.
      </h3>

      <h3 v-else-if="twoFactorEnabled && confirming" class="tw-text-lg tw-font-medium tw-text-gray-900">
        Finish enabling two factor authentication.
      </h3>

      <h3 v-else class="tw-text-lg tw-font-medium tw-text-gray-900">
        You have not enabled two factor authentication.
      </h3>

      <div class="tw-mt-3 tw-max-w-xl tw-text-sm tw-text-gray-600">
        <p>
          When two factor authentication is enabled, you will be prompted for a secure, random token during authentication.
          You may retrieve this token from your phone's Authenticator app.
        </p>
      </div>

      <div v-if="twoFactorEnabled">
        <div v-if="qrCode">
          <div class="tw-mt-4 tw-max-w-xl tw-text-sm tw-text-gray-600">
            <p v-if="confirming" class="tw-font-semibold">
              To finish enabling two factor authentication, scan the following
              QR code using your phone's authenticator application or enter the
              setup key and provide the generated OTP code.
            </p>

            <p v-else>
              Two factor authentication is now enabled. Scan the following QR
              code using your phone's authenticator application or enter the
              setup key.
            </p>
          </div>

          <div class="tw-mt-4" v-html="qrCode" />

          <div v-if="setupKey" class="tw-mt-4 tw-max-w-xl tw-text-sm tw-text-gray-600">
            <p class="tw-font-semibold">
              Setup Key:
              <span v-html="setupKey" />
            </p>
          </div>

          <div v-if="confirming" class="tw-mt-4">
            <InputLabel for="code" value="Code" />

            <TextInput
              id="code"
              v-model="confirmationForm.code"
              type="text"
              name="code"
              class="tw-block tw-mt-1 tw-w-1/2"
              inputmode="numeric"
              autofocus
              autocomplete="one-time-code"
              @keyup.enter="confirmTwoFactorAuthentication"
            />

            <InputError :message="confirmationForm.errors.code" class="tw-mt-2" />
          </div>
        </div>

        <div v-if="recoveryCodes.length > 0 && ! confirming">
          <div class="tw-mt-4 tw-max-w-xl tw-text-sm tw-text-gray-600">
            <p class="tw-font-semibold">
              Store these recovery codes in a secure password manager. They can
              be used to recover access to your account if your two factor
              authentication device is lost.
            </p>
          </div>

          <div class="tw-grid tw-gap-1 tw-max-w-xl tw-mt-4 tw-px-4 tw-py-4 tw-font-mono tw-text-sm tw-bg-gray-100 tw-rounded-lg">
            <div v-for="code in recoveryCodes" :key="code">
              {{ code }}
            </div>
          </div>
        </div>
      </div>

      <div class="tw-mt-5">
        <div v-if="!twoFactorEnabled">
          <ConfirmsPassword title="Enable Two Factor Authentication" @confirmed="enableTwoFactorAuthentication">
            <PrimaryButton type="button" :class="{ 'tw-opacity-25': enabling }" :disabled="enabling">
              Enable
            </PrimaryButton>
          </ConfirmsPassword>
        </div>

        <div v-else>
          <ConfirmsPassword @confirmed="confirmTwoFactorAuthentication">
            <PrimaryButton
              v-if="confirming"
              type="button"
              class="tw-mr-3"
              :class="{ 'tw-opacity-25': enabling }"
              :disabled="enabling"
            >
              Confirm
            </PrimaryButton>
          </ConfirmsPassword>

          <ConfirmsPassword @confirmed="regenerateRecoveryCodes">
            <SecondaryButton
              v-if="recoveryCodes.length > 0 && ! confirming"
              class="tw-mr-3"
            >
              Regenerate Recovery Codes
            </SecondaryButton>
          </ConfirmsPassword>

          <ConfirmsPassword title="Show Two Factor Recovery Codes" @confirmed="showRecoveryCodes">
            <SecondaryButton
              v-if="recoveryCodes.length === 0 && ! confirming"
              class="tw-mr-3"
            >
              Show Recovery Codes
            </SecondaryButton>
          </ConfirmsPassword>

          <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
            <SecondaryButton
              v-if="confirming"
              :class="{ 'tw-opacity-25': disabling }"
              :disabled="disabling"
            >
              Cancel
            </SecondaryButton>
          </ConfirmsPassword>

          <ConfirmsPassword title="Disable Two Factor Authentication" @confirmed="disableTwoFactorAuthentication">
            <DangerButton
              v-if="! confirming"
              :class="{ 'tw-opacity-25': disabling }"
              :disabled="disabling"
            >
              Disable
            </DangerButton>
          </ConfirmsPassword>
        </div>
      </div>
    </template>
  </ActionSection>
</template>
