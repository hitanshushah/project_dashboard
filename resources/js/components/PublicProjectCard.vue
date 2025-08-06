<template>
  <v-card variant="outlined" class="pa-6 h-full bg-white hover:shadow-lg transition-shadow duration-300">
    <div class="mb-4">
      <h3 class="text-xl font-bold text-gray-800 mb-2">
        {{ project.name || 'Project Name' }}
      </h3>
      <div v-if="project.category && (effectivePreviewSettings.showCategory !== false)" class="text-sm text-blue-600 font-medium">
        {{ getCategoryNameLocal(project.category) }}
      </div>
    </div>

    <div v-if="project.description && (effectivePreviewSettings.showDescription !== false)" class="mb-6">
      <p class="text-gray-600 leading-relaxed whitespace-pre-wrap">{{ project.description }}</p>
    </div>

    <!-- Status Badge -->
    <div v-if="project.status && (effectivePreviewSettings.showStatus !== false)" class="mb-4">
      <v-chip
        :color="getStatusColor(project.status)"
        size="small"
        variant="tonal"
        class="font-medium"
      >
        {{ getStatusNameLocal(project.status) }}
      </v-chip>
    </div>

    <!-- Dates -->
    <div v-if="(project.start_date || project.end_date) && (effectivePreviewSettings.showDates !== false)" class="mb-4">
      <div class="text-sm text-gray-500 space-y-1">
        <div v-if="project.start_date" class="flex items-center">
          <v-icon icon="mdi-calendar-start" size="small" class="mr-2"></v-icon>
          <strong>Started:</strong>&nbsp;{{ formatDate(project.start_date) }}
        </div>
        <div v-if="project.end_date" class="flex items-center">
          <v-icon icon="mdi-calendar-end" size="small" class="mr-2"></v-icon>
          <strong>Completed:</strong>&nbsp;{{ formatDate(project.end_date) }}
        </div>
      </div>
    </div>

    <!-- Tags -->
    <div v-if="project.tags && project.tags.length > 0 && (effectivePreviewSettings.showTags !== false)" class="mb-4">
      <div class="text-sm text-gray-500 mb-3 font-medium">
        <v-icon icon="mdi-tag-multiple" size="small" class="mr-1"></v-icon>
        Tags
      </div>
      <div class="flex flex-wrap gap-2">
        <v-chip
          v-for="tag in project.tags"
          :key="tag"
          size="small"
          color="primary"
          variant="outlined"
          class="font-medium"
        >
          {{ tag }}
        </v-chip>
      </div>
    </div>

    <!-- Technologies -->
    <div v-if="project.technologies && project.technologies.length > 0 && (effectivePreviewSettings.showTechnologies !== false)" class="mb-4">
      <div class="text-sm text-gray-500 mb-3 font-medium">
        <v-icon icon="mdi-code-tags" size="small" class="mr-1"></v-icon>
        Technologies
      </div>
      <div class="flex flex-wrap gap-2">
        <v-chip
          v-for="tech in project.technologies"
          :key="tech"
          size="small"
          color="secondary"
          variant="tonal"
          class="font-medium"
        >
          {{ tech }}
        </v-chip>
      </div>
    </div>

    <!-- Links -->
    <div v-if="project.links && project.links.length > 0 && (effectivePreviewSettings.showLinks !== false)" class="mb-4">
      <div class="text-sm text-gray-500 mb-3 font-medium">
        <v-icon icon="mdi-link" size="small" class="mr-1"></v-icon>
        Project Links
      </div>
      <div class="space-y-2">
        <div v-for="link in project.links" :key="link.url" class="flex items-center">
          <v-btn
            :href="link.url"
            target="_blank"
            rel="noopener noreferrer"
            color="primary"
            variant="outlined"
            size="small"
            :prepend-icon="getLinkIcon(link.type)"
            class="mr-2"
          >
            {{ link.title || 'Visit Link' }}
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Assets -->
    <div v-if="project.assets && project.assets.length > 0 && (effectivePreviewSettings.showAssets !== false)" class="mb-4">
      <div class="text-sm text-gray-500 mb-3 font-medium">
        <v-icon icon="mdi-file-multiple" size="small" class="mr-1"></v-icon>
        Project Assets
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div v-for="asset in project.assets" :key="asset.id" class="flex items-center">
          <v-btn
            :href="asset.url"
            target="_blank"
            color="secondary"
            variant="text"
            size="small"
            :prepend-icon="getAssetIcon(asset.type)"
            class="text-left justify-start"
          >
            <span class="truncate">{{ asset.display_name || asset.name }}</span>
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Project Footer with Creation Date -->
    <div class="mt-auto pt-4 border-t border-gray-100">
      <div class="flex items-center text-xs text-gray-400">
        <v-icon icon="mdi-calendar" size="small" class="mr-1"></v-icon>
        Created {{ formatDate(project.created_at) }}
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Project } from '@/types';

interface Props {
  project: Project;
  categories: Array<{ name: string; key: string }>;
  statuses: Array<{ name: string; key: string }>;
  previewSettings?: {
    showDescription?: boolean;
    showCategory?: boolean;
    showStatus?: boolean;
    showDates?: boolean;
    showTags?: boolean;
    showTechnologies?: boolean;
    showLinks?: boolean;
    showAssets?: boolean;
  };
}

const props = defineProps<Props>();

const effectivePreviewSettings = computed(() => ({
  showDescription: true,
  showCategory: true,
  showStatus: true,
  showDates: true,
  showTags: true,
  showTechnologies: true,
  showLinks: true,
  showAssets: true,
  ...props.previewSettings,
}));

const getCategoryNameLocal = (categoryKey: string) => {
  const category = props.categories.find(cat => cat.key === categoryKey);
  return category ? category.name : categoryKey;
};

const getStatusNameLocal = (statusKey: string) => {
  const status = props.statuses.find(stat => stat.key === statusKey);
  return status ? status.name : statusKey;
};

const getStatusColor = (statusKey: string) => {
  // Define status colors based on common status types
  const colorMap: Record<string, string> = {
    'completed': 'success',
    'in-progress': 'warning',
    'planned': 'info',
    'on-hold': 'orange',
    'cancelled': 'error',
    'archived': 'secondary'
  };
  return colorMap[statusKey] || 'primary';
};

const getLinkIcon = (linkType: string | null) => {
  const iconMap: Record<string, string> = {
    'github': 'mdi-github',
    'website': 'mdi-web',
    'demo': 'mdi-play-circle',
    'documentation': 'mdi-file-document',
    'video': 'mdi-video',
    'presentation': 'mdi-presentation'
  };
  return iconMap[linkType || 'website'] || 'mdi-link';
};

const getAssetIcon = (assetType: string | null) => {
  const iconMap: Record<string, string> = {
    'image': 'mdi-image',
    'video': 'mdi-video',
    'document': 'mdi-file-document',
    'code': 'mdi-code-braces',
    'presentation': 'mdi-presentation',
    'spreadsheet': 'mdi-table'
  };
  return iconMap[assetType || 'document'] || 'mdi-file';
};

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<style scoped>
.v-card {
  display: flex;
  flex-direction: column;
}

.truncate {
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>