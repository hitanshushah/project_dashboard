<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import type { Project } from '@/types';

const page = usePage();

const flash = computed(() => page.props.flash as { success?: string });
const projects = computed(() => page.props.projects as Project[] || []);
const categories = computed(() => page.props.categories as Array<{ name: string; key: string }> || []);
const statuses = computed(() => page.props.statuses as Array<{ name: string; key: string }> || []);

const createProject = () => {
  router.visit('/projects/create');
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

        <!-- Projects Grid -->
        <div v-if="projects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <ProjectCard
            v-for="project in projects"
            :key="project.id"
            :project="project"
            :categories="categories"
            :statuses="statuses"
            :preview-settings="project.settings || {}"
            :show-meta-info="true"
          />
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
      </v-container>
    </v-main>
  </AppLayout>
</template>