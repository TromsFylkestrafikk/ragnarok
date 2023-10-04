<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';

defineProps({
    title: { type: String, default: '' },
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
  <Head :title="title" />
  <v-app>
    <v-app-bar flat color="primary">
      <v-container class="fill-height py-0">
        <v-app-bar-title :text="title" />
        <Link :href="route('home')" :active="route().current('home')" class="home-link">
          Home
        </Link>
        <v-spacer />
        <v-avatar v-if="$page.props.auth?.user">
          <img
            :src="$page.props.auth.user.profile_photo_url"
            :alt="$page.props.auth.user.name"
            style="width: inherit;"
          >
          <v-menu activator="parent">
            <v-list>
              <v-list-subheader title="Manage account" />
              <v-list-item title="Profile" :href="route('profile.show')" prepend-icon="mdi-account" />
              <v-list-item title="User accounts" :href="route('user.accounts')" prepend-icon="mdi-account-multiple-check-outline" />
              <v-divider class="border-opacity-30" />
              <v-list-item title="Logout" prepend-icon="mdi-logout" @click.prevent="logout" />
            </v-list>
          </v-menu>
        </v-avatar>
      </v-container>
    </v-app-bar>

    <!-- Page Content -->
    <v-main>
      <v-container class="d-flex justify-center align-center">
        <Banner />
        <v-row>
          <v-col cols="12">
            <slot />
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<style lang="scss" scoped>
  .home-link {
    text-shadow: 0px 0px 4px white;
    text-underline-offset: 3px;
    margin-left: 40px;
    font-weight: bold;
    color: white;
  }
</style>
