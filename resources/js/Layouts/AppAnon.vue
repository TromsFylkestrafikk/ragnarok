<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';

defineProps({
    title: { type: String, default: '' },
});
</script>

<template>
  <Head :title="title" />
  <v-app>
    <v-app-bar flat color="primary">
      <v-container class="fill-height py-0">
        <v-app-bar-title :text="title" />
        <v-tabs centered color="secondary">
          <v-tab exact>
            <Link :href="route('login')">
              Log in
            </Link>
          </v-tab>
        </v-tabs>
        <v-spacer />
      </v-container>
    </v-app-bar>

    <!-- Page Content -->
    <v-main class="bg-grey-lighten-3 tw">
      <v-container class="d-flex justify-center align-center">
        <Banner />
        <v-card min-width="460">
          <v-card-text>
            <slot />
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>
