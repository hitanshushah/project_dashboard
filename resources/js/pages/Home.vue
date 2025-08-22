<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import SearchFilters from '@/components/SearchFilters.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import ProjectReorder from '@/components/ProjectReorder.vue';
import { usePublicProjects } from '@/composables/usePublicProjects';
import type { Project } from '@/types';
import { useAppearance } from '@/composables/useAppearance';

const page = usePage();

const flash = computed(() => page.props.flash as { success?: string });
const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);
const technologies = computed(() => page.props.technologies as string[] || []);


const profile = computed(() => page.props.auth?.profile);


const isProfileIncomplete = computed(() => {
  if (!profile.value) return true;
  
  const hasLinks = profile.value.links && profile.value.links.length > 0;
  const hasDocuments = profile.value.documents && profile.value.documents.length > 0;
  
  
  return !hasLinks && !hasDocuments;
});


const showIncompleteProfileBanner = ref(true);
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


const searchFiltersRef = ref<InstanceType<typeof SearchFilters>>();


const hasActiveFilters = computed(() => {
  return currentFilters.value.search ||
         currentFilters.value.categories.length > 0 ||
         currentFilters.value.statuses.length > 0 ||
         currentFilters.value.technologies.length > 0 ||
         currentFilters.value.sort_by !== 'created_at' ||
         currentFilters.value.sort_direction !== 'desc';
});


const selectedView = ref('hidden'); 


const publicProjects = computed(() => projects.value.filter(project => project.is_public));
const privateProjects = computed(() => projects.value.filter(project => !project.is_public));


const showConfirmationModal = ref(false);
const projectToToggle = ref<Project | null>(null);
const toggleAction = ref<'public' | 'hidden'>('public');


const showDeleteModal = ref(false);
const projectToDelete = ref<Project | null>(null);


const showReorderModal = ref(false);


const { isDark } = useAppearance();

const createProject = () => {
  router.visit('/projects/create');
};

const editProject = (projectId: number) => {
  router.visit(`/projects/${projectId}/edit`);
};

const openPublicPreview = () => {
      
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

const deleteProject = (project: Project) => {
  projectToDelete.value = project;
  showDeleteModal.value = true;
};

const confirmDelete = () => {
  if (!projectToDelete.value) return;
  
  const form = useForm({
    _method: 'DELETE'
  });
  
  form.delete(`/projects/${projectToDelete.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false;
      projectToDelete.value = null;
      
      router.reload();
    },
    onError: (errors) => {
      
    }
  });
};

const cancelDelete = () => {
  showDeleteModal.value = false;
  projectToDelete.value = null;
};


const openReorderModal = () => {
  showReorderModal.value = true;
};

const closeReorderModal = () => {
  showReorderModal.value = false;
};

const handleReorderSaved = (reorderedProjects: Project[]) => {
  
  router.reload();
};


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

        
        <v-alert
          v-if="isProfileIncomplete && showIncompleteProfileBanner"
          type="warning"
          variant="tonal"
          class="mb-6"
          closable
          @click:close="showIncompleteProfileBanner = false"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div>
                <div class="font-semibold text-base">Profile is not complete</div>
                <div class="text-sm opacity-90">
                  Add your resume, LinkedIn, and other links from the edit profile page to complete your profile.
                </div>
              </div>
            </div>
            <v-btn
              :color="isDark ? 'default' : 'default'"
              variant="outlined"
              size="small"
              prepend-icon="mdi-pencil"
              @click="router.visit('/profile/edit')"
              class="ml-4"
            >
              Edit Profile
            </v-btn>
          </div>
        </v-alert>

        
        <div class="mb-6">
          <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 md:gap-0">
            <h1 :class="['text-2xl md:text-3xl font-bold',  isDark ? 'text-white' : 'text-gray-900']">
              Manage Projects
            </h1>
            <div class="flex flex-col md:flex-row gap-3">
              <v-btn
                v-if="publicProjects.length > 0"
                prepend-icon="mdi-open-in-new"
                variant="text"
                @click="openPublicPreview"
                :title="`Preview your ${publicProjects.length} public project${publicProjects.length !== 1 ? 's' : ''}`"
                :class="[
                    ' border rounded-lg !text-sm py-3 md:py-2 px-4 content-center',
                    isDark ? 'text-white !bg-black !border-gray-600' : 'text-gray-900 !bg-gray-100 !border-gray-600'
                  ]"
              >
                Preview Public URL
                <v-badge
                  :content="publicProjects.length"
                  inline
                  class="ml-2"
                ></v-badge>
              </v-btn>
              <v-btn
                v-else
                prepend-icon="mdi-alert-circle-outline"
                variant="text"
                disabled
                :title="'Make projects public to preview'"
                :class="[
                  'border rounded-lg !text-sm py-3 md:py-2 px-4 content-center',
                  isDark ? 'text-gray-400 !bg-black !border-gray-600' : 'text-gray-400 !bg-gray-100 !border-gray-600'
                ]"
              >
                Make projects public to preview
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="createProject"
                class="py-3 md:py-0 content-center"
              >
                Create Project
              </v-btn>
            </div>
          </div>
        </div>

        
        <SearchFilters
          ref="searchFiltersRef"
          :categories="categories"
          :statuses="statuses"
          :technologies="technologies"
          :current-filters="currentFilters"
          :results-count="projects.length"
          class="mb-6"
        />

        
        <div v-if="projects.length > 0" class="mb-6">
          <div class="flex flex-col md:flex-row gap-2 md:gap-0 mb-4">
            <v-btn
              :color="selectedView === 'hidden' ? 'primary' : undefined"
              :variant="selectedView === 'hidden' ? 'elevated' : 'outlined'"
              prepend-icon="mdi-lock"
              @click="selectedView = 'hidden'"
              class="w-full md:w-auto py-4 md:py-0 content-center"
            >
              Hidden Projects ({{ privateProjects.length }})
            </v-btn>
            <v-btn
              :color="selectedView === 'public' ? 'primary' : undefined"
              :variant="selectedView === 'public' ? 'elevated' : 'outlined'"
              prepend-icon="mdi-earth"
              @click="selectedView = 'public'"
              class="w-full md:w-auto py-4 md:py-0 content-center"
            >
              Public Projects ({{ publicProjects.length }})
            </v-btn>
          </div>
        </div>

        
        <div v-if="projects.length > 0">
          
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
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    color="error"
                    variant="tonal"
                    @click="deleteProject(project)"
                    title="Delete Project"
                  ></v-btn>
                </div>
              </div>
            </div>
          </div>

          
          <div v-if="selectedView === 'public' && publicProjects.length > 0" class="mb-8">
            
            <div class="flex items-center justify-between mb-4">
              <v-btn
                prepend-icon="mdi-drag"
                variant="outlined"
                @click="openReorderModal"
                :class="[isDark ? 'text-sm !bg-black text-white !border-gray-600 border rounded-lg !text-sm py-2 px-4 ml-4' : 'text-sm !bg-gray-100 text-black !border-gray-600 border rounded-lg !text-sm py-2 px-4 ml-4']"
              >
                Reorder Projects
              </v-btn>
            </div>
            
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
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    color="error"
                    variant="tonal"
                    @click="deleteProject(project)"
                    title="Delete Project"
                  ></v-btn>
                </div>
              </div>
            </div>
          </div>

          
          <div v-if="(selectedView === 'hidden' && privateProjects.length === 0) || (selectedView === 'public' && publicProjects.length === 0)" class="text-center py-16">
            <v-icon 
              :icon="hasActiveFilters ? 'mdi-filter-off' : 'mdi-folder-open'" 
              size="120" 
              :color="isDark ? 'gray-300' : 'gray-600'" 
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

        
        <div v-else class="text-center py-16">
          <v-icon 
            :icon="hasActiveFilters ? 'mdi-filter-off' : 'mdi-folder-plus'" 
            size="120" 
            :color="isDark ? 'gray-300' : 'gray-600'" 
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

        
        <v-dialog v-model="showReorderModal" class="!max-w-4xl" persistent>
          <v-card class="!p-6">
            <ProjectReorder
              :projects="publicProjects"
              :categories="categories"
              :statuses="statuses"
              @close="closeReorderModal"
              @saved="handleReorderSaved"
              :isDark="isDark"
            />
          </v-card>
        </v-dialog>

        
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
              <p :class="isDark ? 'text-sm text-gray-400' : 'text-sm text-gray-700'">
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
                :variant="isDark ? 'tonal' : 'elevated'"
                :color="toggleAction === 'public' ? 'success' : 'warning'"
                @click="confirmToggle"
              >
                Confirm
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        
        <v-dialog v-model="showDeleteModal" class="!max-w-xl">
          <v-card class="!p-2">
            <v-card-title class="text-h6">
              <v-icon 
                icon="mdi-delete" 
                color="error"
                class="mr-2"
              ></v-icon>
              Confirm Project Deletion
            </v-card-title>
            <v-card-text>
              <p class="mb-2">
                Are you sure you want to delete the project 
                <strong>"{{ projectToDelete?.name }}"</strong>?
              </p>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                variant="outlined"
                @click="cancelDelete"
              >
                Cancel
              </v-btn>
              <v-btn
                :variant="isDark ? 'tonal' : 'elevated'"
                color="error"
                @click="confirmDelete"
              >
                Delete Project
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

      </v-container>
    </v-main>
  </AppLayout>
</template>