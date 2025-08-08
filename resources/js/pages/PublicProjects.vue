<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ProjectCard from '@/components/ProjectCard.vue';
import SearchFilters from '@/components/SearchFilters.vue';
import type { Project } from '@/types';

const page = usePage();

const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);
const technologies = computed(() => page.props.technologies as string[] || []);
const userProfile = computed(() => page.props.userProfile as any || null);
const currentFilters = computed(() => page.props.filters as {
  search: string;
  categories: string[];
  statuses: string[];
  technologies: string[];
  sort_by: string;
  sort_direction: string;
} || {
  search: '',
  categories: [],
  statuses: [],
  technologies: [],
  sort_by: 'created_at',
  sort_direction: 'desc'
});

// Reference to SearchFilters component
const searchFiltersRef = ref<InstanceType<typeof SearchFilters>>();

// Computed property for checking if filters are active
const hasActiveFilters = computed(() => {
  return currentFilters.value.search ||
         currentFilters.value.categories.length > 0 ||
         currentFilters.value.statuses.length > 0 ||
         currentFilters.value.technologies.length > 0 ||
         currentFilters.value.sort_by !== 'created_at' ||
         currentFilters.value.sort_direction !== 'desc';
});

// Clear filters function
const clearFilters = () => {
  if (searchFiltersRef.value) {
    searchFiltersRef.value.clearFilters();
  }
};

// Get current year for footer
const currentYear = new Date().getFullYear();
</script>

<template>
  <!-- Public Projects Portfolio Website -->
  <div class="public-portfolio">
    <!-- Hero Section -->
    <section class="hero-section text-white">
      <!-- Floating geometric elements -->
      <div class="floating-elements">
        <div class="floating-circle circle-1"></div>
        <div class="floating-circle circle-2"></div>
        <div class="floating-circle circle-3"></div>
        <div class="floating-triangle triangle-1"></div>
        <div class="floating-triangle triangle-2"></div>
      </div>
      
      <div class="container mx-auto px-6 py-20">
        <div class="text-center max-w-4xl mx-auto">
          <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent hero-title">
            My Project Portfolio
          </h1>
          <p class="text-xl md:text-2xl text-gray-100 mb-8 leading-relaxed max-w-3xl mx-auto opacity-90">
            Welcome to my collection of public projects. Explore my work, technologies, and creative solutions.
          </p>
          <div class="flex justify-center space-x-4">
            <v-chip
              v-if="projects.length > 0"
              size="large"
              color="rgba(255,255,255,0.15)"
              text-color="white"
              class="font-semibold backdrop-blur-sm border border-white/30 hover:border-white/50 transition-all duration-300"
            >
              <v-icon icon="mdi-folder-multiple" size="small" class="mr-2"></v-icon>
              {{ projects.length }} Project{{ projects.length !== 1 ? 's' : '' }} Available
            </v-chip>
          </div>
        </div>
      </div>
      
      <!-- Wave divider -->
      <div class="wave-divider">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="w-full h-16 text-white fill-current">
          <path d="M0,60 C200,100 400,20 600,60 C800,100 1000,20 1200,60 L1200,120 L0,120 Z"></path>
        </svg>
      </div>
    </section>

    <!-- Main Content -->
    <main class="main-content bg-gray-50 min-h-screen">
      <div class="container mx-auto px-6 py-12">
        
        <!-- Search and Filter Controls (if projects exist) -->
        <div v-if="projects.length > 0" class="mb-12">
          <SearchFilters
            ref="searchFiltersRef"
            :categories="categories"
            :statuses="statuses"
            :technologies="technologies"
            :current-filters="currentFilters"
            :results-count="projects.length"
            class="bg-white rounded-xl shadow-lg p-6"
          />
        </div>

        <!-- Projects Grid -->
        <section v-if="projects.length > 0" class="projects-section">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div 
              v-for="project in projects" 
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
          <div class="max-w-2xl mx-auto">
            <v-icon 
              :icon="hasActiveFilters ? 'mdi-filter-off' : 'mdi-folder-open-outline'" 
              size="120" 
              color="grey-lighten-1" 
              class="mb-8"
            ></v-icon>
            
            <h2 class="text-4xl font-bold text-gray-700 mb-6">
              <span v-if="hasActiveFilters">
                No projects match your criteria
              </span>
              <span v-else>
                No public projects yet
              </span>
            </h2>
            
            <p class="text-xl text-gray-500 mb-10 leading-relaxed">
              <span v-if="hasActiveFilters">
                Try adjusting your search and filter criteria to discover more projects.
              </span>
              <span v-else>
                Public projects will appear here once they are made available.
              </span>
            </p>
            
            <v-btn
              v-if="hasActiveFilters"
              color="primary"
              size="large"
              variant="outlined"
              @click="clearFilters"
              class="font-semibold"
            >
              Clear All Filters
            </v-btn>
          </div>
        </section>

        <!-- Technologies Section (if projects exist) -->
        <section v-if="projects.length > 0 && technologies.length > 0" class="technologies-section mt-16 py-12 bg-white rounded-xl shadow-lg">
          <div class="text-center mb-8">
            <h3 class="text-3xl font-bold text-gray-800 mb-4">Technologies I Use</h3>
            <p class="text-gray-600 text-lg">A collection of tools and technologies featured in my projects</p>
          </div>
          
          <div class="flex flex-wrap justify-center gap-3 px-6">
            <v-chip
              v-for="tech in technologies"
              :key="tech"
              size="large"
              color="primary"
              variant="outlined"
              class="font-medium hover:bg-primary hover:text-white transition-colors duration-200"
            >
              {{ tech }}
            </v-chip>
          </div>
        </section>
      </div>
    </main>

    <!-- Footer -->
    <footer class="footer bg-gray-900 text-white py-12">
      <div class="container mx-auto px-6">
        <div class="text-center">
          <h4 class="text-2xl font-bold mb-4">Project Portfolio</h4>
          <p class="text-gray-400 mb-6">
            Showcasing innovation through code and creativity
          </p>
          
          <!-- Social Links placeholder -->
          <div class="flex justify-center space-x-6 mb-6">
            <v-btn
              icon="mdi-github"
              variant="text"
              color="white"
              size="large"
              href="#"
              target="_blank"
            ></v-btn>
            <v-btn
              icon="mdi-linkedin"
              variant="text"
              color="white"
              size="large"
              href="#"
              target="_blank"
            ></v-btn>
            <v-btn
              icon="mdi-email"
              variant="text"
              color="white"
              size="large"
              href="mailto:"
            ></v-btn>
          </div>
          
          <div class="border-t border-gray-700 pt-6">
            <p class="text-sm text-gray-500">
              © {{ currentYear }} Project Portfolio. Built with passion and code.
            </p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.public-portfolio {
  min-height: 100vh;
  background: #f8fafc;
}

.hero-section {
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%);
  background-size: 400% 400%;
  animation: gradientShift 15s ease infinite;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(79, 172, 254, 0.2) 0%, rgba(245, 87, 108, 0.2) 100%);
  z-index: 1;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.hero-section > * {
  position: relative;
  z-index: 2;
}

/* Floating elements */
.floating-elements {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  overflow: hidden;
}

.floating-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.circle-1 {
  width: 120px;
  height: 120px;
  top: 20%;
  left: 10%;
  animation: float 20s ease-in-out infinite;
}

.circle-2 {
  width: 80px;
  height: 80px;
  top: 60%;
  right: 15%;
  animation: float 25s ease-in-out infinite reverse;
}

.circle-3 {
  width: 60px;
  height: 60px;
  top: 40%;
  left: 80%;
  animation: float 18s ease-in-out infinite;
}

.floating-triangle {
  position: absolute;
  width: 0;
  height: 0;
  border-style: solid;
}

.triangle-1 {
  border-left: 30px solid transparent;
  border-right: 30px solid transparent;
  border-bottom: 52px solid rgba(255, 255, 255, 0.08);
  top: 30%;
  right: 25%;
  animation: float 22s ease-in-out infinite;
}

.triangle-2 {
  border-left: 25px solid transparent;
  border-right: 25px solid transparent;
  border-bottom: 43px solid rgba(255, 255, 255, 0.06);
  bottom: 30%;
  left: 20%;
  animation: float 28s ease-in-out infinite reverse;
}

@keyframes float {
  0%, 100% { 
    transform: translateY(0px) rotate(0deg);
    opacity: 0.7;
  }
  50% { 
    transform: translateY(-30px) rotate(180deg);
    opacity: 0.9;
  }
}

.hero-title {
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  animation: titleGlow 3s ease-in-out infinite alternate;
}

@keyframes titleGlow {
  from { 
    filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.1));
  }
  to { 
    filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.2));
  }
}

.wave-divider {
  position: relative;
  z-index: 2;
  transform: translateY(-1px);
}

.container {
  max-width: 1200px;
}

.project-item {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
}

.text-transparent {
  color: transparent;
}

.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Custom scrollbar for better aesthetics */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>