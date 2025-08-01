<template>
  <v-app-bar app color="grey-darken-4" dark height="64" class="px-4">
    <!-- Logo on left -->
    <v-app-bar-title class="text-white font-bold text-xl">
      <div class="flex items-center">
        <div class="w-8 h-8 bg-blue-500 rounded mr-2 flex items-center justify-center">
          <span class="text-white font-bold text-sm">L</span>
        </div>
        <span>LogoApp</span>
      </div>
    </v-app-bar-title>

    <!-- Navigation items (optional) -->
    <v-spacer></v-spacer>
    
    <div class="hidden md:flex items-center space-x-6 mr-6">
      <a href="#" class="text-white hover:text-gray-300 transition-colors">Home</a>
      <a href="#" class="text-white hover:text-gray-300 transition-colors">Features</a>
      <a href="#" class="text-white hover:text-gray-300 transition-colors">About</a>
      <a href="#" class="text-white hover:text-gray-300 transition-colors">Contact</a>
    </div>

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
              <div class="font-semibold text-gray-800">{{ currentProfile?.name || currentUser?.username || 'User' }}</div>
              <div class="text-sm text-gray-600">{{ currentUser?.email || 'user@example.com' }}</div>
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
            class="hover:bg-gray-50 transition-colors"
          >
            <template v-slot:append>
              <v-icon icon="mdi-chevron-right" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>

          <v-list-item
            prepend-icon="mdi-cog"
            title="Settings & Privacy"
            @click="openSettings"
            class="hover:bg-gray-50 transition-colors"
          >
            <template v-slot:append>
              <v-icon icon="mdi-chevron-right" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>

          <v-list-item
            prepend-icon="mdi-help-circle"
            title="Help & Support"
            @click="openSupport"
            class="hover:bg-gray-50 transition-colors"
          >
            <template v-slot:append>
              <v-icon icon="mdi-chevron-right" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>

          <v-list-item
            prepend-icon="mdi-monitor"
            title="Display & Accessibility"
            @click="openAccessibility"
            class="hover:bg-gray-50 transition-colors"
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
import { usePage } from '@inertiajs/vue3';

const page = usePage();

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
  console.log('Edit Profile clicked');
  // Add your edit profile logic here
};

const openSettings = () => {
  console.log('Settings & Privacy clicked');
  // Add your settings logic here
};

const openSupport = () => {
  console.log('Help & Support clicked');
  // Add your support logic here
};

const openAccessibility = () => {
  console.log('Display & Accessibility clicked');
  // Add your accessibility logic here
};

const logout = () => {
  console.log('Logout clicked');
  // Add your logout logic here
  // Example: router.visit('/logout');
};
</script>

<style scoped>
/* Additional custom styles if needed */
.v-list-item:hover {
  background-color: #f9fafb;
}

.v-list-item.text-red-600:hover {
  background-color: #fef2f2;
}
</style>