<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

const updateProfileInformation = () => {
    if (photoInput.value) {
        [form.photo] = photoInput.value.files;
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (!photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};
</script>

<template>
  <FormSection @submitted="updateProfileInformation">
    <template #title>
      Profile Information
    </template>

    <template #description>
      Update your account's profile information and email address.
    </template>

    <template #form>
      <!-- Profile Photo -->
      <div v-if="$page.props.jetstream.managesProfilePhotos" class="tw-col-span-6 sm:tw-col-span-4">
        <!-- Profile Photo File Input -->
        <input
          ref="photoInput"
          type="file"
          class="tw-hidden"
          @change="updatePhotoPreview"
        >

        <InputLabel for="photo" value="Photo" />

        <!-- Current Profile Photo -->
        <div v-show="! photoPreview" class="tw-mt-2">
          <img :src="user.profile_photo_url" :alt="user.name" class="tw-rounded-full tw-h-20 tw-w-20 tw-object-cover">
        </div>

        <!-- New Profile Photo Preview -->
        <div v-show="photoPreview" class="tw-mt-2">
          <span
            class="tw-block tw-rounded-full tw-w-20 tw-h-20 tw-bg-cover tw-bg-no-repeat tw-bg-center"
            :style="'background-image: url(\'' + photoPreview + '\');'"
          />
        </div>

        <SecondaryButton class="tw-mt-2 tw-mr-2" type="button" @click.prevent="selectNewPhoto">
          Select A New Photo
        </SecondaryButton>

        <SecondaryButton
          v-if="user.profile_photo_path"
          type="button"
          class="tw-mt-2"
          @click.prevent="deletePhoto"
        >
          Remove Photo
        </SecondaryButton>

        <InputError :message="form.errors.photo" class="tw-mt-2" />
      </div>

      <!-- Name -->
      <div class="tw-col-span-6 sm:tw-col-span-4">
        <InputLabel for="name" value="Name" />
        <TextInput
          id="name"
          v-model="form.name"
          type="text"
          class="tw-mt-1 tw-block tw-w-full"
          autocomplete="name"
        />
        <InputError :message="form.errors.name" class="tw-mt-2" />
      </div>

      <!-- Email -->
      <div class="tw-col-span-6 sm:tw-col-span-4">
        <InputLabel for="email" value="Email" />
        <TextInput
          id="email"
          v-model="form.email"
          type="email"
          class="tw-mt-1 tw-block tw-w-full"
          autocomplete="username"
        />
        <InputError :message="form.errors.email" class="tw-mt-2" />

        <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null">
          <p class="tw-text-sm tw-mt-2">
            Your email address is unverified.

            <Link
              :href="route('verification.send')"
              method="post"
              as="button"
              class="tw-underline tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-indigo-500"
              @click.prevent="sendEmailVerification"
            >
              Click here to re-send the verification email.
            </Link>
          </p>

          <div v-show="verificationLinkSent" class="tw-mt-2 tw-font-medium tw-text-sm tw-text-green-600">
            A new verification link has been sent to your email address.
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <ActionMessage :on="form.recentlySuccessful" class="tw-mr-3">
        Saved.
      </ActionMessage>

      <PrimaryButton :class="{ 'tw-opacity-25': form.processing }" :disabled="form.processing">
        Save
      </PrimaryButton>
    </template>
  </FormSection>
</template>
