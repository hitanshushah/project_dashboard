<template>
  <v-app-bar 
    app 
    :color="isDark ? 'grey-darken-4' : 'white'" 
    :dark="isDark"
    height="72" 
    class="px-4 transition-colors duration-300"
    elevation="1"
  >
        <!-- Logo on left -->
    <v-app-bar-title :class="[
      'font-bold text-xl transition-colors duration-300 cursor-pointer hover:opacity-80',
      isDark ? 'text-white' : 'text-gray-900'
    ]" @click="goHome">
      <div class="flex items-center">
        <img 
          src="/images/logo.png" 
          alt="LogoApp" 
          class="w-24 h-12 rounded mr-2 object-cover"
        >
        <span>Admin Panel</span>
        
        <!-- Social Media Links -->
        <div class="flex items-center gap-4 ml-8">
          <!-- LinkedIn -->
          <v-btn
            v-if="linkedinLink"
            :href="linkedinLink.url"
            target="_blank"
            icon
            size="xx-large"
            variant="text"
            :title="`Visit ${linkedinLink.title}`"
          >
            <v-icon class="text-gray-300 hover:text-gray-700" size="small">mdi-linkedin</v-icon>
          </v-btn>

          <!-- GitHub -->
          <v-btn
            v-if="githubLink"
            :href="githubLink.url"
            target="_blank"
            icon
            size="xx-large"
            variant="text"
            :title="`Visit ${githubLink.title}`"
          >
            <v-icon class="text-gray-300 hover:text-gray-700" size="small">mdi-github</v-icon>
          </v-btn>

          <!-- Personal Website -->
          <v-btn
            v-if="portfolioLink"
            :href="portfolioLink.url"
            target="_blank"
            icon
            size="xx-large"
            variant="text"
            :title="`Visit ${portfolioLink.title}`"
          >
            <v-icon class="text-gray-300 hover:text-gray-700" size="small">mdi-web</v-icon>
          </v-btn>
        </div>
        <v-menu offset-y v-if="documents.length > 0">
      <template v-slot:activator="{ props }">
        <v-btn
          icon
          v-bind="props"
          size="small"
          variant="text"
          color="gray-300"
          class="mr-2 ml-2"
          :title="`${documents.length} document${documents.length !== 1 ? 's' : ''} available`"
        >
          <v-badge
            :content="documents.length"
            color="gray-300"
            offset-x="-2"
            offset-y="-6"
          >
            <v-icon>mdi-file-document-multiple</v-icon>
          </v-badge>
        </v-btn>
      </template>

      <v-card min-width="280">
        <v-card-title class="text-sm font-medium pb-2">
          Documents ({{ documents.length }})
        </v-card-title>
        <v-divider></v-divider>
        <v-list density="compact" class="py-1">
          <v-list-item
            v-for="document in documents"
            :key="document.id"
            :href="document.url"
            target="_blank"
            :prepend-icon="getDocumentIcon(document.name || '')"
            :title="document.name"
            class="hover:bg-gray-50"
          >
            <template v-slot:append>
              <v-icon icon="mdi-open-in-new" size="small" class="text-gray-400"></v-icon>
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </v-menu>
              </div>
      </v-app-bar-title>
  

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
              <v-img
                v-if="profilePhotoUrl"
                :src="profilePhotoUrl"
                cover
                @error="handleImageError"
              />
              <span v-else class="text-white font-semibold text-lg">{{ userInitials }}</span>
            </v-avatar>
          </v-btn>
      </template>

      <v-card class="mx-auto" min-width="280" max-width="320">
        <!-- User info header -->
        <v-card-text class="pb-2">
                      <div class="flex items-center space-x-3">
              <v-avatar size="48" class="bg-gradient-to-br from-purple-400 to-blue-500">
                <v-img
                  v-if="profilePhotoUrl"
                  :src="profilePhotoUrl"
                  cover
                  @error="handleImageError"
                />
                <span v-else class="text-white font-semibold text-xl">{{ userInitials }}</span>
              </v-avatar>
            <div>
              <div class="font-semibold text-white">{{ currentProfile?.name || currentUser?.username || 'User' }}</div>
              <div class="text-sm text-gray-400">{{ currentUser?.email || 'user@example.com' }}</div>
            </div>
          </div>
        </v-card-text>

        
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

const profilePhotoUrl = computed(() => {
  const url = currentProfile.value?.profile_photo_url || null;
  console.log('Profile photo URL:', url);
  console.log('Current profile data:', currentProfile.value);
  console.log('Profile links:', currentProfile.value?.links);
  console.log('Profile documents:', currentProfile.value?.documents);
  return url;
});

// Social media links
const linkedinLink = computed(() => {
  return currentProfile.value?.links?.find(link => 
    link.type === 'linkedin' || link.title.toLowerCase().includes('linkedin')
  );
});

const githubLink = computed(() => {
  return currentProfile.value?.links?.find(link => 
    link.type === 'github' || link.title.toLowerCase().includes('github')
  );
});

const portfolioLink = computed(() => {
  return currentProfile.value?.links?.find(link => 
    link.type === 'portfolio' || link.title.toLowerCase().includes('portfolio') || link.title.toLowerCase().includes('website')
  );
});

// Documents
const documents = computed(() => {
  return currentProfile.value?.documents || [];
});

// Check if user has any social links
const hasSocialLinks = computed(() => {
  return !!(linkedinLink.value || githubLink.value || portfolioLink.value);
});

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

const goHome = () => {
  router.visit('/');
};

const handleImageError = (error: any) => {
  console.error('Profile photo failed to load:', error);
  // The fallback to initials will happen automatically due to v-else
};

const getDocumentIcon = (documentName: string): string => {
  const name = documentName.toLowerCase();
  if (name.includes('resume') || name.includes('cv')) {
    return 'mdi-file-document-edit';
  } else if (name.includes('cover') || name.includes('letter')) {
    return 'mdi-file-document-outline';
  } else if (name.includes('certificate') || name.includes('cert')) {
    return 'mdi-certificate';
  } else if (name.includes('portfolio')) {
    return 'mdi-briefcase';
  } else {
    return 'mdi-file-document';
  }
};

const editProfile = () => {
  router.visit('/profile/edit');
};


const logout = () => {
  console.log('Logout clicked');
  // Add your logout logic here
  // Example: router.visit('/logout');
};
</script>