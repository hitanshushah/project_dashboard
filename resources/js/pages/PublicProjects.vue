<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ProjectCard from '@/components/ProjectCard.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import type { Project } from '@/types';

const page = usePage();

const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);
const technologies = computed(() => page.props.technologies as string[] || []);
const userProfile = computed(() => {
  const profile = page.props.userProfile as any || null;
  console.log('User Profile Data:', profile);
  if (profile?.links) {
    console.log('Available links:', profile.links);
    profile.links.forEach((link: any, index: number) => {
      console.log(`Link ${index}:`, link);
    });
  }
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
  console.log('Profile image failed to load');
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

const getDocumentColor = (filename: string): string => {
  const lowerFilename = filename.toLowerCase();
  if (lowerFilename.includes('resume') || lowerFilename.includes('cv')) {
    return 'blue';
  } else if (lowerFilename.includes('cover') || lowerFilename.includes('letter')) {
    return 'green';
  } else if (lowerFilename.includes('certificate') || lowerFilename.includes('cert')) {
    return 'orange';
  } else if (lowerFilename.includes('portfolio')) {
    return 'purple';
  } else {
    return 'gray';
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
  <div class="bg-gradient-to-br from-[#0c0c0c] to-[#1A1A1C]">
          <!-- Profile Header Section -->
      <div class="px-8 pt-8 pb-4">
        <div class="flex flex-row justify-between">
        <div class="flex items-center gap-3 mb-4">
          <v-icon icon="mdi-periodic-table" color="blue" size="large"></v-icon>
          <h1 class="text-2xl font-bold text-gray-300">Projects Dashboard</h1>
        </div>
        <div class="flex justify-end mb-4">
          <ThemeToggle class="mr-4" />
        </div>
        </div>
        <div class="w-full h-px bg-gray-800"></div>
      </div>
      <section class="bg-black !mx-8 !my-8 rounded-xl">
      <div class="mx-auto px-6 py-12">
        <!-- Theme Toggle Button -->

        
        <div class="flex items-start space-x-8">
          <!-- Profile Avatar -->
          <div class="flex-shrink-0 mr-8 ml-8">
            <div v-if="userProfile?.profile_photo_url" class="w-24 h-24 rounded-full overflow-hidden">
              <v-img
                :src="userProfile.profile_photo_url"
                :alt="userProfile?.name || 'Profile Photo'"
                class="w-full h-full object-cover bg-gradient-to-br from-blue-800 to-blue-950"
                @error="handleImageError"
              />
            </div>
            <div v-else class="w-24 h-24 bg-primary rounded-full flex items-center justify-center">
              <span class="text-2xl font-bold text-gray-300">{{ userInitials }}</span>
            </div>
          </div>
          
          <!-- Profile Info -->
          <div class="flex-1">
            <div class="flex flex-row gap-8 justify-between">
            <div>
            <h1 class="text-3xl font-bold text-gray-300 mb-2">
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
                class="!bg-black !text-gray-300 !border-gray-800 hover:bg-gray-700 border"
              >
                <v-icon 
                  :icon="getDocumentIcon(document.display_name || '')" 
                  class="mr-2"
                  :color="getDocumentColor(document.display_name || '')"
                ></v-icon>
                {{ document.display_name || 'Document' }}
                <v-icon icon="mdi-download" size="small" class="ml-2"></v-icon>
              </v-btn>
            </div>
            </div>
            <p class="text-xl text-blue-400 mb-4">
              {{ userProfile?.designation || 'Full Stack Developer' }}
            </p>
            <p class="text-gray-400 text-lg mb-6 max-w-3xl">
              {{ userProfile?.bio || 'Passionate developer with experience building modern web applications. I love creating beautiful, functional, and user-friendly solutions.' }}
            </p>
            
            <!-- Contact Info -->
            <div class="flex items-center space-x-6 mb-6 gap-8">
              <div v-if="userProfile?.city || userProfile?.country" class="flex items-center text-gray-400">
                <v-icon icon="mdi-map-marker-outline" size="small" variant="outlined" class="mr-2 text-gray-400"></v-icon>
                <span>{{ [userProfile?.city, userProfile?.country].filter(Boolean).join(', ') }}</span>
              </div>
              <div v-if="userProfile?.email" class="flex items-center text-gray-400">
                <v-icon icon="mdi-email-outline" size="small" class="mr-2 text-gray-400"></v-icon>
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
                class="!bg-black text-gray-300 !border-gray-800 hover:!border-gray-400 hover:!bg-gray-900 transition-all duration-200 px-4 py-2"
              >
                <v-icon :icon="getLinkIcon(link.type || link.title)" class="mr-2" size="18"></v-icon>
                <span class="font-medium text-sm">{{ link.title.toUpperCase() }}</span>
              </v-btn>
            </div>

            <!-- Document Downloads -->
            
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <main class="!mx-8 !my-8">
      <div class="mx-auto px-6 py-12">
        <!-- Category Filters -->
        <div class="mb-8">
          <div class="flex flex-wrap gap-3">
            <v-btn
              variant="elevated"
              :class="[
                selectedCategory === 'all' 
                  ? '!bg-gradient-to-br !from-blue-950 !to-blue-800 !text-white rounded-lg' 
                  : '!bg-black text-gray-300 !border !border-gray-800 rounded-lg'
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
                  ? '!bg-gradient-to-br !from-blue-950 !to-blue-800 !text-white rounded-lg' 
                  : '!bg-black text-gray-300 !border !border-gray-800 rounded-lg'
              ]"
              @click="selectedCategory = category.key"
            >
              {{ category.name }}
            </v-btn>
          </div>
        </div>

        <!-- Technologies Section -->
        <div v-if="technologies.length > 0" class="mb-12">
          <h2 class="text-2xl font-bold text-white mb-6">Technologies Used:</h2>
          <div class="flex flex-wrap gap-3">
            <v-chip
              v-for="tech in technologies"
              :key="tech"
              size="large"
              class="!border-2 !border-blue-900 !bg-[#23153A] !text-blue-400"
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
            <h3 class="text-2xl font-bold text-gray-300 mb-4">
              {{ selectedCategory === 'all' ? 'No Projects Available' : 'No Projects in This Category' }}
            </h3>
            <p class="text-gray-500 mb-6">
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
    <footer class="bg-black border-t border-gray-800 py-8">
      <div class="container mx-auto px-6 text-center">
        <p class="text-gray-400">
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
