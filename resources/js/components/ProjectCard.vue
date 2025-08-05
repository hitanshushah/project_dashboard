<template>
  <v-card variant="outlined" class="pa-4">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-800">
        {{ project.name || 'Project Name' }}
      </h3>
      <div v-if="project.category && (effectivePreviewSettings.showCategory !== false)" class="text-sm text-blue-600 mt-1">
        {{ getCategoryNameLocal(project.category) }}
      </div>
    </div>

    <div v-if="project.description && (effectivePreviewSettings.showDescription !== false)" class="mb-4">
      <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ project.description }}</p>
    </div>

    <!-- Status Badge -->
    <div v-if="project.status && (effectivePreviewSettings.showStatus !== false)" class="mb-4">
      <v-chip
        :color="getStatusColor(project.status)"
        size="small"
        variant="tonal"
      >
        {{ getStatusNameLocal(project.status) }}
      </v-chip>
    </div>

    <!-- Dates -->
    <div v-if="(project.start_date || project.end_date) && (effectivePreviewSettings.showDates !== false)" class="mb-4">
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
    <div v-if="project.tags && project.tags.length > 0 && (effectivePreviewSettings.showTags !== false)" class="mb-4">
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
    <div v-if="project.technologies && project.technologies.length > 0 && (effectivePreviewSettings.showTechnologies !== false)" class="mb-4">
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
    <div v-if="project.links && project.links.length > 0 && (effectivePreviewSettings.showLinks !== false)" class="mb-4">
      <div class="text-sm text-gray-500 mb-2">Links:</div>
      <div class="space-y-1">
        <div
          v-for="link in project.links"
          :key="link.title"
          class="text-sm flex items-center"
        >
          <v-icon 
            :icon="getLinkIcon(link.title)" 
            size="small" 
            class="mr-2"
          ></v-icon>
          <a :href="link.url" target="_blank" class="text-blue-600 hover:underline">
            {{ link.title }}
          </a>
        </div>
      </div>
    </div>

          <!-- Assets -->
      <div v-if="project.assets && project.assets.length > 0 && (effectivePreviewSettings.showAssets !== false)" class="mb-4">
        <div class="text-sm text-gray-500 mb-2">Assets ({{ project.assets.length }} files):</div>
        
        <!-- Image/Video Carousel for Media Assets -->
        <div v-if="mediaAssets.length > 0" class="mb-4">
          <v-carousel
            :show-arrows="mediaAssets.length > 1"
            :show-dots="mediaAssets.length > 1"
            height="200"
            class="rounded-lg overflow-hidden"
          >
            <v-carousel-item
              v-for="(file, index) in mediaAssets"
              :key="index"
              :src="getFileUrlForPreview(file)"
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
        
        <!-- Downloadable Files List -->
        <div v-if="downloadableAssets.length > 0" class="space-y-2">
          <div class="text-sm text-gray-500 mb-2">Files:</div>
          <div
            v-for="file in downloadableAssets"
            :key="file.id || file.filename || file.name || file.path || file.url"
            class="text-sm text-gray-600 flex items-center justify-between p-2 bg-gray-50 rounded"
          >
            <div class="flex items-center">
              <v-icon :icon="getFileIcon(file.filename || file.display_name || file.name || file.path || file.url || '')" size="small" class="mr-2" :color="getFileColor(file.filename || file.display_name || file.name || file.path || file.url || '')"></v-icon>
              <span class="truncate">{{ file.display_name || getFileNameFromUrl(file.filename || file.path || file.url || '') }}</span>
            </div>
            <v-btn
              :href="getFileUrlForPreview(file)"
              target="_blank"
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="mdi-download"
            >
              Download
            </v-btn>
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
import { 
  getLinkIcon, 
  getLinkIconColor, 
  getStatusColor, 
  getStatusName, 
  getCategoryName, 
  formatDate, 
  formatDateTime, 
  getFileUrl,
  getFileIcon,
  getFileColor
} from '@/lib/projectUtils';

interface Props {
  project: Project;
  categories?: Array<{ name: string; key: string }>;
  statuses?: Array<{ name: string; key: string }>;
  showMetaInfo?: boolean;
  previewSettings?: {
    showDescription?: boolean;
    showCategory?: boolean;
    showStatus?: boolean;
    showDates?: boolean;
    showTags?: boolean;
    showTechnologies?: boolean;
    showLinks?: boolean;
    showAssets?: boolean;
  } | null;
}

const props = withDefaults(defineProps<Props>(), {
  showMetaInfo: false,
  previewSettings: () => ({
    showDescription: true,
    showCategory: true,
    showStatus: true,
    showDates: true,
    showTags: true,
    showTechnologies: true,
    showLinks: true,
    showAssets: true,
  })
});



// Computed property to handle null preview settings
const effectivePreviewSettings = computed(() => {
  return props.previewSettings || {
    showDescription: true,
    showCategory: true,
    showStatus: true,
    showDates: true,
    showTags: true,
    showTechnologies: true,
    showLinks: true,
    showAssets: true,
  };
});

// Helper function to extract filename from URL
const getFileNameFromUrl = (url: string): string => {
  if (!url) return '';
  try {
    const urlObj = new URL(url);
    const pathname = urlObj.pathname;
    const filename = pathname.split('/').pop();
    return filename || url;
  } catch {
    return url;
  }
};

// Helper function to get file URL for preview (handles both existing and new files)
const getFileUrlForPreview = (file: any): string => {
  // If it's a new file (has file property), create object URL
  if (file.file && file.file instanceof File) {
    return URL.createObjectURL(file.file);
  }
  // Otherwise use the existing getFileUrl function
  return getFileUrl(file);
};

// Computed properties for assets
const mediaAssets = computed(() => {
  if (!props.project.assets) return [];
  return props.project.assets.filter(file => {
    // Check if it's an image or video based on asset type or filename
    const assetType = file.asset_type?.key || '';
    const filename = file.filename || file.display_name || file.name || file.path || file.url || '';
    const isImage = assetType === 'images' || filename.match(/\.(jpg|jpeg|png|gif|svg|webp|bmp|tiff)$/i);
    const isVideo = assetType === 'videos' || filename.match(/\.(mp4|avi|mov|wmv|flv|webm|mkv|m4v)$/i);
    return isImage || isVideo;
  });
});

const downloadableAssets = computed(() => {
  if (!props.project.assets) return [];
  return props.project.assets.filter(file => {
    // Check if it's a document or other file (not image/video)
    const assetType = file.asset_type?.key || '';
    const filename = file.filename || file.display_name || file.name || file.path || file.url || '';
    const isImage = assetType === 'images' || filename.match(/\.(jpg|jpeg|png|gif|svg|webp|bmp|tiff)$/i);
    const isVideo = assetType === 'videos' || filename.match(/\.(mp4|avi|mov|wmv|flv|webm|mkv|m4v)$/i);
    return !isImage && !isVideo;
  });
});

// Helper functions - using imported utilities
const getStatusNameLocal = (statusKey: string) => {
  return getStatusName(statusKey, props.statuses);
};

const getCategoryNameLocal = (categoryKey: string) => {
  return getCategoryName(categoryKey, props.categories);
};
</script> 