<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const emit = defineEmits(['refresh', 'error', 'close']);

const props = defineProps({
    roles: {
        type: Array,
        required: true,
        default() {
            return [];
        },
    },
});

const isValidForm = ref(false);

const form = useForm({
    name: '',
    email: '',
    role: props.roles.slice(0, 1).toString(),
});

const nameRules = () => {
    if (!form.name) return 'Required';
    if (form.name.length > 3) return true;
    return 'Account name is too short.';
};

const emailRules = () => {
    if (!form.email) return 'Required';
    const invalidMsg = 'Invalid e-mail address';
    const emailParts = form.email.split('@');
    if (emailParts.length !== 2) return invalidMsg;
    const emailName = emailParts.shift();
    if (emailName.split('.').includes('')) return invalidMsg;

    const emailHost = emailParts.shift().split('.');
    if (emailHost.length < 2) return invalidMsg;
    return (emailHost.includes('')) ? invalidMsg : true;
};

const roleRules = () => (form.role ? true : 'Required');

const createAccount = () => {
    form.email = form.email.toLowerCase();
    form.post(route('account.create'), {
        onFinish: () => emit('refresh'),
        onError: (e) => emit('error', `ERROR ${Object.values(e.message).shift()}`),
    });
};
</script>

<template>
  <v-card title="New user account">
    <v-card-text>
      <v-row no-gutters>
        <!-- Description -->
        <v-col cols="5">
          Set the new account's name, e-mail address and user role.
          Temporary login details will be sent to the user by e-mail.
        </v-col>

        <!-- Input form -->
        <v-col>
          <v-form v-model="isValidForm" class="custom-input-form" @submit.prevent="createAccount">
            <v-container>
              <!-- Name -->
              <v-row class="pa-2">
                <v-text-field
                  v-model="form.name"
                  :rules="[nameRules]"
                  label="Name"
                  density="compact"
                  clearable
                  autofocus
                  @click:clear="isValidForm = false"
                />
              </v-row>

              <!-- E-mail -->
              <v-row class="pa-2">
                <v-text-field
                  v-model="form.email"
                  :rules="[emailRules]"
                  label="E-mail"
                  density="compact"
                  clearable
                  @click:clear="isValidForm = false"
                />
              </v-row>

              <!-- User role -->
              <v-row class="pa-2">
                <v-select
                  v-model="form.role"
                  :items="props.roles"
                  :rules="[roleRules]"
                  label="User role"
                  density="compact"
                />
              </v-row>

              <!-- Action buttons -->
              <v-row class="pa-2">
                <v-spacer />
                <v-btn class="mr-6" color="secondary" @click="$emit('close')">
                  Cancel
                </v-btn>
                <v-btn
                  color="primary"
                  :disabled="!isValidForm"
                  :loading="form.processing"
                  type="submit"
                >
                  Create user account
                </v-btn>
              </v-row>
            </v-container>
          </v-form>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<style scoped lang="scss">
  .custom-input-form {
    border: 1px solid #ccc;
  }
</style>
