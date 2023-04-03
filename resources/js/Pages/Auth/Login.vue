<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppAnon from '@/Layouts/AppAnon.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <AppAnon title="Log in">
    <div v-if="status" class="tw-mb-4 tw-font-medium tw-text-sm tw-text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="email" value="Email" />
        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="tw-mt-1 tw-block tw-w-full"
          required
          autofocus
          autocomplete="username"
        />
        <InputError class="tw-mt-2" :message="form.errors.email" />
      </div>

      <div class="tw-mt-4">
        <InputLabel for="password" value="Password" />
        <TextInput
          id="password"
          v-model="form.password"
          type="password"
          class="tw-mt-1 tw-block tw-w-full"
          required
          autocomplete="current-password"
        />
        <InputError class="tw-mt-2" :message="form.errors.password" />
      </div>

      <div class="tw-block tw-mt-4">
        <label class="tw-flex tw-items-center">
          <Checkbox v-model:checked="form.remember" name="remember" />
          <span class="tw-ml-2 tw-text-sm tw-text-gray-600">Remember me</span>
        </label>
      </div>

      <div class="tw-flex tw-items-center tw-justify-end tw-mt-4">
        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="tw-underline tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-indigo-500"
        >
          Forgot your password?
        </Link>

        <PrimaryButton class="tw-ml-4" :class="{ 'tw-opacity-25': form.processing }" :disabled="form.processing">
          Log in
        </PrimaryButton>
      </div>
    </form>
  </AppAnon>
</template>
