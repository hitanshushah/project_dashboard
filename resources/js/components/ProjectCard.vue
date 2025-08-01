<template>
  <v-card variant="outlined" class="pa-4">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-800">
        {{ project.name || 'Project Name' }}
      </h3>
      <div v-if="project.category" class="text-sm text-blue-600 mt-1">
        {{ getCategoryName(project.category) }}
      </div>
    </div>

    <div v-if="project.description" class="mb-4">
      <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ project.description }}</p>
    </div>

    <!-- Status Badge -->
    <div class="mb-4">
      <v-chip
        v-if="project.status"
        :color="getStatusColor(project.status)"
        size="small"
        variant="tonal"
      >
        {{ getStatusName(project.status) }}
      </v-chip>
    </div>

    <!-- Dates -->
    <div v-if="project.start_date || project.end_date" class="mb-4">
      <div class="text-sm text-gray-500">
        <div v-if="project.start_date">
          <strong>Start:</strong> {{ formatDate(project.start_date) }}
        </div>
        <div v-if="project.end_date">
          <strong>End:</strong> {{ formatDate(project.end_date) }}
        </div>
      </div>
    </div>

    <!-- Tags -->
    <div v-if="project.tags && project.tags.length > 0" class="mb-4">
      <div class="text-sm text-gray-500 mb-2">Tags:</div>
      <div class="flex flex-wrap gap-1">
        <v-chip
          v-for="tag in project.tags"
          :key="tag"
          size="x-small"
          color="primary"
          variant="tonal"
        >
          {{ tag }}
        </v-chip>
      </div>
    </div>

    <!-- Technologies -->
    <div v-if="project.technologies && project.technologies.length > 0" class="mb-4">
      <div class="text-sm text-gray-500 mb-2">Technologies:</div>
      <div class="flex flex-wrap gap-1">
        <v-chip
          v-for="tech in project.technologies"
          :key="tech"
          size="x-small"
          color="secondary"
          variant="tonal"
        >
          {{ tech }}
        </v-chip>
      </div>
    </div>

    <!-- Links -->
    <div v-if="project.links && project.links.length > 0" class="mb-4">
      <div class="text-sm text-gray-500 mb-2">Links:</div>
      <div class="space-y-1">
        <div
          v-for="link in project.links"
          :key="link.title"
          class="text-sm"
        >
          <a :href="link.url" target="_blank" class="text-blue-600 hover:underline">
            {{ link.title }}
          </a>
        </div>
      </div>
    </div>

    <!-- Notes -->
    <div v-if="project.notes" class="mb-4">
      <div class="text-sm text-gray-500 mb-2">Notes:</div>
      <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ project.notes }}</p>
    </div>

    <!-- Assets -->
    <div v-if="project.assets && project.assets.length > 0" class="mb-4">
      <div class="text-sm text-gray-500 mb-2">Assets ({{ project.assets.length }} files):</div>
      
      <!-- Image Carousel for Image Assets -->
      <div v-if="imageAssets.length > 0" class="mb-4">
        <v-carousel
          :show-arrows="imageAssets.length > 1"
          :show-dots="imageAssets.length > 1"
          height="200"
          class="rounded-lg overflow-hidden"
        >
          <v-carousel-item
            v-for="(file, index) in imageAssets"
            :key="index"
            :src="getFileUrl(file)"
            contain
          >
            <template v-slot:placeholder>
              <div class="d-flex fill-height justify-center align-center">
                <v-progress-circular
                  indeterminate
                  color="grey-lighten-4"
                ></v-progress-circular>
              </div>
            </template>
          </v-carousel-item>
        </v-carousel>
      </div>
      
      <!-- Other Files List -->
      <div v-if="nonImageAssets.length > 0" class="space-y-1">
        <div class="text-sm text-gray-500 mb-2">Other Files:</div>
        <div
          v-for="file in nonImageAssets"
          :key="file.name || file.path"
          class="text-sm text-gray-600 flex items-center"
        >
          <v-icon icon="mdi-file" size="small" class="mr-1"></v-icon>
          {{ file.name || file.path }}
        </div>
      </div>
    </div>

    <!-- Created/Updated Info -->
    <div v-if="showMetaInfo && (project.created_at || project.updated_at)" class="mt-4 pt-4 border-t border-gray-200">
      <div class="text-xs text-gray-400">
        <div v-if="project.created_at">
          Created: {{ formatDateTime(project.created_at) }}
        </div>
        <div v-if="project.updated_at && project.updated_at !== project.created_at">
          Updated: {{ formatDateTime(project.updated_at) }}
        </div>
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Project } from '@/types';

interface Props {
  project: Project;
  categories?: Array<{ name: string; key: string }>;
  statuses?: Array<{ name: string; key: string }>;
  showMetaInfo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showMetaInfo: false
});

// Computed properties for assets
const imageAssets = computed(() => {
  if (!props.project.assets) return [];
  return props.project.assets.filter(file => {
    const fileType = file.type || file.name || '';
    return fileType.startsWith('image/');
  });
});

const nonImageAssets = computed(() => {
  if (!props.project.assets) return [];
  return props.project.assets.filter(file => {
    const fileType = file.type || file.name || '';
    return !fileType.startsWith('image/');
  });
});

// Helper functions
const getStatusColor = (status: string) => {
  const colorMap: Record<string, string> = {
    'planning': 'blue',
    'inprogress': 'orange',
    'finished': 'green',
    'onhold': 'yellow',
    'cancelled': 'red',
    'notstarted': 'grey'
  };
  return colorMap[status] || 'grey';
};

const getStatusName = (statusKey: string) => {
  if (!props.statuses) return statusKey;
  const status = props.statuses.find(s => s.key === statusKey);
  return status?.name || statusKey;
};

const getCategoryName = (categoryKey: string) => {
  if (!props.categories) return categoryKey;
  const category = props.categories.find(c => c.key === categoryKey);
  return category?.name || categoryKey;
};

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString();
};

const getFileUrl = (file: any) => {
  // For File objects (from form), create object URL
  if (file instanceof File) {
    return URL.createObjectURL(file);
  }
  // For saved files, use the path
  if (file.path) {
    return file.path;
  }
  // Fallback
  return '';
};
</script> 