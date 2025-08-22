<template>
  <v-card 
    :class="[
      'rounded-xl !p-6 h-full flex flex-col',
      isDarkMode ? 'bg-black' : '!bg-gray-300',
    ]"
  >
    
    <div class="flex-1 flex flex-col">
      
      <div class="p-6 pb-4">
        
        <h3 :class="[
          'text-xl md:text-2xl font-bold mb-2',
          isDarkMode ? 'text-white' : 'text-gray-900'
        ]">
          {{ project.name }}
        </h3>
        
        
        <div class="flex items-center gap-2 mb-3">
          <v-chip
            size="small"
            v-if="project.category"
            :class="[
              'text-xs !font-bold',
              isDarkMode ? '!border-1 !border-orange-400 !bg-[#3A2315] !text-orange-400' : 'bg-gray-100 text-gray-700'
            ]"
          >
            {{ getCategoryNameLocal(project.category)}}
          </v-chip>
          <v-chip
            size="small"
            v-if="project.status"
            class="!border-2 !border-green-900 !bg-[#183421] !text-green-500 text-xs"
          >
            {{ getStatusNameLocal(project.status)}}
          </v-chip>
        </div>
        
        
        <p v-if="project.description"
        :class="[
          'text-sm',
          isDarkMode ? 'text-gray-300' : 'text-gray-600'
        ]">
          {{ project.description}}
        </p>
      </div>

        
        <div v-if="project.tags" class="md:px-6 px-0 md:pb-4 pb-2">
      <div class="flex flex-wrap gap-2">
        <v-chip
          v-for="tag in (project.tags)"
          :key="tag"
          size="small"
          :class="[
            'text-xs !font-bold',
            isDarkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700'
          ]"
        >
          {{ tag }}
        </v-chip>
      </div>
    </div>

    
    <div class="md:px-6 px-0 md:pb-4 pb-2">
      <v-sheet class="overflow-hidden rounded-lg" max-width="700">
        <v-carousel
          v-if="mediaAssets.length > 0"
          v-model="currentIndex"
          direction="vertical"
          height="300"
          show-arrows
          :progress="isDarkMode ? 'blue' : 'black'"
          vertical-arrows="left"
          vertical-delimiters="right"
          hide-delimiter-background
          :class="isDarkMode ? 'bg-gradient-to-r from-blue-800 to-blue-950 rounded-lg' : 'bg-gradient-to-r from-[#f5f5f5] to-[#bfbfbf] rounded-lg'"
        >
          <v-carousel-item
            v-for="(file, index) in mediaAssets"
            :key="index"
            :src="getFileUrlForPreview(file)"
            contain
          />
        </v-carousel>

        <div v-else :class="isDarkMode ? 'h-80 rounded-lg overflow-hidden bg-gradient-to-r from-blue-800 to-blue-950 flex items-center justify-center' : 
        'h-80 rounded-lg overflow-hidden bg-gradient-to-r from-[#f5f5f5] to-[#bfbfbf] flex items-center justify-center'">
          <div :class="isDarkMode ? 'text-center text-white' : 'text-center text-black'">
            <v-icon size="64" :color="isDarkMode ? 'white' : 'black'" class="mb-4">mdi-cellphone</v-icon>
            <p :class="isDarkMode ? 'text-lg font-medium text-white' : 'text-lg font-medium text-black'">Project Preview</p>
          </div>
        </div>
      </v-sheet>
    </div>

    
    <div v-if="project.technologies" class="md:px-6 px-0 md:pb-4 pb-2">
      <h4 v-if="project.technologies.length > 0" :class="[
        'font-bold md:mb-3 mb-1',
        isDarkMode ? 'text-white' : 'text-gray-900'
      ]">
        Technologies
      </h4>
      <div class="flex flex-wrap gap-2">
        <v-chip
          v-for="tech in (project.technologies)"
          :key="tech"
          size="small"
          :class="[
            'text-lg !font-bold',
            isDarkMode ? '!border-2 !border-blue-900 !bg-[#23153A] !text-blue-400' : '!border-2 !border-gray-950 !bg-white !text-gray-950'
          ]"
        >
          {{ tech }}
        </v-chip>
      </div>
    </div>

    
    <div v-if="project.start_date || project.end_date" class="md:px-6 px-0 md:pb-6 pb-2">
      <div class="flex items-center gap-2 text-sm">
        <v-icon 
          size="16" 
          :color="isDarkMode ? 'gray-300' : 'gray-800'"
        >
          mdi-calendar
        </v-icon>
        <span :class="[
          'transition-colors',
          isDarkMode ? 'text-gray-300' : 'text-gray-800'
        ]">
          {{ formatDate(project.start_date || '')}} 
          {{ project.end_date ? `- ${formatDate(project.end_date)}` : '' }}
        </span>
      </div>
    </div>

    
    <div v-if="documentAssets.length > 0 && effectivePreviewSettings.showAssets" class="md:px-6 px-0 md:pb-6 pb-2">
      <div class="flex items-center gap-2 mb-3">
        <v-icon 
          size="16" 
          :color="isDarkMode ? 'gray-300' : 'gray-500'"
        >
          mdi-file-document-multiple
        </v-icon>
        <h4 :class="[
          'font-bold',
          isDarkMode ? 'text-white' : 'text-gray-900'
        ]">
          Documents
        </h4>
      </div>
      <div class="flex flex-wrap gap-2">
        <v-chip
          v-for="document in documentAssets"
          :key="document.id || document.name"
          size="small"
          variant="outlined"
          :class="[
            'cursor-pointer hover:bg-gray-100 transition-colors',
            isDarkMode ? 'border-gray-600 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700'
          ]"
          @click="openDocument(document)"
        >
          <v-icon 
            :icon="getFileIcon(document.type || document.name || '')" 
            :color="getFileColor(document.type || document.name || '')"
            size="small"
            class="mr-1"
          />
          <span class="text-xs">{{ document.display_name || document.name || 'Document' }}</span>
          <v-icon 
            icon="mdi-download" 
            size="x-small" 
            class="ml-1 text-gray-500"
          />
        </v-chip>
      </div>
    </div>
    </div>

    
    <div  v-if="githubLink || demoLink || additionalLinks.length" class="md:px-6 px-0 md:py-6 py-2 md:pb-0 pb-2 border-t border-gray-400">
      <div class="flex flex-col gap-2 md:gap-3">
        <div class="flex flex-col md:flex-row gap-2 md:gap-3">
          
          <v-btn
            v-if="githubLink"
            variant="elevated"
            size="small"
            :class="[
              'w-full md:flex-1 border rounded-lg !text-sm py-2 md:py-0 content-center',
              isDarkMode 
                ? '!bg-blue-950 text-white' 
                : 'text-gray-900 !bg-gray-100 !border-gray-600'
            ]"
            :href="githubLink.url"
            target="_blank"
            prepend-icon="mdi-github"
          >
            {{ githubLink.title }}
          </v-btn>
          
          
          <v-btn
            v-if="demoLink"
            variant="elevated"
            size="small"
            :class="[
              'w-full md:flex-1 rounded-lg !text-sm py-2 md:py-0 content-center',
              isDarkMode 
                ? '!bg-blue-950 text-white' 
                : 'text-gray-900 !bg-gray-100 !border-gray-600'
            ]"
            :href="demoLink.url"
            target="_blank"
            prepend-icon="mdi-open-in-new"
          >
            {{ demoLink.title }}
          </v-btn>
        </div>

        
        <div v-if="additionalLinks.length" class="flex flex-col gap-2">
          <v-btn
            v-for="link in additionalLinks"
            :key="link.url"
            variant="elevated"
            size="small"
            :class="[
              'w-full rounded-lg py-2 md:py-0 content-center',
              isDarkMode 
                ? '!bg-blue-950 text-white' 
                : 'text-gray-900 !bg-gray-100 !border-gray-600'
            ]"
            :href="link.url"
            target="_blank"
            prepend-icon="mdi-open-in-new"
          >
            {{ link.title }}
          </v-btn>
        </div>
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
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
import { useAppearance } from '@/composables/useAppearance';

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

const { isDark } = useAppearance();
const isDarkMode = computed(() => isDark.value);
const currentIndex = ref(0);


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


const getFileUrlForPreview = (file: any): string => {
  
  if (file.file && file.file instanceof File) {
    return URL.createObjectURL(file.file);
  }
  
  return getFileUrl(file);
};


const mediaAssets = computed(() => {
  if (!props.project.assets) return [];
  return props.project.assets.filter(file => {
    
    const assetType = file.asset_type?.key || '';
    const filename = file.filename || file.display_name || file.name || file.path || file.url || '';
    const isImage = assetType === 'images' || filename.match(/\.(jpg|jpeg|png|gif|svg|webp|bmp|tiff)$/i);
    const isVideo = assetType === 'videos' || filename.match(/\.(mp4|avi|mov|wmv|flv|webm|mkv|m4v)$/i);
    return isImage || isVideo;
  });
});


const documentAssets = computed(() => {
  if (!props.project.assets) return [];
  return props.project.assets.filter(file => {
    
    const assetType = file.asset_type?.key || '';
    const filename = file.filename || file.display_name || file.name || file.path || file.url || '';
    const isImage = assetType === 'images' || filename.match(/\.(jpg|jpeg|png|gif|svg|webp|bmp|tiff)$/i);
    const isVideo = assetType === 'videos' || filename.match(/\.(mp4|avi|mov|wmv|flv|webm|mkv|m4v)$/i);
    return !isImage && !isVideo; 
  });
});


const githubLink = computed(() => {
  if (!props.project.links) return null;
  return props.project.links.find(link => link.type === 'github');
});

const demoLink = computed(() => {
  if (!props.project.links) return null;
  return props.project.links.find(link => link.type === 'liveurl');
});

const additionalLinks = computed(() => {
  if (!props.project.links) return [];
  return props.project.links.filter(link => 
    link.type !== 'github' && link.type !== 'liveurl'
  );
});


const getStatusNameLocal = (statusKey: string) => {
  return getStatusName(statusKey, props.statuses);
};

const getCategoryNameLocal = (categoryKey: string) => {
  return getCategoryName(categoryKey, props.categories);
};

const openDocument = (document: any) => {
  const url = getFileUrl(document);
  window.open(url, '_blank');
};
</script>