<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ProjectCard from '@/components/ProjectCard.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import type { Project } from '@/types';
import { useAppearance } from '@/composables/useAppearance';

const page = usePage();
const { isDark } = useAppearance();

const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);
const technologies = computed(() => page.props.technologies as string[] || []);
const userProfile = computed(() => {
  const profile = page.props.userProfile as any || null;

  return profile;
});

// Category filter state
const selectedCategory = ref('all');

// Get categories that have projects
const availableCategories = computed(() => {
  const projectCategories = new Set(projects.value.map(project => project.category).filter(Boolean));
  return categories.value.filter(category => projectCategories.has(category.key));
});

// Filtered projects based on selected category
const filteredProjects = computed(() => {
  if (selectedCategory.value === 'all') {
    return projects.value;
  }
  return projects.value.filter(project => project.category === selectedCategory.value);
});

// Get user initials
const userInitials = computed(() => {
  if (!userProfile.value?.name) return 'U';
  return userProfile.value.name
    .split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
});

// Get current year for footer
const currentYear = new Date().getFullYear();

// Handle image error
const handleImageError = () => {
  
};

// Filter out duplicate documents based on display_name
const uniqueDocuments = computed(() => {
  if (!userProfile.value?.documents) return [];
  
  const seen = new Set();
  return userProfile.value.documents.filter((document: any) => {
    const displayName = document.display_name || '';
    if (seen.has(displayName)) {
      return false;
    }
    seen.add(displayName);
    return true;
  });
});

// Document helper functions
const getDocumentIcon = (filename: string): string => {
  const lowerFilename = filename.toLowerCase();
  if (lowerFilename.includes('resume') || lowerFilename.includes('cv')) {
    return 'mdi-file-document';
  } else if (lowerFilename.includes('cover') || lowerFilename.includes('letter')) {
    return 'mdi-file-document-outline';
  } else if (lowerFilename.includes('certificate') || lowerFilename.includes('cert')) {
    return 'mdi-certificate';
  } else if (lowerFilename.includes('portfolio')) {
    return 'mdi-briefcase';
  } else {
    return 'mdi-file-document-multiple';
  }
};

const getDocumentLabel = (filename: string): string => {
  const lowerFilename = filename.toLowerCase();
  if (lowerFilename.includes('resume') || lowerFilename.includes('cv')) {
    return 'Resume';
  } else if (lowerFilename.includes('cover') || lowerFilename.includes('letter')) {
    return 'Cover Letter';
  } else if (lowerFilename.includes('certificate') || lowerFilename.includes('cert')) {
    return 'Certificate';
  } else if (lowerFilename.includes('portfolio')) {
    return 'Portfolio';
  } else {
    // Return a cleaned version of the filename
    return filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
  }
};

// Link icon helper function
const getLinkIcon = (linkType: string): string => {
  const lowerType = linkType.toLowerCase();
  if (lowerType.includes('github')) {
    return 'mdi-github';
  } else if (lowerType.includes('linkedin')) {
    return 'mdi-linkedin';
  } else if (lowerType.includes('portfolio') || lowerType.includes('website')) {
    return 'mdi-web';
  } else if (lowerType.includes('twitter')) {
    return 'mdi-twitter';
  } else if (lowerType.includes('facebook')) {
    return 'mdi-facebook';
  } else if (lowerType.includes('instagram')) {
    return 'mdi-instagram';
  } else if (lowerType.includes('youtube')) {
    return 'mdi-youtube';
  } else {
    return 'mdi-link';
  }
};
</script>

<template>
  <!-- Public Projects Portfolio Website -->
  <div :class="isDark ? '!bg-gradient-to-br from-[#0c0c0c] to-[#1A1A1C]' : '!bg-gradient-to-br from-[#bfbfbf] to-[#F5F5F5]'">
          <!-- Profile Header Section -->
      <div class="px-8 pt-8 pb-4">
        <div class="flex flex-row justify-between">
        <div class="flex items-center gap-3 mb-4">
          <img 
          :src="isDark ? '/images/logos/dark_mode.png' : '/images/logos/light_mode.png'" 
          alt="LogoApp" 
          class="w-32 h-12 rounded mr-2 object-cover"
        >
        </div>
        <div class="flex justify-end mb-4">
          <ThemeToggle class="mr-4" />
        </div>
        </div>
        <div class="w-full h-px bg-gray-800"></div>
      </div>
      <section :class="isDark ? 'bg-black !mx-8 !my-8 rounded-xl' : '!bg-gray-100 !mx-8 !my-8 rounded-xl'">
      <div class="mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <!-- Theme Toggle Button -->

        
        <!-- Desktop Layout (unchanged) -->
        <div class="hidden md:flex flex-row items-start space-x-8">
          <!-- Profile Avatar -->
          <div class="flex-shrink-0 mr-8 ml-8">
            <div v-if="userProfile?.profile_photo_url" class="w-24 h-24 rounded-full overflow-hidden">
              <v-img
                :src="userProfile.profile_photo_url"
                :alt="userProfile?.name || 'Profile Photo'"
                :class="isDark ? 'w-full h-full object-cover bg-gradient-to-br from-blue-800 to-blue-950' : 'w-full h-full object-cover bg-gradient-to-br from-[#f5f5f5] to-[#bfbfbf]'"
                @error="handleImageError"
              />
            </div>
            <div v-else :class="isDark ? 'w-24 h-24 bg-primary rounded-full flex items-center justify-center text-2xl font-bold text-gray-300' : 'w-24 h-24 bg-white border-2 border-gray-950 rounded-full flex items-center justify-center text-2xl font-bold text-gray-300'">
              <span>{{ userInitials }}</span>
            </div>
          </div>
          
          <!-- Profile Info -->
          <div class="flex-1">
            <div class="flex flex-row gap-8 justify-between">
            <div>
            <h1 :class="isDark ? 'text-3xl font-bold text-gray-300 mb-2' : 'text-3xl font-bold text-gray-900 mb-2'">
              {{ userProfile?.name || 'Your Name' }}
            </h1>
            </div>
            <div v-if="uniqueDocuments.length > 0" class="flex flex-wrap gap-3 items-center">
              <v-btn
                v-for="document in uniqueDocuments"
                :key="`${document.id}-${document.display_name}`"
                variant="elevated"
                size="small"
                :href="document.url || document.filename"
                target="_blank"
                :class="isDark ? '!bg-black !text-gray-300 !border-gray-800 hover:bg-gray-700 border' : '!bg-gray-100 !text-gray-900 !border-gray-800 hover:bg-gray-700 border'"
              >
                <v-icon 
                  :icon="getDocumentIcon(document.display_name || '')" 
                  class="mr-2"
                  :color="isDark ? 'gray-300' : 'gray-800'"
                ></v-icon>
                {{ document.display_name || 'Document' }}
                <v-icon icon="mdi-download" size="small" class="ml-2"></v-icon>
              </v-btn>
            </div>
            </div>
            <p :class="isDark ? 'text-xl text-blue-400 mb-4' : 'text-xl text-blue-950 mb-4'">
              {{ userProfile?.designation || 'Full Stack Developer' }}
            </p>
            <p :class="isDark ? 'text-gray-400 text-lg mb-6 max-w-3xl' : 'text-gray-800 text-lg mb-6 max-w-3xl'">
              {{ userProfile?.bio || 'Passionate developer with experience building modern web applications. I love creating beautiful, functional, and user-friendly solutions.' }}
            </p>
            
            <!-- Contact Info -->
            <div class="flex items-center space-x-6 mb-6 gap-8">
              <div v-if="userProfile?.city || userProfile?.country" :class="isDark ? 'flex items-center text-gray-400' : 'flex items-center text-gray-800'">
                <v-icon icon="mdi-map-marker-outline" size="small" variant="outlined" :class="isDark ? 'mr-2 text-gray-400' : 'mr-2 text-gray-800'"></v-icon>
                <span>{{ [userProfile?.city, userProfile?.country].filter(Boolean).join(', ') }}</span>
              </div>
              <div v-if="userProfile?.email" :class="isDark ? 'flex items-center text-gray-400' : 'flex items-center text-gray-800'">
                <v-icon icon="mdi-email-outline" size="small" :class="isDark ? 'mr-2 text-gray-400' : 'mr-2 text-gray-800'"></v-icon>
                <span>{{ userProfile.email }}</span>
              </div>
            </div>
            
            <!-- Social Links -->
            <div class="flex flex-wrap gap-3">
              <v-btn
                v-for="link in userProfile?.links || []"
                :key="`${link.title}-${link.url}`"
                variant="outlined"
                size="medium"
                :href="link.url"
                target="_blank"
                :class="isDark ? '!bg-black text-gray-300 !border-gray-800 hover:!border-gray-400 hover:!bg-gray-900 transition-all duration-200 px-4 py-2' : '!bg-gray-100 text-gray-900 !border-gray-800 hover:!border-gray-400 hover:!bg-gray-900 transition-all duration-200 px-4 py-2'"
              >
                <v-icon :icon="getLinkIcon(link.type || link.title)" class="mr-2" size="18"></v-icon>
                <span class="font-medium text-sm">{{ link.title.toUpperCase() }}</span>
              </v-btn>
            </div>
          </div>
        </div>

        <!-- Mobile Layout -->
        <div class="md:hidden flex flex-col items-center">
          <!-- Documents at top -->
          <div v-if="uniqueDocuments.length > 0" class="flex flex-wrap gap-3 justify-center w-full">
            <v-btn
              v-for="document in uniqueDocuments"
              :key="`${document.id}-${document.display_name}`"
              variant="elevated"
              size="small"
              :href="document.url || document.filename"
              target="_blank"
              :class="isDark ? '!bg-black !text-gray-300 !border-gray-800 hover:bg-gray-700 border' : '!bg-gray-100 !text-gray-900 !border-gray-800 hover:bg-gray-700 border'"
            >
              <v-icon 
                :icon="getDocumentIcon(document.display_name || '')" 
                class="mr-2"
                :color="isDark ? 'gray-300' : 'gray-800'"
              ></v-icon>
              {{ document.display_name || 'Document' }}
              <v-icon icon="mdi-download" size="small" class="ml-2"></v-icon>
            </v-btn>
          </div>

          <!-- Profile Avatar in center -->
          <div class="flex-shrink-0 mt-4 mb-4">
            <div v-if="userProfile?.profile_photo_url" class="w-32 h-32 rounded-full overflow-hidden">
              <v-img
                :src="userProfile.profile_photo_url"
                :alt="userProfile?.name || 'Profile Photo'"
                :class="isDark ? 'w-full h-full object-cover bg-gradient-to-br from-blue-800 to-blue-950' : 'w-full h-full object-cover bg-gradient-to-br from-[#f5f5f5] to-[#bfbfbf]'"
                @error="handleImageError"
              />
            </div>
            <div v-else class="w-32 h-32 bg-primary rounded-full flex items-center justify-center">
              <span :class="isDark ? 'text-3xl font-bold text-gray-300' : 'text-3xl font-bold text-gray-900'">{{ userInitials }}</span>
            </div>
          </div>
          
          <!-- Profile Info below image -->
          <div class="flex-1 text-center">
            <h1 :class="isDark ? 'text-2xl font-bold text-gray-300 mb-2' : 'text-2xl font-bold text-gray-900 mb-2'">
              {{ userProfile?.name || 'Your Name' }}
            </h1>
            <p :class="isDark ? 'text-lg text-blue-400 mb-4' : 'text-lg text-blue-950 mb-4'">
              {{ userProfile?.designation || 'Full Stack Developer' }}
            </p>
            <p :class="isDark ? 'text-gray-400 text-base mb-6' : 'text-gray-800 text-base mb-6'">
              {{ userProfile?.bio || 'Passionate developer with experience building modern web applications. I love creating beautiful, functional, and user-friendly solutions.' }}
            </p>
            
            <!-- Contact Info -->
            <div class="flex flex-col items-center space-y-3 mb-6">
              <div v-if="userProfile?.city || userProfile?.country" :class="isDark ? 'flex items-center text-gray-400' : 'flex items-center text-gray-800'">
                <v-icon icon="mdi-map-marker-outline" size="small" variant="outlined" :class="isDark ? 'mr-2 text-gray-400' : 'mr-2 text-gray-800'"></v-icon>
                <span>{{ [userProfile?.city, userProfile?.country].filter(Boolean).join(', ') }}</span>
              </div>
              <div v-if="userProfile?.email" :class="isDark ? 'flex items-center text-gray-400' : 'flex items-center text-gray-800'">
                <v-icon icon="mdi-email-outline" size="small" :class="isDark ? 'mr-2 text-gray-400' : 'mr-2 text-gray-800'"></v-icon>
                <span>{{ userProfile.email }}</span>
              </div>
            </div>
            
            <!-- Social Links -->
            <div class="flex flex-wrap gap-3 justify-center">
              <v-btn
                v-for="link in userProfile?.links || []"
                :key="`${link.title}-${link.url}`"
                variant="outlined"
                size="medium"
                :href="link.url"
                target="_blank"
                :class="isDark ? '!bg-black text-gray-300 !border-gray-800 hover:!border-gray-400 hover:!bg-gray-900 transition-all duration-200 px-4 py-2' : '!bg-gray-100 text-gray-900 !border-gray-800 hover:!border-gray-400 hover:!bg-gray-900 transition-all duration-200 px-4 py-2'"
              >
                <v-icon :icon="getLinkIcon(link.type || link.title)" class="mr-2" size="18"></v-icon>
                <span class="font-medium text-sm">{{ link.title.toUpperCase() }}</span>
              </v-btn>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <main class="md:!mx-8 !mx-0 !my-8">
      <div class="mx-auto px-6 md:py-12 py-2">
        <!-- Category Filters -->
        <div class="mb-8">
          <div class="flex flex-wrap gap-3">
            <v-btn
              variant="elevated"
              :class="[
                selectedCategory === 'all'
                  ? (isDark
                      ? '!bg-gradient-to-br !from-blue-950 !to-blue-800 !text-white rounded-lg'
                      : '!bg-gray-950 !text-white rounded-lg')
                  : (isDark
                      ? '!bg-black text-gray-300 !border !border-gray-800 rounded-lg'
                      : '!bg-white text-gray-800 !border !border-gray-300 rounded-lg')
              ]"
              @click="selectedCategory = 'all'"
            >
              All Projects
            </v-btn>
            <v-btn
              v-for="category in availableCategories"
              :key="category.key"
              variant="elevated"
              :color="selectedCategory === category.key ? ' bg-gradient-to-br from-blue-800 to-blue-950' : 'gray'"
              :class="[
                selectedCategory === category.key
                  ? (isDark
                      ? '!bg-gradient-to-br !from-blue-950 !to-blue-800 !text-white rounded-lg'
                      : '!bg-gray-950 !text-white rounded-lg')
                  : (isDark
                      ? '!bg-black text-gray-300 !border !border-gray-800 rounded-lg'
                      : '!bg-white text-gray-800 !border !border-gray-300 rounded-lg')
              ]"
              @click="selectedCategory = category.key"
            >
              {{ category.name }}
            </v-btn>
          </div>
        </div>

        <!-- Technologies Section -->
        <div v-if="technologies.length > 0" class="mb-12">
          <h2 :class="isDark ? 'text-2xl font-bold text-white mb-6' : 'text-2xl font-bold text-gray-900 mb-6'">Technologies Used:</h2>
          <div class="flex flex-wrap gap-3">
            <v-chip
              v-for="tech in technologies"
              :key="tech"
              size="large"
              :class="isDark ? '!border-2 !border-blue-900 !bg-[#23153A] !text-blue-400' : '!border-2 !border-black !bg-white !text-black'"
            >
              {{ tech }}
            </v-chip>
          </div>
        </div>

        <!-- Projects Grid -->
        <section v-if="filteredProjects.length > 0" class="projects-section">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div 
              v-for="project in filteredProjects" 
              :key="project.id" 
              class="project-item transform transition-all duration-300 hover:scale-105"
            >
              <ProjectCard
                :project="project"
                :categories="categories"
                :statuses="statuses"
                :preview-settings="project.settings"
                class="h-full"
              />
            </div>
          </div>
        </section>

        <!-- No Projects State -->
        <section v-else class="no-projects-section text-center py-20">
          <div class="max-w-md mx-auto">
            <v-icon 
              icon="mdi-folder-open" 
              size="64" 
              color="gray-600" 
              class="mb-6"
            ></v-icon>
            <h3 :class="isDark ? 'text-2xl font-bold text-gray-300 mb-4' : 'text-2xl font-bold text-gray-900 mb-4'">
              {{ selectedCategory === 'all' ? 'No Projects Available' : 'No Projects in This Category' }}
            </h3>
            <p :class="isDark ? 'text-gray-500 mb-6' : 'text-gray-800 mb-6'">
              {{ selectedCategory === 'all' 
                ? 'There are no public projects available at the moment.' 
                : `No projects found in the "${categories.find(c => c.key === selectedCategory)?.name || selectedCategory}" category.` 
              }}
            </p>
            <v-btn
              v-if="selectedCategory !== 'all'"
              variant="outlined"
              color="primary"
              @click="selectedCategory = 'all'"
            >
              View All Projects
            </v-btn>
          </div>
        </section>
      </div>
    </main>

    <!-- Footer -->
    <footer :class="isDark ? 'bg-black border-t border-gray-800 py-8' : 'bg-gray-100 border-t border-gray-800 py-8'">
      <div class="container mx-auto px-6 text-center">
        <p :class="isDark ? 'text-gray-400' : 'text-gray-800'">
          © {{ currentYear }} {{ userProfile?.name || 'Portfolio' }}. All rights reserved.
        </p>
      </div>
    </footer>
  </div>
</template>

<style>
html, body {
  background: linear-gradient(to bottom right, #0c0c0c, #1A1A1C) !important;
  height: 100%;
  margin: 0;
  padding: 0;
}
</style>
