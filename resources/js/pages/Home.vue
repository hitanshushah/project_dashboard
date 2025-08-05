<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import type { Project } from '@/types';

const page = usePage();

const flash = computed(() => page.props.flash as { success?: string });
const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);

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
      console.error('Failed to toggle project visibility:', errors);
    }
  });
};

const cancelToggle = () => {
  showConfirmationModal.value = false;
  projectToToggle.value = null;
};


</script>

<template>
  <AppLayout>
    <v-main>
      <v-container class="py-8">
        <v-alert
          v-if="flash?.success"
          type="success"
          variant="tonal"
          class="mb-6"
          closable
        >
          {{ flash.success }}
        </v-alert>

        <!-- Header with Create Project Button -->
        <div class="d-flex justify-space-between align-center mb-6">
          <h1 class="text-3xl font-bold text-gray-800">
            My Projects
          </h1>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="createProject"
          >
            Create Project
          </v-btn>
        </div>

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
                  :preview-settings="project.settings || {}"
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
                    color="primary"
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
                  :preview-settings="project.settings || {}"
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
                    color="primary"
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
              icon="mdi-folder-open" 
              size="120" 
              color="grey-lighten-1" 
              class="mb-6"
            ></v-icon>
            <h2 class="text-2xl font-bold text-gray-700 mb-4">
              No {{ selectedView === 'hidden' ? 'hidden' : 'public' }} projects
            </h2>
            <p class="text-lg text-gray-500 mb-8 max-w-md mx-auto">
              {{ selectedView === 'hidden' ? 'All your projects are currently public.' : 'All your projects are currently hidden.' }}
            </p>
          </div>
        </div>

        <!-- No Projects State -->
        <div v-else class="text-center py-16">
          <v-icon 
            icon="mdi-folder-plus" 
            size="120" 
            color="grey-lighten-1" 
            class="mb-6"
          ></v-icon>
          
          <h2 class="text-4xl font-bold text-gray-700 mb-4">
            No projects yet
          </h2>
          
          <p class="text-lg text-gray-500 mb-8 max-w-md mx-auto">
            Start creating projects to organize your work and display it on dashboard.
          </p>
          
          <v-btn
            color="primary"
            size="large"
            prepend-icon="mdi-plus"
            @click="createProject"
          >
            Create Your First Project
          </v-btn>
        </div>

        <!-- Confirmation Modal -->
        <v-dialog v-model="showConfirmationModal" max-width="400">
          <v-card>
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
              <p class="text-sm text-gray-600">
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