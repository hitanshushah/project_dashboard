<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import SearchFilters from '@/components/SearchFilters.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { usePublicProjects } from '@/composables/usePublicProjects';
import type { Project } from '@/types';

const page = usePage();

const flash = computed(() => page.props.flash as { success?: string });
const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);
const technologies = computed(() => page.props.technologies as string[] || []);
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

// Computed property for checking if filters are active (based on current filters prop)
const hasActiveFilters = computed(() => {
  return currentFilters.value.search ||
         currentFilters.value.categories.length > 0 ||
         currentFilters.value.statuses.length > 0 ||
         currentFilters.value.technologies.length > 0 ||
         currentFilters.value.sort_by !== 'created_at' ||
         currentFilters.value.sort_direction !== 'desc';
});

// Toggle state for showing public/hidden projects
const selectedView = ref('hidden'); // 'hidden' or 'public'

// Group projects by public status
const publicProjects = computed(() => projects.value.filter(project => project.is_public));
const privateProjects = computed(() => projects.value.filter(project => !project.is_public));

// Confirmation modal state
const showConfirmationModal = ref(false);
const projectToToggle = ref<Project | null>(null);
const toggleAction = ref<'public' | 'hidden'>('public');

const createProject = () => {
  router.visit('/projects/create');
};

const editProject = (projectId: number) => {
  router.visit(`/projects/${projectId}/edit`);
};

const openPublicPreview = () => {
      // Open the public projects page in a new tab
    window.open('/public-projects', '_blank');
};

const toggleProjectVisibility = (project: Project) => {
  projectToToggle.value = project;
  toggleAction.value = project.is_public ? 'hidden' : 'public';
  showConfirmationModal.value = true;
};

const confirmToggle = () => {
  if (!projectToToggle.value) return;
  
  const form = useForm({
    project_id: projectToToggle.value.id,
    is_public: !projectToToggle.value.is_public
  });
  
  form.patch(`/projects/${projectToToggle.value.id}/toggle-visibility`, {
    onSuccess: () => {
      showConfirmationModal.value = false;
      projectToToggle.value = null;
      // Refresh the page to get updated project data
      router.reload();
    },
    onError: (errors) => {
      
    }
  });
};

const cancelToggle = () => {
  showConfirmationModal.value = false;
  projectToToggle.value = null;
};

// Clear filters function for accessing from SearchFilters component
const clearFilters = () => {
  if (searchFiltersRef.value) {
    searchFiltersRef.value.clearFilters();
  }
};


</script>

<template>
  <AppLayout>
    <v-main>
      <v-container class="py-8 !max-w-none !px-8">
        <v-alert
          v-if="flash?.success"
          type="success"
          variant="tonal"
          class="mb-6"
          closable
        >
          {{ flash.success }}
        </v-alert>

        <!-- Header with Action Buttons -->
        <div class="d-flex justify-space-between align-center mb-6">
          <h1 class="text-3xl font-bold text-white">
            My Projects
          </h1>
          <div class="d-flex gap-3">
            <v-btn
              v-if="publicProjects.length > 0"
              prepend-icon="mdi-open-in-new"
              variant="text"
              @click="openPublicPreview"
              :title="`Preview your ${publicProjects.length} public project${publicProjects.length !== 1 ? 's' : ''}`"
              :class="[
                  '!bg-black text-white !border-gray-600 border rounded-lg !text-sm py-2 px-4 ml-4' 
                ]"
            >
              Preview Public
              <v-badge
                :content="publicProjects.length"
                inline
                class="ml-2"
              ></v-badge>
            </v-btn>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="createProject"
            >
              Create Project
            </v-btn>
          </div>
        </div>

        <!-- Search and Filter Controls -->
        <SearchFilters
          ref="searchFiltersRef"
          :categories="categories"
          :statuses="statuses"
          :technologies="technologies"
          :current-filters="currentFilters"
          :results-count="projects.length"
          class="mb-6"
        />

        <!-- Project Type Toggle -->
        <div v-if="projects.length > 0" class="mb-6">
          <v-btn-toggle
            v-model="selectedView"
            mandatory
            color="primary"
            class="mb-4"
          >
            <v-btn value="hidden" prepend-icon="mdi-lock">
              Hidden Projects ({{ privateProjects.length }})
            </v-btn>
            <v-btn value="public" prepend-icon="mdi-earth">
              Public Projects ({{ publicProjects.length }})
            </v-btn>
          </v-btn-toggle>
        </div>

        <!-- Projects Grid -->
        <div v-if="projects.length > 0">
          <!-- Hidden Projects -->
          <div v-if="selectedView === 'hidden' && privateProjects.length > 0" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="project in privateProjects" :key="project.id" class="relative">
                <ProjectCard
                  :project="project"
                  :categories="categories"
                  :statuses="statuses"
                  :preview-settings="project.settings"
                  :show-meta-info="true"
                />
                <div class="absolute top-2 right-2 d-flex gap-1">
                  <v-switch
                    :model-value="project.is_public"
                    @update:model-value="toggleProjectVisibility(project)"
                    color="success"
                    density="compact"
                    hide-details
                    class="mr-2"
                  >
                    <template v-slot:label>
                      <span class="text-xs">{{ project.is_public ? 'Public' : 'Hidden' }}</span>
                    </template>
                  </v-switch>
                  <v-btn
                    icon="mdi-pencil"
                    size="small"
                    color="blue"
                    variant="tonal"
                    @click="editProject(project.id!)"
                    title="Edit Project"
                  ></v-btn>
                </div>
              </div>
            </div>
          </div>

          <!-- Public Projects -->
          <div v-if="selectedView === 'public' && publicProjects.length > 0" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="project in publicProjects" :key="project.id" class="relative">
                <ProjectCard
                  :project="project"
                  :categories="categories"
                  :statuses="statuses"
                  :preview-settings="project.settings"
                  :show-meta-info="true"
                />
                <div class="absolute top-2 right-2 d-flex gap-1">
                  <v-switch
                    :model-value="project.is_public"
                    @update:model-value="toggleProjectVisibility(project)"
                    color="success"
                    density="compact"
                    hide-details
                    class="mr-2"
                  >
                    <template v-slot:label>
                      <span class="text-xs">{{ project.is_public ? 'Public' : 'Hidden' }}</span>
                    </template>
                  </v-switch>
                  <v-btn
                    icon="mdi-pencil"
                    size="small"
                    color="blue"
                    variant="tonal"
                    @click="editProject(project.id!)"
                    title="Edit Project"
                  ></v-btn>
                </div>
              </div>
            </div>
          </div>

          <!-- No Projects Message -->
          <div v-if="(selectedView === 'hidden' && privateProjects.length === 0) || (selectedView === 'public' && publicProjects.length === 0)" class="text-center py-16">
            <v-icon 
              :icon="hasActiveFilters ? 'mdi-filter-off' : 'mdi-folder-open'" 
              size="120" 
              color="grey-lighten-1" 
              class="mb-6"
            ></v-icon>
            <h2 class="text-2xl font-bold text-gray-700 mb-4">
              <span v-if="hasActiveFilters">
                No projects match your filters
              </span>
              <span v-else>
                No {{ selectedView === 'hidden' ? 'hidden' : 'public' }} projects
              </span>
            </h2>
            <p class="text-lg text-gray-500 mb-8 max-w-md mx-auto">
              <span v-if="hasActiveFilters">
                Try adjusting your search criteria or clear some filters to see more results.
              </span>
              <span v-else>
                {{ selectedView === 'hidden' ? 'All your projects are currently public.' : 'All your projects are currently hidden.' }}
              </span>
            </p>
            <v-btn
              v-if="hasActiveFilters"
              color="primary"
              variant="outlined"
              @click="clearFilters"
            >
              Clear Filters
            </v-btn>
          </div>
        </div>

        <!-- No Projects State -->
        <div v-else class="text-center py-16">
          <v-icon 
            :icon="hasActiveFilters ? 'mdi-filter-off' : 'mdi-folder-plus'" 
            size="120" 
            color="grey-lighten-1" 
            class="mb-6"
          ></v-icon>
          
          <h2 class="text-4xl font-bold text-gray-700 mb-4">
            <span v-if="hasActiveFilters">
              No projects found
            </span>
            <span v-else>
              No projects yet
            </span>
          </h2>
          
          <p class="text-lg text-gray-500 mb-8 max-w-md mx-auto">
            <span v-if="hasActiveFilters">
              No projects match your current search and filter criteria. Try adjusting your filters or create a new project.
            </span>
            <span v-else>
              Start creating projects to organize your work and display it on dashboard.
            </span>
          </p>
          
          <div class="d-flex gap-3 justify-center">
            <v-btn
              v-if="hasActiveFilters"
              color="secondary"
              variant="outlined"
              @click="clearFilters"
            >
              Clear Filters
            </v-btn>
            <v-btn
              color="primary"
              size="large"
              prepend-icon="mdi-plus"
              @click="createProject"
            >
              {{ hasActiveFilters ? 'Create Project' : 'Create Your First Project' }}
            </v-btn>
          </div>
        </div>

        <!-- Confirmation Modal -->
        <v-dialog v-model="showConfirmationModal" class="!max-w-xl">
          <v-card class="!p-2">
            <v-card-title class="text-h6">
              <v-icon 
                :icon="toggleAction === 'public' ? 'mdi-eye' : 'mdi-eye-off'" 
                :color="toggleAction === 'public' ? 'success' : 'warning'"
                class="mr-2"
              ></v-icon>
              Confirm Project Visibility Change
            </v-card-title>
            <v-card-text>
              <p class="mb-2">
                Are you sure you want to make the project 
                <strong>"{{ projectToToggle?.name }}"</strong> 
                {{ toggleAction === 'public' ? 'public' : 'hidden' }}?
              </p>
              <p class="text-sm text-gray-400">
                {{ toggleAction === 'public' 
                  ? 'This project will be visible to everyone.' 
                  : 'This project will only be visible to you.' 
                }}
              </p>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                variant="outlined"
                @click="cancelToggle"
              >
                Cancel
              </v-btn>
              <v-btn
                variant="tonal"
                :color="toggleAction === 'public' ? 'success' : 'warning'"
                @click="confirmToggle"
              >
                Confirm
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

      </v-container>
    </v-main>
  </AppLayout>
</template>