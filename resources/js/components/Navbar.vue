<template>
  <v-app-bar 
    app 
    :color="isDark ? 'grey-darken-4' : '#dbdbdb'" 
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
        
        <!-- Social Media Links - Hidden on mobile -->
        <div class="hidden md:flex items-center gap-4 ml-8">
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
            <v-icon :class="[isDark ? 'text-gray-300 hover:text-gray-700' : 'text-blue-800']" size="small">mdi-linkedin</v-icon>
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
            <v-icon :class="[ isDark ? 'text-gray-300' : 'text-gray-950']" size="small">mdi-github</v-icon>
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
            <v-icon :class="[isDark ? 'text-gray-300 hover:text-gray-700' : 'text-orange-400']" size="small">mdi-web</v-icon>
          </v-btn>
        </div>
        
        <!-- Documents Menu - Hidden on mobile -->
        <div v-if="documents.length > 0" class="hidden md:block">
          <v-menu offset-y>
            <template v-slot:activator="{ props }">
              <v-btn
                icon
                v-bind="props"
                size="small"
                variant="text"
                :class="[isDark ? '!text-gray-300' : '!text-blue-600']"
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
         
         <!-- Public URL Controls - Hidden on mobile -->
        <div v-if="currentProfile?.public_url" class="ml-4 hidden md:block">
          <v-btn-group>
            <!-- Visit Live URL -->
                          <v-btn
                :href="`https://${currentProfile.public_url}.${domainUrl}`"
                target="_blank"
                size="medium"
                variant="text"
                append-icon="mdi-open-in-new"
                :class="[isDark ? '!bg-black text-white !border-gray-600 border !text-sm py-2 px-2' : '!bg-white text-gray-700 !border-gray-300 border !text-sm py-2 px-2']"
              >
                <span class="mr-2">Live URL</span>
              </v-btn>

            <!-- Edit Public URL -->
            <v-menu v-model="editMenuOpen" offset-y @update:model-value="setupEditPublicUrl">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon
                  size="x-small"
                  variant="text"
                  :class="[isDark ? '!bg-black text-white !border-gray-600 border !text-sm px-2 text-gray-400 hover:text-gray-600' : '!bg-white text-gray-700 !border-gray-300 border !text-sm px-2 text-gray-400 hover:text-gray-600']"
                  :title="`Edit public URL`"
                >
                  <v-icon icon="mdi-pencil" size="small"></v-icon>
                </v-btn>
              </template>
              <!-- Edit Menu Content -->
              <v-card min-width="400" class="pa-4" @click.stop>
                <v-card-title class="text-lg font-semibold pb-2">Edit Public URL</v-card-title>
                <v-card-text class="pa-0 pb-4">
                  <p :class="[isDark ? 'text-sm text-gray-400 mb-4' : 'text-sm text-gray-700 mb-4']">
                    Update your custom public URL. This will change your unique profile link.
                  </p>
                  <v-form @submit.prevent="updatePublicUrl" @click.stop>
                    <v-text-field
                      v-model="publicUrlInput"
                      label="Custom URL"
                      variant="outlined"
                      :error-messages="urlError"
                      :loading="isLoading"
                      :disabled="isLoading"
                      prepend-inner-icon="mdi-link"
                      :hint="`Your URL will be: ${fullPublicUrl}`"
                      persistent-hint
                      density="compact"
                      @click.stop
                      @input="validatePublicUrl"
                    >
                      <template v-slot:append>
                        <span :class="[isDark ? 'text-gray-400 text-sm' : 'text-gray-700 text-sm']">.{{ domainUrl }}</span>
                      </template>
                    </v-text-field>
                  </v-form>
                </v-card-text>
                <v-card-actions class="pa-0">
                  <v-spacer></v-spacer>
                  <v-btn variant="outlined" @click="closeEditMenu" :disabled="isLoading" size="small" class="px-2">Cancel</v-btn>
                  <v-btn color="success" @click="updatePublicUrl" :loading="isLoading" :disabled="!publicUrlInput.trim()" size="small" :variant="isDark ? 'tonal' : 'elevated'">Update URL</v-btn>
                </v-card-actions>
              </v-card>
            </v-menu>

            <!-- Copy Public URL -->
            <v-btn
              @click="copyPublicUrl"
              icon
              size="x-small"
              variant="text"
              :class="[isDark ? '!bg-black text-white !border-gray-600 border !text-sm px-2 text-blue-400 hover:text-blue-600' : '!bg-white text-gray-700 !border-gray-300 border !text-sm px-2 text-blue-400 hover:text-blue-600']"
              :title="`Copy public URL to clipboard`"
            >
              <v-icon icon="mdi-content-copy" size="small"></v-icon>
            </v-btn>

            <!-- Share Public URL -->
            <v-btn
              @click="sharePublicUrl"
              icon
              size="x-small"
              variant="text"
              :class="[isDark ? '!bg-black text-white !border-gray-600 border !text-sm px-2 text-green-400 hover:text-green-600' : '!bg-white text-gray-700 !border-gray-300 border !text-sm px-2 text-green-400 hover:text-green-600']"
              :title="`Share public URL`"
            >
              <v-icon icon="mdi-share-variant" size="small"></v-icon>
            </v-btn>

            <!-- Delete Public URL -->
            <v-menu v-model="deleteMenuOpen" offset-y>
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon
                  size="x-small"
                  variant="text"
                  :class="[isDark ? '!bg-black text-white !border-gray-600 border !text-sm px-2 text-red-400 hover:text-red-600' : '!bg-white text-gray-700 !border-gray-300 border !text-sm px-2 text-red-400 hover:text-red-600']"
                  :title="`Delete public URL`"
                >
                  <v-icon icon="mdi-delete" size="small"></v-icon>
                </v-btn>
              </template>
              <!-- Delete Menu Content -->
              <v-card min-width="300" class="pa-4" @click.stop>
                <v-card-title class="text-lg font-semibold pb-2">Delete Public URL</v-card-title>
                <v-card-text class="pa-0 pb-4">
                  <p :class="[isDark ? 'text-sm text-gray-400 mb-4' : 'text-sm text-gray-700 mb-4']">
                    Are you sure you want to delete your public URL?
                  </p>
                </v-card-text>
                <v-card-actions class="pa-0">
                  <v-spacer></v-spacer>
                  <v-btn variant="outlined" @click="closeDeleteMenu" size="small">Cancel</v-btn>
                  <v-btn color="error" @click="deletePublicUrl" :loading="isDeleting" size="small" :variant="isDark ? 'tonal' : 'elevated'">Delete URL</v-btn>
                </v-card-actions>
              </v-card>
            </v-menu>
          </v-btn-group>
        </div>

        <!-- Create Public URL Button if none exists - Hidden on mobile -->
        <v-menu v-model="createMenuOpen" offset-y v-else class="hidden md:block">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              size="medium"
              variant="text"
              append-icon="mdi-chevron-down"
              :class="[isDark ? '!bg-black text-white !border-gray-600 border rounded-lg !text-sm py-2 px-2 ml-4' : '!bg-white text-gray-700 !border-gray-300 border rounded-lg !text-sm py-2 px-2 ml-4']"
            >
              <span class="mr-2">Set Live URL</span>
            </v-btn>
          </template>
          <!-- Create Menu Content -->
          <v-card min-width="400" class="pa-4" @click.stop>
            <v-card-title class="text-lg font-semibold pb-2">Create Public URL</v-card-title>
            <v-card-text class="pa-0 pb-4">
              <p :class="[isDark ? 'text-sm text-gray-400 mb-4' : 'text-sm text-gray-700 mb-4']">
                Create a custom public URL for your portfolio. This will be your unique profile link.
              </p>
              <v-form @submit.prevent="savePublicUrl" @click.stop>
                <v-text-field
                  v-model="publicUrlInput"
                  label="Custom URL"
                  variant="outlined"
                  :error-messages="urlError"
                  :loading="isLoading"
                  :disabled="isLoading"
                  prepend-inner-icon="mdi-link"
                  :hint="`Your URL will be: ${fullPublicUrl}`"
                  persistent-hint
                  density="compact"
                  @click.stop
                >
                  <template v-slot:append>
                    <span :class="[isDark ? 'text-gray-400 text-sm' : 'text-gray-700 text-sm']">.{{ domainUrl }}</span>
                  </template>
                </v-text-field>
              </v-form>
            </v-card-text>
            <v-card-actions class="pa-0">
              <v-spacer></v-spacer>
              <v-btn variant="outlined" @click="closeCreateMenu" :disabled="isLoading" size="small" class="px-2">Cancel</v-btn>
              <v-btn color="success" @click="savePublicUrl" :loading="isLoading" :disabled="!publicUrlInput.trim()" size="small" :variant="isDark ? 'tonal' : 'elevated'">Create URL</v-btn>
            </v-card-actions>
          </v-card>
        </v-menu>
      </div>
    </v-app-bar-title>

    <!-- Right side controls -->
    <div class="flex items-center">
      <!-- Theme Toggle -->
      <ThemeToggle class="mr-4" />

      <!-- Mobile Menu Button -->
       <div class="md:hidden">
      <v-btn
        icon
        @click="mobileMenuOpen = !mobileMenuOpen"
        class="md:hidden mr-2"
        :class="[isDark ? 'text-white' : 'text-gray-900']"
      >
        <v-icon>{{ mobileMenuOpen ? 'mdi-close' : 'mdi-menu' }}</v-icon>
      </v-btn>
      </div>

      <!-- Avatar dropdown on right -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn
            icon
            v-bind="props"
            class="ml-2"
          >
            <v-avatar size="40" :class="isDark ? 'bg-gray-300' : 'bg-white'">
              <v-img
                v-if="profilePhotoUrl"
                :src="profilePhotoUrl"
                cover
                @error="handleImageError"
              />
              <span v-else :class="isDark ? 'text-white font-semibold text-lg' : 'text-gray-900 font-semibold text-lg'">{{ userInitials }}</span>
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
                <div :class="isDark ? 'font-semibold text-white' : 'font-semibold text-gray-900'">{{ currentProfile?.name || currentUser?.username || 'User' }}</div>
                <div :class="isDark ? 'text-sm text-gray-400' : 'text-sm text-gray-700'">{{ currentUser?.email || 'user@example.com' }}</div>
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
                <v-icon icon="mdi-chevron-right" size="small" :class="isDark ? 'text-gray-400' : 'text-gray-700'"></v-icon>
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
                <v-icon icon="mdi-chevron-right" size="small" :class="isDark ? 'text-gray-400' : 'text-gray-700'"></v-icon>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-menu>
    </div>
  </v-app-bar>

  <!-- Mobile Navigation Drawer -->
  <v-navigation-drawer
    v-model="mobileMenuOpen"
    temporary
    location="right"
    :color="isDark ? 'grey-darken-4' : '#dbdbdb'"
    :dark="isDark"
    width="300"
  >
    <v-list>
      <!-- User Profile Section -->
      <v-list-item class="py-4">
        <template v-slot:prepend>
          <v-avatar size="48" class="bg-gradient-to-br from-purple-400 to-blue-500">
            <v-img
              v-if="profilePhotoUrl"
              :src="profilePhotoUrl"
              cover
              @error="handleImageError"
            />
            <span v-else class="text-white font-semibold text-xl">{{ userInitials }}</span>
          </v-avatar>
        </template>
        <v-list-item-title :class="isDark ? 'text-white' : 'text-gray-900'">
          {{ currentProfile?.name || currentUser?.username || 'User' }}
        </v-list-item-title>
        <v-list-item-subtitle :class="isDark ? 'text-gray-400' : 'text-gray-700'">
          {{ currentUser?.email || 'user@example.com' }}
        </v-list-item-subtitle>
      </v-list-item>

      <v-divider></v-divider>

             <!-- Social Media Links -->
       <v-list-subheader :class="isDark ? 'text-gray-300' : 'text-gray-700'">Social Links</v-list-subheader>
       
       <v-list-item
         v-if="linkedinLink"
         :href="linkedinLink.url"
         target="_blank"
         prepend-icon="mdi-linkedin"
         :class="[isDark ? 'text-blue-400' : 'text-blue-800']"
       >
         <v-list-item-title>{{ linkedinLink.title }}</v-list-item-title>
       </v-list-item>

       <v-list-item
         v-if="githubLink"
         :href="githubLink.url"
         target="_blank"
         prepend-icon="mdi-github"
         :class="[isDark ? 'text-gray-300' : 'text-gray-950']"
       >
         <v-list-item-title>{{ githubLink.title }}</v-list-item-title>
       </v-list-item>

       <v-list-item
         v-if="portfolioLink"
         :href="portfolioLink.url"
         target="_blank"
         prepend-icon="mdi-web"
         :class="[isDark ? 'text-orange-400' : 'text-orange-600']"
       >
         <v-list-item-title>{{ portfolioLink.title }}</v-list-item-title>
       </v-list-item>

       <!-- Documents Section -->
       <template v-if="documents.length > 0">
         <v-divider></v-divider>
         <v-list-subheader :class="isDark ? 'text-gray-300' : 'text-gray-700'">
           Documents ({{ documents.length }})
         </v-list-subheader>
         
         <v-list-item
           v-for="document in documents"
           :key="document.id"
           :href="document.url"
           target="_blank"
           :prepend-icon="getDocumentIcon(document.name || '')"
           :class="[isDark ? 'text-gray-300' : 'text-gray-700']"
         >
           <v-list-item-title>{{ document.name }}</v-list-item-title>
           <template v-slot:append>
             <v-icon icon="mdi-open-in-new" size="small" :class="isDark ? 'text-gray-400' : 'text-gray-500'"></v-icon>
           </template>
         </v-list-item>
       </template>

       <!-- Public URL Section -->
      <v-divider></v-divider>
      <v-list-subheader :class="isDark ? 'text-gray-300' : 'text-gray-700'">Public URL</v-list-subheader>
      
      <v-list-item
        v-if="currentProfile?.public_url"
        :href="`https://${currentProfile.public_url}.${domainUrl}`"
        target="_blank"
        prepend-icon="mdi-link"
        :class="[isDark ? 'text-green-400' : 'text-green-600']"
      >
        <v-list-item-title>Visit</v-list-item-title>
        <template v-slot:append>
          <v-icon icon="mdi-open-in-new" size="small" :class="isDark ? 'text-gray-400' : 'text-gray-500'"></v-icon>
        </template>
      </v-list-item>

      <v-list-item
        v-if="currentProfile?.public_url"
        @click="setupEditPublicUrl(); editMenuOpen = true"
        prepend-icon="mdi-pencil"
        :class="[isDark ? 'text-blue-400' : 'text-blue-600']"
      >
        <v-list-item-title>Edit</v-list-item-title>
      </v-list-item>

      <v-list-item
        v-if="currentProfile?.public_url"
        @click="copyPublicUrl"
        prepend-icon="mdi-content-copy"
        :class="[isDark ? 'text-blue-400' : 'text-blue-600']"
      >
        <v-list-item-title>Copy</v-list-item-title>
      </v-list-item>

      <v-list-item
        v-if="currentProfile?.public_url"
        @click="sharePublicUrl"
        prepend-icon="mdi-share-variant"
        :class="[isDark ? 'text-green-400' : 'text-green-600']"
      >
        <v-list-item-title>Share</v-list-item-title>
      </v-list-item>

      <v-list-item
        v-if="currentProfile?.public_url"
        @click="deleteMenuOpen = true"
        prepend-icon="mdi-delete"
        class="text-red-600"
      >
        <v-list-item-title>Delete</v-list-item-title>
      </v-list-item>

      <v-list-item
        v-else
        @click="createMenuOpen = true"
        prepend-icon="mdi-plus"
        :class="[isDark ? 'text-green-400' : 'text-green-600']"
      >
        <v-list-item-title>Create Public URL</v-list-item-title>
      </v-list-item>

      <!-- Profile Actions -->
      <v-divider></v-divider>
      <v-list-item
        @click="editProfile"
        prepend-icon="mdi-account-edit"
        :class="[isDark ? 'text-gray-300' : 'text-gray-700']"
      >
        <v-list-item-title>Edit Profile</v-list-item-title>
      </v-list-item>

      <v-list-item
        @click="logout"
        prepend-icon="mdi-logout"
        class="text-red-600"
      >
        <v-list-item-title>Logout</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-navigation-drawer>

  <!-- Public URL Setup Modal -->
  
  <!-- Toast Notification -->
  <v-snackbar
    v-model="snackbar"
    :timeout="3000"
    :color="snackbarColor"
    location="bottom"
    multi-line
  >
    {{ snackbarMessage }}
  </v-snackbar>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import ThemeToggle from './ThemeToggle.vue';
import { useAppearance } from '@/composables/useAppearance';

const page = usePage();
const { isDark } = useAppearance();

const publicUrl = import.meta.env.VITE_PUBLIC_URL;
const domainUrl = import.meta.env.VITE_DOMAIN_URL || 'local.hitanshushah.com';
// Public URL dropdown state
const publicUrlInput = ref('');
const urlError = ref('');
const isLoading = ref(false);
const isDeleting = ref(false);
const editMenuOpen = ref(false);
const deleteMenuOpen = ref(false);
const createMenuOpen = ref(false);

// Mobile menu state
const mobileMenuOpen = ref(false);

// Toast notification state
const snackbar = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('success');

const currentUser = computed(() => page.props.auth?.user);

const currentProfile = computed(() => page.props.auth?.profile);
const profilePhotoUrl = computed(() => {
  const url = currentProfile.value?.profile_photo_url || null;
  
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

// Computed property for full public URL
const fullPublicUrl = computed(() => {
  if (!publicUrlInput.value.trim()) return '';
  return `${publicUrlInput.value.trim()}.${domainUrl}`;
});

const setupPublicUrl = () => {
  publicUrlInput.value = '';
  urlError.value = '';
};

const setupEditPublicUrl = () => {
  publicUrlInput.value = currentProfile.value?.public_url || '';
  urlError.value = '';
};

const savePublicUrl = async () => {
  if (!publicUrlInput.value.trim()) {
    urlError.value = 'Please enter a custom URL';
    return;
  }

  // Validate that the URL doesn't contain dots
  if (publicUrlInput.value.includes('.')) {
    urlError.value = 'Public URL cannot contain dots (.). Use only letters, numbers, hyphens, and underscores.';
    return;
  }

  // Validate URL format
  if (!/^[a-zA-Z0-9_-]+$/.test(publicUrlInput.value.trim())) {
    urlError.value = 'Public URL can only contain letters, numbers, hyphens, and underscores.';
    return;
  }

  isLoading.value = true;
  urlError.value = '';

  try {
    const response = await fetch('/api/profile/public-url', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        public_url: publicUrlInput.value.trim()
      })
    });

    const data = await response.json();

    if (response.ok) {
      // Refresh the page to get updated profile data
      window.location.reload();
    } else {
      urlError.value = data.message || 'Failed to save public URL';
    }
  } catch (error) {
    urlError.value = 'Network error. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const updatePublicUrl = async () => {
  if (!publicUrlInput.value.trim()) {
    urlError.value = 'Please enter a custom URL';
    return;
  }

  // Validate that the URL doesn't contain dots
  if (publicUrlInput.value.includes('.')) {
    urlError.value = 'Public URL cannot contain dots (.). Use only letters, numbers, hyphens, and underscores.';
    return;
  }

  // Validate URL format
  if (!/^[a-zA-Z0-9_-]+$/.test(publicUrlInput.value.trim())) {
    urlError.value = 'Public URL can only contain letters, numbers, hyphens, and underscores.';
    return;
  }

  // Check if the URL is the same as current
  if (publicUrlInput.value.trim() === currentProfile.value?.public_url) {
    urlError.value = 'This is already your current URL';
    return;
  }

  isLoading.value = true;
  urlError.value = '';

  try {
    const response = await fetch('/api/profile/public-url', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        public_url: publicUrlInput.value.trim()
      })
    });

    const data = await response.json();

    if (response.ok) {
      // Refresh the page to get updated profile data
      window.location.reload();
    } else {
      urlError.value = data.message || 'Failed to update public URL';
    }
  } catch (error) {
    urlError.value = 'Network error. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const closeEditMenu = () => {
  editMenuOpen.value = false;
};

const validatePublicUrl = () => {
  if (!publicUrlInput.value.trim()) {
    urlError.value = '';
    return;
  }

  if (publicUrlInput.value.includes('.')) {
    urlError.value = 'Public URL cannot contain dots (.). Use only letters, numbers, hyphens, and underscores.';
    return;
  }

  if (!/^[a-zA-Z0-9_-]+$/.test(publicUrlInput.value.trim())) {
    urlError.value = 'Public URL can only contain letters, numbers, hyphens, and underscores.';
    return;
  }

  urlError.value = '';
};

const closeDeleteMenu = () => {
  deleteMenuOpen.value = false;
};

const closeCreateMenu = () => {
  createMenuOpen.value = false;
};

const deletePublicUrl = async () => {
  isDeleting.value = true;

  try {
    const response = await fetch('/api/profile/public-url', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });

    const data = await response.json();

    if (response.ok) {
      // Refresh the page to get updated profile data
      window.location.reload();
    } else {
      
    }
  } catch (error) {
    
  } finally {
    isDeleting.value = false;
  }
};

const copyPublicUrl = async () => {
  const fullUrl = `https://${currentProfile.value?.public_url}.${domainUrl}`;
  
  try {
    await navigator.clipboard.writeText(fullUrl);
    snackbarMessage.value = 'URL copied to clipboard!';
    snackbarColor.value = 'success';
    snackbar.value = true;
  } catch (error) {
    console.error('Failed to copy URL to clipboard:', error);
    // Fallback for older browsers
    const textArea = document.createElement('textarea');
    textArea.value = fullUrl;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    snackbarMessage.value = 'URL copied to clipboard!';
    snackbarColor.value = 'success';
    snackbar.value = true;
  }
};

const sharePublicUrl = async () => {
  const fullUrl = `https://${currentProfile.value?.public_url}.${domainUrl}`;
  
  // Check if Web Share API is available (mobile devices)
  if (navigator.share) {
    try {
      await navigator.share({
        title: `${currentProfile.value?.name || 'My Portfolio'}`,
        text: `Check out my portfolio: ${currentProfile.value?.name || 'My Portfolio'}`,
        url: fullUrl
      });
    } catch (error) {
      // User cancelled or share failed, fallback to copy
      copyPublicUrl();
    }
  } else {
    // Fallback to copy for desktop browsers
    copyPublicUrl();
  }
};

const logout = () => {
  const logoutUrl = usePage().props.logoutUrl as string;
  if (logoutUrl) {
    window.location.href = logoutUrl;
  }
};
</script>