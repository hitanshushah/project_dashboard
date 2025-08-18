<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { User, Profile, ProjectLink, ProjectAsset } from '@/types';
import { getLinkIcon, getFileIcon, getFileColor, formatFileSize } from '@/lib/projectUtils';
import { useAppearance } from '@/composables/useAppearance';

interface Props {
  user: User;
  profile: Profile & {
    assets?: ProjectAsset[];
  };
}

const props = defineProps<Props>();
const page = usePage();

// Toast state
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');

// Local state for links
const newLinkTitle = ref('');
const newLinkUrl = ref('');
const linkedinUrl = ref('');
const githubUrl = ref('');
const portfolioUrl = ref('');
const links = ref<ProjectLink[]>(props.profile.links || []);

// Local state for assets
interface AssetWithMeta {
  file: File;
  displayName: string;
  docType: string;
}

const newAssets = ref<AssetWithMeta[]>([]);
const existingAssets = ref<ProjectAsset[]>(props.profile.assets || []);
const fileInputRef = ref<HTMLInputElement>();

// Profile photo state
const profilePhoto = ref<File | null>(null);
const profilePhotoPreview = ref<string | null>(null);
const profilePhotoInputRef = ref<HTMLInputElement>();

// Predefined document types
const predefinedDocs = [
  { key: 'resume', label: 'Resume/CV', icon: 'mdi-file-document', description: 'Upload your resume or CV' },
  { key: 'cover-letter', label: 'Cover Letter', icon: 'mdi-file-document-outline', description: 'Upload your cover letter' },
  { key: 'other', label: 'Other Documents', icon: 'mdi-folder-multiple', description: 'Upload certificates, portfolios, etc.' }
];

// Form data with prefilled values
const form = useForm({
  name: props.profile.name || '',
  designation: props.profile.designation || '',
  bio: props.profile.bio || '',
  street: props.profile.street || '',
  city: props.profile.city || '',
  province: props.profile.province || '',
  country: props.profile.country || '',
});

// Theme management
const { isDark } = useAppearance();

// Methods for managing links
const addLink = () => {
  const title = newLinkTitle.value.trim();
  const url = newLinkUrl.value.trim();
  
  if (!title || !url) {
    return;
  }
  
  // Basic URL validation
  let validUrl = url;
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    validUrl = 'https://' + url;
  }
  
  links.value.push({
    title: title,
    url: validUrl
  });
  
  // Clear the input fields
  newLinkTitle.value = '';
  newLinkUrl.value = '';
};

const removeLink = (index: number) => {
  links.value.splice(index, 1);
};

const addLinkedinLink = () => {
  if (!linkedinUrl.value.trim()) {
    return;
  }
  
  // Basic URL validation
  let validUrl = linkedinUrl.value.trim();
  if (!validUrl.startsWith('http://') && !validUrl.startsWith('https://')) {
    validUrl = 'https://' + validUrl;
  }
  
  links.value.push({
    title: 'LinkedIn',
    url: validUrl
  });
  
  // Clear the input field
  linkedinUrl.value = '';
};

const addGithubLink = () => {
  if (!githubUrl.value.trim()) {
    return;
  }
  
  // Basic URL validation
  let validUrl = githubUrl.value.trim();
  if (!validUrl.startsWith('http://') && !validUrl.startsWith('https://')) {
    validUrl = 'https://' + validUrl;
  }
  
  links.value.push({
    title: 'Github',
    url: validUrl
  });
  
  // Clear the input field
  githubUrl.value = '';
};

const addPortfolioLink = () => {
  if (!portfolioUrl.value.trim()) {
    return;
  }
  
  // Basic URL validation
  let validUrl = portfolioUrl.value.trim();
  if (!validUrl.startsWith('http://') && !validUrl.startsWith('https://')) {
    validUrl = 'https://' + validUrl;
  }
  
  links.value.push({
    title: 'Portfolio',
    url: validUrl
  });
  
  // Clear the input field
  portfolioUrl.value = '';
};

const hasLinkWithTitle = (title: string) => {
  return links.value.some(link => link.title === title);
};

const handleLinkedinKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    event.stopPropagation();
    addLinkedinLink();
  }
};

const handleGithubKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    event.stopPropagation();
    addGithubLink();
  }
};

const handlePortfolioKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    event.stopPropagation();
    addPortfolioLink();
  }
};

const handleLinkKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    event.stopPropagation();
    addLink();
  }
};

// Profile photo handling methods
const handleProfilePhotoSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
      showToastNotification('Please select an image file', 'error');
      return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      showToastNotification('Image size should be less than 5MB', 'error');
      return;
    }
    
    profilePhoto.value = file;
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      profilePhotoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
  // Reset the input
  if (target) {
    target.value = '';
  }
};

const removeProfilePhoto = async () => {
  // If there's a new photo selected, just clear the local state
  if (profilePhoto.value) {
    profilePhoto.value = null;
    profilePhotoPreview.value = null;
    return;
  }
  
  // If there's an existing profile photo, remove it from server
  const existingProfilePhoto = existingAssets.value.find(asset => 
    asset.asset_type?.key === 'images' && asset.display_name === 'Profile Photo'
  );
  
  
  
  if (existingProfilePhoto) {
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      
      const response = await fetch('/profile/photo', {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json',
        },
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Remove the profile photo from the existing assets list
        const photoIndex = existingAssets.value.findIndex(asset => 
          asset.asset_type?.key === 'images' && asset.display_name === 'Profile Photo'
        );
        if (photoIndex !== -1) {
          existingAssets.value.splice(photoIndex, 1);
        }
        showToastNotification('Profile photo removed successfully', 'success');
      } else {
        showToastNotification(result.message || 'Failed to remove profile photo', 'error');
      }
    } catch (error) {
      showToastNotification('Failed to remove profile photo', 'error');
    }
  }
};

const triggerProfilePhotoInput = () => {
  profilePhotoInputRef.value?.click();
};

// Asset handling methods
const handleFileSelect = (event: Event, docType: string = 'other') => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    const files = Array.from(target.files);
    
    // Check file sizes before adding (max 10MB per file)
    for (const file of files) {
      if (file.size > 10 * 1024 * 1024) {
        showToastNotification('File size must be less than 10MB', 'error');
        return;
      }
    }
    
    // Add files with metadata
    files.forEach(file => {
      const fileWithMeta = {
        file: file,
        displayName: docType === 'other' ? file.name : docType === 'resume' ? 'Resume' : 'Cover Letter',
        docType: docType
      };
      newAssets.value.push(fileWithMeta as any);
    });
  }
};

const removeAsset = (index: number) => {
  newAssets.value.splice(index, 1);
};

const removeExistingAsset = async (index: number) => {
  const asset = existingAssets.value[index];
  

  
  if (!asset || !asset.id) {
    showToastNotification('Asset not found', 'error');
    return;
  }
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    const response = await fetch(`/profile/assets/${asset.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
      },
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Remove the asset from the existing assets list
      existingAssets.value.splice(index, 1);
      showToastNotification('Document removed successfully', 'success');
    } else {
      showToastNotification(result.message || 'Failed to remove document', 'error');
    }
      } catch (error) {
      showToastNotification('Failed to remove document', 'error');
    }
};

const triggerFileInput = (docType: string = 'other') => {
  // Create a new file input for the specific document type
  const input = document.createElement('input');
  input.type = 'file';
  input.multiple = docType === 'other';
  input.accept = '.pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.svg,.mp4,.avi,.mov';
  input.onchange = (event) => handleFileSelect(event, docType);
  input.click();
};



const cancel = () => {
  router.visit('/');
};

// Show toast notification
const showToastNotification = (message: string, type: 'success' | 'error' = 'success') => {
  toastMessage.value = message;
  toastType.value = type;
  showToast.value = true;
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    showToast.value = false;
  }, 5000);
};

// Handle form submission with toast feedback
const submit = () => {
  const formData = new FormData();
  
  // Add form data
  formData.append('name', form.name);
  formData.append('designation', form.designation);
  formData.append('bio', form.bio);
  formData.append('street', form.street);
  formData.append('city', form.city);
  formData.append('province', form.province);
  formData.append('country', form.country);
  
  // Add links
  links.value.forEach((link, index) => {
    formData.append(`links[${index}][title]`, link.title);
    formData.append(`links[${index}][url]`, link.url);
  });
  
  // Add profile photo
  if (profilePhoto.value) {
    formData.append('profile_photo', profilePhoto.value);
  }
  
  // Add assets
  newAssets.value.forEach((asset, index) => {
    formData.append(`assets[${index}]`, asset.file);
    formData.append(`asset_display_names[${index}]`, asset.displayName);
    formData.append(`asset_doc_types[${index}]`, asset.docType);
  });
  
  router.post('/profile', formData, {
    onSuccess: () => {
      showToastNotification('Profile updated successfully!', 'success');
    },
    onError: (errors) => {
      showToastNotification('Failed to update profile. Please check the form and try again.', 'error');
    },
  });
};

// Check for flash messages on mount
onMounted(() => {
  const flash = page.props.flash as any;
  if (flash?.success) {
    showToastNotification(flash.success, 'success');
  }
  if (flash?.error) {
    showToastNotification(flash.error, 'error');
  }
});
</script>

<template>
  <AppLayout>
    <!-- Toast Notification -->
    <v-snackbar
      v-model="showToast"
      :color="toastType === 'success' ? 'success' : 'error'"
      :timeout="5000"
      location="top"
    >
      <div class="d-flex align-center">
        <v-icon
          :icon="toastType === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'"
          class="mr-2"
        ></v-icon>
        <span>{{ toastMessage }}</span>
      </div>
      
      <template v-slot:actions>
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="showToast = false"
        ></v-btn>
      </template>
    </v-snackbar>

    <v-main>
      <v-container class="py-8 max-w-5xl">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6 md:gap-0 mb-6">
          <div>
            <v-btn
              icon="mdi-arrow-left"
              variant="outlined"
              @click="cancel"
              color="gray"
              class="mb-4"
            ></v-btn>
            <h1 :class="isDark ? 'text-2xl md:text-3xl font-bold text-gray-300' : 'text-2xl md:text-3xl font-bold text-gray-900'">Edit Profile</h1>
            <p :class="isDark ? 'text-gray-500 mt-2' : 'text-gray-800 mt-2'">Update your profile information</p>
          </div>
          <div class="flex flex-col md:flex-row gap-3 md:gap-4 md:justify-end">
            <v-btn
              variant="outlined"
              size="large"
              @click="cancel"
              class="w-full md:w-auto py-3 md:py-0"
            >
              Cancel
            </v-btn>
            
            <v-btn
              type="submit"
              color="primary"
              size="large"
              :loading="form.processing"
              :disabled="form.processing"
              @click="submit"
              class="w-full md:w-auto py-3 md:py-0"
            >
              Save Profile
            </v-btn>
          </div>
        </div>

        <v-row>
          <v-col cols="12">
            <v-form @submit.prevent="submit">
              <v-card class="pa-6">
                <!-- Account Information (Read-only) -->
                <div class="mb-0">
                  <div class="flex items-center mb-6">
                    <div class="w-1 h-8 bg-gradient-to-b from-purple-500 to-blue-500 rounded-full mr-4"></div>
                    <h2 :class="isDark ? 'text-2xl font-bold text-gray-300' : 'text-2xl font-bold text-gray-900'">Account Information</h2>
                  </div>
                  
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-text-field
                        :model-value="user.username"
                        label="Username"
                        readonly
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-account"
                        class="mb-4"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field
                        :model-value="user.email"
                        label="Email"
                        readonly
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-email"
                        class="mb-4"
                      />
                    </v-col>
                  </v-row>
                </div>

                <!-- Personal Information -->
                <div class="mb-0">
                  <div class="flex items-center mb-6">
                    <div class="w-1 h-8 bg-gradient-to-b from-green-500 to-teal-500 rounded-full mr-4"></div>
                    <h2 :class="isDark ? 'text-2xl font-bold text-gray-300' : 'text-2xl font-bold text-gray-900'">Personal Information</h2>
                  </div>
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="d-flex align-center gap-4">
                        <!-- Profile Photo Preview -->
                        <div class="relative">
                          <v-avatar
                            size="120"
                            class="border-2 border-gray-600"
                          >
                            <v-img
                              v-if="profilePhotoPreview"
                              :src="profilePhotoPreview"
                              cover
                            />
                            <v-img
                              v-else-if="existingAssets.find(asset => asset.asset_type?.key === 'images' && asset.display_name === 'Profile Photo')"
                              :src="existingAssets.find(asset => asset.asset_type?.key === 'images' && asset.display_name === 'Profile Photo')?.url"
                              cover
                            />
                            <v-icon
                              v-else
                              icon="mdi-account"
                              size="60"
                              color="gray"
                            />
                          </v-avatar>
                          
                          <!-- Remove button for new photo -->
                          <v-btn
                            v-if="profilePhotoPreview"
                            icon="mdi-close"
                            size="small"
                            color="error"
                            variant="tonal"
                            class="absolute -top-2 -right-2"
                            @click="removeProfilePhoto"
                          />
                        </div>
                        
                        <!-- Upload Controls -->
                        <div class="flex flex-col gap-2">
                          <v-btn
                            color="gray-300"
                            variant="outlined"
                            prepend-icon="mdi-camera"
                            @click="triggerProfilePhotoInput"
                          >
                            {{ profilePhotoPreview ? 'Change Photo' : 'Upload Photo' }}
                          </v-btn>
                          
                          <v-btn
                            v-if="profilePhotoPreview || existingAssets.find(asset => asset.asset_type?.key === 'images' && asset.display_name === 'Profile Photo')"
                            color="error"
                            variant="text"
                            size="small"
                            prepend-icon="mdi-delete"
                            @click="removeProfilePhoto"
                          >
                            Remove Photo
                          </v-btn>
                        </div>
                      </div>
                      
                      <!-- Hidden file input -->
                      <input
                        ref="profilePhotoInputRef"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleProfilePhotoSelect"
                      />
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.name"
                        label="Full Name"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-account-box"
                        :error-messages="form.errors.name"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.designation"
                        label="Designation/Job Title"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-briefcase"
                        :error-messages="form.errors.designation"
                      />
                    </v-col>
                  </v-row>
                  
                  <v-row>
                    <v-col cols="12">
                      <v-textarea
                        v-model="form.bio"
                        label="Bio"
                        variant="outlined"
                        prepend-inner-icon="mdi-text"
                        :error-messages="form.errors.bio"
                        rows="4"
                        counter="1000"
                        class="mb-4"
                      />
                    </v-col>
                  </v-row>
                </div>

                <!-- Address Information -->
                <div class="mb-0">
                  <div class="flex items-center mb-6">
                    <div class="w-1 h-8 bg-gradient-to-b from-orange-500 to-red-500 rounded-full mr-4"></div>
                    <h2 :class="isDark ? 'text-2xl font-bold text-gray-300' : 'text-2xl font-bold text-gray-900'">Address Information</h2>
                  </div>
                  
                  <v-row>
                    <v-col cols="12">
                      <v-text-field
                        v-model="form.street"
                        label="Street Address"
                        variant="outlined"
                        prepend-inner-icon="mdi-home"
                        :error-messages="form.errors.street"
                        density="compact"
                      />
                    </v-col>
                  </v-row>
                  
                  <v-row>
                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="form.city"
                        label="City"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-city"
                        :error-messages="form.errors.city"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="form.province"
                        label="Province/State"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-map"
                        :error-messages="form.errors.province"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="form.country"
                        label="Country"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-earth"
                        :error-messages="form.errors.country"
                      />
                    </v-col>
                  </v-row>
                </div>

                <!-- Links Section -->
                <div class="mt-4">
                  <div class="flex items-center mb-6">
                    <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full mr-4"></div>
                    <h2 :class="isDark ? 'text-2xl font-bold text-gray-300' : 'text-2xl font-bold text-gray-900'">Links</h2>
                  </div>
                  
                  <div>
                    <!-- LinkedIn Link -->
                    <div v-if="!hasLinkWithTitle('LinkedIn')">
                      <v-row>
                        <v-col cols="12" md="10">
                          <v-text-field
                            v-model="linkedinUrl"
                            label="LinkedIn Profile"
                            placeholder="https://linkedin.com/in/username"
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-linkedin"
                            @keydown="handleLinkedinKeydown"
                          />
                        </v-col>
                        
                        <v-col cols="12" md="2" class="self-center">
                          <v-btn
                            variant="outlined"
                            @click="addLinkedinLink"
                            :disabled="!linkedinUrl.trim()"
                            class="w-auto"
                            density="compact"
                          >
                            <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                            Add
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>

                    <!-- Github Link -->
                    <div v-if="!hasLinkWithTitle('Github')">
                      <v-row>
                        <v-col cols="12" md="10">
                          <v-text-field
                            v-model="githubUrl"
                            label="GitHub Profile"
                            placeholder="https://github.com/username"
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-github"

                            @keydown="handleGithubKeydown"
                          />
                        </v-col>
                        
                        <v-col cols="12" md="2" class="self-center">
                          <v-btn
                            variant="outlined"
                            @click="addGithubLink"
                            :disabled="!githubUrl.trim()"
                            class="w-auto"
                            density="compact"
                          >
                            <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                            Add
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>

                    <!-- Portfolio Link -->
                    <div v-if="!hasLinkWithTitle('Portfolio')">
                      <v-row>
                        <v-col cols="12" md="10">
                          <v-text-field
                            v-model="portfolioUrl"
                            label="Portfolio Website"
                            placeholder="https://yourportfolio.com"
                            variant="outlined"  
                            density="compact"
                            prepend-inner-icon="mdi-web"

                            @keydown="handlePortfolioKeydown"
                          />
                        </v-col>
                        
                        <v-col cols="12" md="2" class="self-center">
                          <v-btn
                            variant="outlined"
                            @click="addPortfolioLink"
                            :disabled="!portfolioUrl.trim()"
                            class="w-auto"
                            density="compact"
                          >
                            <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                            Add
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>

                    <!-- Custom Links -->
                    <div class="mb-4">
                      <h3 :class="isDark ? 'text-lg font-medium text-gray-300 mb-3' : 'text-lg font-medium text-gray-900 mb-3'">Add Custom Link</h3>
                      <v-row>
                        <v-col cols="12" md="5">
                          <v-text-field
                            v-model="newLinkTitle"
                            label="Link Title"    
                            density="compact"
                            placeholder="e.g., LinkedIn, Twitter"
                            variant="outlined"
                            prepend-inner-icon="mdi-tag"
                          />
                        </v-col>
                        <v-col cols="12" md="5">
                          <v-text-field
                            v-model="newLinkUrl"
                            label="URL"
                            placeholder="https://example.com"
                            variant="outlined"
                            prepend-inner-icon="mdi-link"
                            density="compact"
                            @keydown="handleLinkKeydown"
                          />
                        </v-col>
                        <v-col cols="12" md="2" class="self-center">
                          <v-btn
                            variant="outlined"
                            @click="addLink" 
                            density="compact"
                            :disabled="!newLinkTitle.trim() || !newLinkUrl.trim()"
                            class="w-auto"
                          >
                            <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                            Add
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>
                  </div>
                  
                  <!-- Display Added Links -->
                  <div v-if="links.length > 0" class="mb-4 mt-4">
                    <div class="flex flex-wrap gap-2">
                      <v-chip
                        v-for="(link, index) in links"
                        :key="index"
                        variant="outlined"
                        density="compact"
                        class="max-w-full text-sm !py-3 !px-4"
                      >
                        <div class="flex grow items-center gap-2 w-full">
                          <!-- Left: icon, title and URL -->
                          <div class="flex items-center flex-1 min-w-0 gap-2">
                            <v-icon 
                              :icon="getLinkIcon(link.title)" 
                              size="small"
                            ></v-icon>
                            <div class="flex-1 min-w-0 flex gap-1">
                              <span class="truncate font-medium max-w-[120px]">
                                {{ link.title }}:
                              </span>
                              <a
                                :href="link.url"
                                target="_blank"
                                :class="isDark ? 'text-blue-600 hover:underline truncate max-w-[200px]' : 'text-blue-950 hover:underline truncate max-w-[200px]'"
                              >
                                {{ link.url }}
                              </a>
                            </div>
                          </div>

                          <!-- Right: remove button -->
                          <div class="flex-none items-center shrink-0">
                            <v-btn
                              icon="mdi-delete"
                              size="x-small"
                              variant="text"
                              color="red"
                              @click="removeLink(index)"
                              class="ml-2"
                            ></v-btn>
                          </div>
                        </div>
                      </v-chip>
                    </div>
                  </div>
                </div>

                <!-- Assets Section -->
                <div class="mt-4">
                  <div class="flex items-center mb-6">
                    <div class="w-1 h-8 bg-gradient-to-b from-yellow-500 to-orange-500 rounded-full mr-4"></div>
                    <h2 :class="isDark ? 'text-2xl font-bold text-gray-300' : 'text-2xl font-bold text-gray-900'">Documents & Files</h2>
                  </div>
                  
                  <!-- Predefined Document Upload -->
                  <div class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div
                        v-for="doc in predefinedDocs"
                        :key="doc.key"
                        class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 transition-colors cursor-pointer"
                        @click="triggerFileInput(doc.key)"
                      >
                        <div class="flex items-center gap-3">
                          <v-icon :icon="doc.icon" size="large" color="blue"></v-icon>
                          <div>
                            <h4 :class="isDark ? 'font-medium text-gray-300' : 'font-medium text-gray-900'">{{ doc.label }}</h4>
                            <p class="text-sm text-gray-500">{{ doc.description }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- New Assets -->
                  <div v-if="newAssets.length > 0" class="mb-4">
                    <h3 :class="isDark ? 'text-lg font-medium text-gray-300 mb-3' : 'text-lg font-medium text-gray-900 mb-3'">New Files to Upload</h3>
                    <div class="flex flex-wrap gap-2">
                      <v-chip
                        v-for="(asset, index) in newAssets"
                        :key="`new-${index}`"
                        variant="outlined"
                        density="compact"
                        class="max-w-full text-sm !py-3 !px-4"
                      >
                        <div class="flex grow items-center gap-2 w-full">
                          <v-icon 
                            :icon="getFileIcon(asset.file.type)" 
                            :color="getFileColor(asset.file.type)"
                            size="small"
                          ></v-icon>
                          <div class="flex-1 min-w-0">
                            <div class="truncate font-medium">{{ asset.displayName }}</div>
                            <div class="text-xs text-gray-500">{{ formatFileSize(asset.file.size) }}</div>
                          </div>
                          <v-btn
                            icon="mdi-delete"
                            size="x-small"
                            variant="text"
                            color="red"
                            @click="removeAsset(index)"
                            class="ml-2"
                          ></v-btn>
                        </div>
                      </v-chip>
                    </div>
                  </div>

                  <!-- Existing Assets -->
                  <div v-if="existingAssets.length > 0" class="mb-4">
                    <h3 :class="isDark ? 'text-lg font-medium text-gray-300 mb-3' : 'text-lg font-medium text-gray-900 mb-3'">Uploaded Files</h3>
                    <div class="flex flex-wrap gap-2">
                      <v-chip
                        v-for="(asset, index) in existingAssets"
                        :key="`existing-${asset.id}`"
                        variant="outlined"
                        density="compact"
                        class="max-w-full text-sm !py-6 !px-4"
                      >
                        <div class="flex grow items-center gap-2 w-full">
                          <v-icon 
                            :icon="getFileIcon(asset.asset_type?.key || 'others')" 
                            :color="getFileColor(asset.asset_type?.key || 'others')"
                            size="small"
                          ></v-icon>
                          <div class="flex-1 min-w-0">
                            <div class="truncate font-medium">{{ asset.display_name }}</div>
                            <div class="text-xs text-gray-500">{{ asset.asset_type?.name || 'File' }}</div>
                          </div>
                          <div class="flex gap-1">
                            <v-btn
                              icon="mdi-download"
                              size="x-small"
                              variant="text"
                              color="blue"
                              :href="asset.url"
                              target="_blank"
                              class="ml-1"
                            ></v-btn>
                            <v-btn
                              icon="mdi-delete"
                              size="x-small"
                              variant="text"
                              color="red"
                              @click="removeExistingAsset(index)"
                              class="ml-1"
                            ></v-btn>
                          </div>
                        </div>
                      </v-chip>
                    </div>
                  </div>
                </div>

                <div class="flex gap-4 justify-end">
            <v-btn
              variant="outlined"
              size="large"
              @click="cancel"
              density="compact"
            >
              Cancel
            </v-btn>
            
            <v-btn
              type="submit"
              color="primary"
              size="large"
              density="compact"
              :loading="form.processing"
              :disabled="form.processing"
              @click="submit"
            >
              Save Profile
            </v-btn>
          </div>
              </v-card>
            </v-form>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </AppLayout>
</template>