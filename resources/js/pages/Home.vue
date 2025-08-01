<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();

const flash = computed(() => page.props.flash as { success?: string });

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

        <div class="text-center py-16">
          <v-icon 
            icon="mdi-folder-plus" 
            size="120" 
            color="grey-lighten-1" 
            class="mb-6"
          ></v-icon>
          
          <h1 class="text-4xl font-bold text-gray-700 mb-4">
            No projects yet
          </h1>
          
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