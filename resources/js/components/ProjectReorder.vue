<template>
  <div class="project-reorder">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <v-icon icon="mdi-drag" color="blue" size="large"></v-icon>
        <h2 :class="isDark ? 'text-xl font-semibold text-gray-300' : 'text-xl font-semibold text-gray-900'">Reorder Public Projects</h2>
      </div>
      <div class="flex gap-2">
        <v-btn
          variant="outlined"
          :color="isDark ? 'gray-300' : 'gray-800'"
          @click="$emit('close')"
        >
          Cancel
        </v-btn>
        <v-btn
          :color="isDark ? 'success' : 'success'"
          :variant="isDark ? 'tonal' : 'elevated'"
          :loading="saving"
          :disabled="!hasChanges"
          @click="saveOrder"
        >
          Save Order
        </v-btn>
      </div>
    </div>

    <!-- Instructions -->
    <v-alert
      type="info"
      variant="tonal"
      class="mb-6"
    >
      <div class="flex items-start gap-3">
        <div>
          <div class="font-medium mb-1">Drag and drop to reorder your public projects</div>
          <div :class="isDark ? 'text-sm text-gray-600' : 'text-sm text-gray-800'">
            The order you set here will be displayed on your public portfolio page. 
            Hidden projects are automatically removed from the order.
          </div>
        </div>
      </div>
    </v-alert>

    <!-- Projects List -->
    <div class="space-y-3">
      <draggable
        v-model="orderedProjects"
        item-key="id"
        class="space-y-3"
        :animation="200"
        ghost-class="ghost-card"
        chosen-class="chosen-card"
        drag-class="dragging-card"
        @start="dragStart"
        @end="dragEnd"
      >
        <template #item="{ element: project, index }">
          <div class="project-item">
            <v-card class="cursor-move hover:shadow-lg transition-shadow duration-200">
              <div class="flex items-center gap-4 p-4">
                <!-- Drag Handle -->
                <div class="drag-handle">
                  <v-icon icon="mdi-drag" :color="isDark ? 'gray-300' : 'gray-800'" size="small"></v-icon>
                </div>

                <!-- Project Info -->
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 :class="isDark ? 'text-lg font-medium text-gray-300' : 'text-lg font-medium text-gray-900'">{{ project.name }}</h3>
                    <v-chip
                      v-if="project.category"
                      size="small"
                      variant="outlined"
                      :color="isDark ? 'blue' : 'blue'"
                    >
                      {{ getCategoryName(project.category, categories) }}
                    </v-chip>
                    <v-chip
                      v-if="project.status"
                      size="small"
                      :color="isDark ? getStatusColor(project.status) : getStatusColor(project.status)"
                    >
                      {{ getStatusName(project.status, statuses) }}
                    </v-chip>
                  </div>
                  
                  <p v-if="project.description" :class="isDark ? 'text-sm text-gray-500 line-clamp-2' : 'text-sm text-gray-800 line-clamp-2'">
                    {{ project.description }}
                  </p>
                </div>

                <!-- Order Number -->
                <div class="order-number">
                  <v-chip
                    size="small"
                    variant="outlined"
                    :color="isDark ? 'primary' : 'primary'"
                  >
                    {{ index + 1 }}
                  </v-chip>
                </div>
              </div>
            </v-card>
          </div>
        </template>
      </draggable>
    </div>

    <!-- Empty State -->
    <div v-if="orderedProjects.length === 0" class="text-center py-12">
      <v-icon icon="mdi-folder-open" size="x-large" :color="isDark ? 'gray-300' : 'gray-800'" class="mb-4"></v-icon>
      <h3 :class="isDark ? 'text-lg font-medium text-gray-300 mb-2' : 'text-lg font-medium text-gray-900 mb-2'">No Public Projects</h3>
      <p :class="isDark ? 'text-gray-500' : 'text-gray-800'">Make some projects public to reorder them here.</p>
    </div>

    <!-- Snackbar for feedback -->
    <v-snackbar
      v-model="showSnackbar"
      :color="snackbarColor"
      timeout="3000"
    >
      {{ snackbarMessage }}
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import { 
  getStatusColor, 
  getStatusName, 
  getCategoryName 
} from '@/lib/projectUtils';
import type { Project } from '@/types';

interface Props {
  projects: Project[];
  categories?: Array<{ name: string; key: string }>;
  statuses?: Array<{ name: string; key: string }>;
  isDark?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => [],
  statuses: () => []
});

const emit = defineEmits<{
  close: [];
  saved: [Project[]];
}>();

// State
const dragging = ref(false);
const saving = ref(false);
const showSnackbar = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('success');

// Computed
const orderedProjects = ref<Project[]>([...props.projects]);

const hasChanges = computed(() => {
  if (orderedProjects.value.length !== props.projects.length) return true;
  
  return orderedProjects.value.some((project, index) => {
    const originalProject = props.projects[index];
    return project.id !== originalProject.id;
  });
});

// Methods
const dragStart = () => {
  dragging.value = true;
};

const dragEnd = () => {
  dragging.value = false;
};

const saveOrder = async () => {
  if (!hasChanges.value) return;
  
  saving.value = true;
  
  try {
    const projectIds = orderedProjects.value
      .map(project => project.id)
      .filter((id): id is number => id !== undefined);

    const response = await fetch('/projects/update-sorting-orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ project_ids: projectIds }),
    });

    const data = await response.json();

    if (response.ok) {
      snackbarMessage.value = 'Project order saved successfully!';
      snackbarColor.value = 'success';
      showSnackbar.value = true;
      emit('saved', orderedProjects.value);
      
      // Close the modal after successful save
      setTimeout(() => {
        emit('close');
      }, 500); // Wait 1.5 seconds to show the success message
    } else {
      snackbarMessage.value = data.error || 'Failed to save project order. Please try again.';
      snackbarColor.value = 'error';
      showSnackbar.value = true;
    }
  } catch (error) {
    snackbarMessage.value = 'An error occurred while saving.';
    snackbarColor.value = 'error';
    showSnackbar.value = true;
  } finally {
    saving.value = false;
  }
};

// Watch for changes in props
watch(() => props.projects, (newProjects) => {
  orderedProjects.value = [...newProjects];
}, { deep: true });
</script>

<style scoped>
.project-item {
  transition: all 0.3s ease;
}

.ghost-card {
  opacity: 0.5;
  background: #c8ebfb;
  border: 2px dashed #2196f3;
}

.chosen-card {
  background: #e3f2fd;
  transform: rotate(5deg);
}

.dragging-card {
  background: #f5f5f5;
  transform: rotate(5deg);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.drag-handle {
  cursor: grab;
  padding: 4px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.drag-handle:hover {
  background-color: rgba(156, 163, 175, 0.1);
}

.drag-handle:active {
  cursor: grabbing;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
