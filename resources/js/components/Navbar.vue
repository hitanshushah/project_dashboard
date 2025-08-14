<template>
  <v-app-bar 
    app 
    :color="isDark ? 'grey-darken-4' : 'white'" 
    :dark="isDark"
    height="64" 
    class="px-4 transition-colors duration-300"
    elevation="1"
  >
    <!-- Logo on left -->
    <v-app-bar-title :class="[
      'font-bold text-xl transition-colors duration-300',
      isDark ? 'text-white' : 'text-gray-900'
    ]">
      <div class="flex items-center">
        <img 
          src="/images/logo.png" 
          alt="LogoApp" 
          class="w-24 h-12 rounded mr-2 object-cover"
        >
        <span>Admin Panel</span>
      </div>
    </v-app-bar-title>

    <v-spacer></v-spacer>

    <!-- Theme Toggle -->
    <ThemeToggle class="mr-4" />

    <!-- Avatar dropdown on right -->
    <v-menu offset-y>
      <template v-slot:activator="{ props }">
        <v-btn
          icon
          v-bind="props"
          class="ml-2"
        >
          <v-avatar size="40" class="bg-gradient-to-br from-purple-400 to-blue-500">
            <span class="text-white font-semibold text-lg">{{ userInitials }}</span>
          </v-avatar>
        </v-btn>
      </template>

      <v-card class="mx-auto" min-width="280" max-width="320">
        <!-- User info header -->
        <v-card-text class="pb-2">
          <div class="flex items-center space-x-3">
            <v-avatar size="48" class="bg-gradient-to-br from-purple-400 to-blue-500">
              <span class="text-white font-semibold text-xl">{{ userInitials }}</span>
            </v-avatar>
            <div>
              <div class="font-semibold text-white">{{ currentProfile?.name || currentUser?.username || 'User' }}</div>
              <div class="text-sm text-gray-400">{{ currentUser?.email || 'user@example.com' }}</div>
            </div>
          </div>
        </v-card-text>

        <v-divider></v-divider>

        <!-- Menu items -->
        <v-list density="compact" class="py-1">
          <v-list-item
            prepend-icon="mdi-account-edit"
            title="Edit Profile"
            @click="editProfile"
          >
            <template v-slot:append>
              <v-icon icon="mdi-chevron-right" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>

          <v-list-item
            prepend-icon="mdi-web"
            title="See Website"
            @click="seeWebsite"
          >
            <template v-slot:append>
              <v-icon icon="mdi-chevron-right" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>

          <v-divider class="my-1"></v-divider>

          <v-list-item
            prepend-icon="mdi-logout"
            title="Logout"
            @click="logout"
            class="hover:bg-red-50 transition-colors text-red-600"
          >
            <template v-slot:append>
              <v-icon icon="mdi-chevron-right" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </v-menu>
  </v-app-bar>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import ThemeToggle from './ThemeToggle.vue';
import { useAppearance } from '@/composables/useAppearance';

const page = usePage();
const { isDark } = useAppearance();

const currentUser = computed(() => page.props.auth?.user);

const currentProfile = computed(() => page.props.auth?.profile);

const userInitials = computed(() => {
  const name = currentProfile.value?.name || currentUser.value?.username;
  if (!name) return 'U';
  return name
    .split(' ')
    .map((name: string) => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2);
});

const editProfile = () => {
  router.visit('/profile/edit');
};

const seeWebsite = () => {
  console.log('See Website clicked');
  router.visit('/public-projects');
};

const logout = () => {
  console.log('Logout clicked');
  // Add your logout logic here
  // Example: router.visit('/logout');
};
</script>