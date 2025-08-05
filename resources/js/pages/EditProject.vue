<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import { 
  getLinkIcon, 
  getFileIcon, 
  getFileColor, 
  getFileType, 
  formatFileSize, 
  isPreviewable, 
  getFileUrl 
} from '@/lib/projectUtils';
import PreviewSettings from '@/components/PreviewSettings.vue';

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);

// Get data from backend
const project = computed(() => page.props.project || {} as any);
const categories = computed(() => page.props.categories || []);
const statuses = computed(() => page.props.statuses || []);
const userTechnologies = computed(() => (page.props.userTechnologies as string[]) || []);

const snackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

// Form data - prefill with existing project data
const form = useForm({
  name: project.value.name || '',
  description: project.value.description || '',
  category: project.value.category || '',
  tags: project.value.tags || [] as string[],
  assets: [] as File[], // New assets only
  existingAssets: project.value.assets || [] as any[], // Existing assets
  links: project.value.links || [] as Array<{ title: string; url: string }>,
  start_date: project.value.start_date || '',
  end_date: project.value.end_date || '',
  status: project.value.status || '',
  technologies: project.value.technologies || [] as string[],
  user_id: currentUser.value?.id || null,
  preview_settings: project.value.preview_settings || {
    showDescription: true,
    showCategory: true,
    showStatus: true,
    showDates: true,
    showTags: true,
    showTechnologies: true,
    showLinks: true,
    showAssets: true,
  },
});

// Update user_id when currentUser changes
const updateUserId = () => {
  form.user_id = currentUser.value?.id || null;
};

// Watch for changes in currentUser
watch(currentUser, updateUserId, { immediate: true });

// Available options
const statusOptions = computed(() => 
  statuses.value?.map((status: any) => ({
    value: status.key,
    label: status.name
  })) || []
);

// Date formatting
const formattedStartDate = computed(() => {
  if (!form.start_date) return '';
  return new Date(form.start_date).toLocaleDateString();
});

const formattedEndDate = computed(() => {
  if (!form.end_date) return '';
  return new Date(form.end_date).toLocaleDateString();
});

// Date update methods
const updateStartDate = () => {
  // This will be handled by the date picker
};

const updateEndDate = () => {
  // This will be handled by the date picker
};

// Link helper methods
const hasLinkWithTitle = (title: string) => {
  return form.links.some((link: any) => link.title.toLowerCase().includes(title.toLowerCase()));
};

const addGithubLink = () => {
  if (githubUrl.value.trim()) {
    form.links.push({
      title: 'Github',
      url: githubUrl.value.trim()
    });
    githubUrl.value = '';
  }
};

const addProjectDemoLink = () => {
  if (projectDemo.value.trim()) {
    form.links.push({
      title: 'Project Demo',
      url: projectDemo.value.trim()
    });
    projectDemo.value = '';
  }
};

// File handling methods
// Helper function to validate file size
const validateFileSize = (file: File): boolean => {
  const maxFileSize = 100 * 1024 * 1024; // 100MB in bytes
  if (file.size > maxFileSize) {
    snackbarMessage.value = `File "${file.name}" is too large. Maximum size is 100MB.`;
    snackbarColor.value = 'error';
    snackbar.value = true;
    return false;
  }
  return true;
};

const openFileDialog = () => {
  fileInputRef.value?.click();
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    const files = Array.from(target.files);
    
    // Check file sizes before adding
    for (const file of files) {
      if (!validateFileSize(file)) {
        return; // Don't add any files if one is too large
      }
    }
    
    form.assets.push(...files);
  }
};

const handleFileDrop = (event: DragEvent) => {
  event.preventDefault();
  if (event.dataTransfer?.files) {
    const files = Array.from(event.dataTransfer.files);
    
    // Check file sizes before adding
    for (const file of files) {
      if (!validateFileSize(file)) {
        return; // Don't add any files if one is too large
      }
    }
    
    form.assets.push(...files);
  }
};

const previewFile = (file: File) => {
  if (file.type.startsWith('image/')) {
    const url = URL.createObjectURL(file);
    window.open(url, '_blank');
  }
};

// Normalize technology name for comparison (lowercase and remove spaces)
const normalizeTechnologyName = (name: string): string => {
  return name.toLowerCase().replace(/\s+/g, '');
};

// Check if technology already exists (case-insensitive and space-insensitive)
const isTechnologyDuplicate = (newTech: string, existingTechs: string[]): boolean => {
  const normalizedNewTech = normalizeTechnologyName(newTech);
  return existingTechs.some(tech => normalizeTechnologyName(tech) === normalizedNewTech);
};

// Local state
const newTag = ref('');
const newLinkTitle = ref('');
const newLinkUrl = ref('');
const newTechnology = ref('');
const newTeamMember = ref('');
const newAssets = ref<File[]>([]);
const fileInputRef = ref<HTMLInputElement>();
const githubUrl = ref('');
const projectDemo = ref('');

// Preview settings
const showPreviewSettings = ref(false);
const previewSettings = computed(() => form.preview_settings);

// Methods
const addTag = () => {
  const tag = newTag.value.trim();
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag);
    newTag.value = '';
  }
};

const removeTag = (index: number) => {
  form.tags.splice(index, 1);
};

const addTechnology = () => {
  const techName = newTechnology.value.trim();
  if (techName && !isTechnologyDuplicate(techName, form.technologies)) {
    form.technologies.push(techName);
    newTechnology.value = '';
  }
};

const removeTechnology = (index: number) => {
  form.technologies.splice(index, 1);
};

const addLink = () => {
  if (newLinkTitle.value.trim() && newLinkUrl.value.trim()) {
    form.links.push({
      title: newLinkTitle.value.trim(),
      url: newLinkUrl.value.trim()
    });
    newLinkTitle.value = '';
    newLinkUrl.value = '';
  }
};

const removeLink = (index: number) => {
  form.links.splice(index, 1);
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    const files = Array.from(target.files);
    
    // Check file sizes before adding
    for (const file of files) {
      if (!validateFileSize(file)) {
        return; // Don't add any files if one is too large
      }
    }
    
    form.assets.push(...files);
  }
};

const removeAsset = (index: number) => {
  form.assets.splice(index, 1);
};

const removeExistingAsset = (index: number) => {
  form.existingAssets.splice(index, 1);
};

const updateProject = async () => {
  // Clear previous errors
  form.clearErrors();
  
  // Validate required fields
  if (!form.name.trim()) {
    form.setError('name', 'Project name is required');
    return false;
  }

  // Validate file sizes (100MB limit)
  for (let i = 0; i < form.assets.length; i++) {
    const file = form.assets[i];
    if (!validateFileSize(file)) {
      form.setError('assets', `File "${file.name}" is too large. Maximum size is 100MB.`);
      return false;
    }
  }

  try {
    // Check if there are any file uploads
    const hasFiles = form.assets && form.assets.length > 0;
    
    if (hasFiles) {
      // Use post method with _method: PUT for file uploads
      await form.post(`/projects/${project.value.id}`, {
        data: {
          _method: 'PUT'
        },
        onSuccess: () => {
          snackbarMessage.value = 'Project updated successfully!';
          snackbarColor.value = 'success';
          snackbar.value = true;
        },
        onError: (errors: any) => {
          snackbarMessage.value = 'Failed to update project. Please check the form.';
          snackbarColor.value = 'error';
          snackbar.value = true;
        }
      } as any);
    } else {
      // Use put method for forms without file uploads
      await form.put(`/projects/${project.value.id}`, {
        onSuccess: () => {
          snackbarMessage.value = 'Project updated successfully!';
          snackbarColor.value = 'success';
          snackbar.value = true;
        },
        onError: (errors: any) => {
          snackbarMessage.value = 'Failed to update project. Please check the form.';
          snackbarColor.value = 'error';
          snackbar.value = true;
        }
      });
    }
  } catch (error) {
    snackbarMessage.value = 'An error occurred while updating the project.';
    snackbarColor.value = 'error';
    snackbar.value = true;
  }
};

const cancelEdit = () => {
  router.visit('/');
};

// Computed properties for assets
const allAssets = computed(() => {
  const existingAssets = form.existingAssets.map((asset: any) => ({
    ...asset,
    isExisting: true
  }));
  const newAssets = form.assets.map(file => ({
    id: `new-${file.name}-${file.size}`,
    name: file.name,
    filename: file.name, // For ProjectCard compatibility
    display_name: file.name, // For ProjectCard compatibility
    size: file.size,
    type: file.type,
    isExisting: false,
    file
  }));
  return [...existingAssets, ...newAssets];
});

const imageAssets = computed(() => {
  return allAssets.value.filter(file => {
    const fileType = file.type || file.name || '';
    return fileType === 'image' || fileType.startsWith('image/');
  });
});

const nonImageAssets = computed(() => {
  return allAssets.value.filter(file => {
    const fileType = file.type || file.name || '';
    return fileType !== 'image' && !fileType.startsWith('image/');
  });
});

const cancel = () => {
  router.visit('/');
};
</script>

<template>
  <AppLayout>
    <v-main>
      <v-container class="py-8" @keydown.enter.prevent>
        <!-- Header -->
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
          <v-btn
            icon="mdi-arrow-left"
            variant="outlined"
            @click="cancel"
            color="gray"
            class="mb-4"
          ></v-btn>
          <h1 class="text-3xl font-bold text-gray-800">
            Edit Project
          </h1>
          <p class="text-gray-600 mt-2">Edit the details below to modify your project</p>
          </div>
          <div class="d-flex gap-2">
            <v-btn
              variant="outlined"
              @click="cancelEdit"
            >
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              @click="updateProject"
              :loading="form.processing"
            >
              Update Project
            </v-btn>
          </div>
        </div>

        <v-row>
          <!-- Form Section -->
          <v-col cols="12" lg="7">
              <!-- Project Information Section -->
              <div class="mb-8">
                <div class="flex items-center mb-8">
                  <div class="w-1 h-8 bg-gradient-to-b from-blue-500 to-cyan-500 rounded-full mr-4"></div>
                  <h2 class="text-2xl font-bold text-slate-800">Project Information</h2>
                </div>
                
                <v-row>
                  <v-col cols="12" md="8">
                <v-text-field
                  v-model="form.name"
                  label="Project Name *"
                  placeholder="Enter your project name"
                  variant="outlined"
                  :error-messages="form.errors.name"
                  color="primary"
                  density="compact"
                  class="text-field-modern"
                  required
                >
                  <template v-slot:prepend-inner>
                    <v-icon>mdi-rocket-launch</v-icon>
                  </template>
                </v-text-field>
                </v-col>
                      
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.category"
                    :items="categories"
                    item-title="name"
                    item-value="key"
                    label="Category *"
                    placeholder="Select category"
                    variant="outlined"
                    :error-messages="form.errors.category"
                    color="primary"
                    density="compact"
                    class="select-modern"
                    required
                  >
                    <template v-slot:prepend-inner>
                      <v-icon>mdi-folder-star</v-icon>
                    </template>
                  </v-select>
                </v-col>
                </v-row>

                <v-textarea
                  v-model="form.description"
                  label="Project Description"
                  placeholder="Describe your project vision, goals, and what makes it special..."
                  variant="outlined"
                  rows="5"
                  color="primary"
                  density="compact"
                  :error-messages="form.errors.description"
                  class="textarea-modern"
                >
                  <template v-slot:prepend-inner>
                    <v-icon>mdi-text-box</v-icon>
                  </template>
                </v-textarea>
              </div>

              <!-- Project Timeline Section -->
              <div class="mb-2">
                <div class="flex items-center mb-8">
                  <div class="w-1 h-8 bg-gradient-to-b from-blue-500 to-cyan-500 rounded-full mr-4"></div>
                  <h2 class="text-2xl font-bold text-slate-800">Project Timeline</h2>
                </div>
                
                <v-row>
                  <v-col cols="12" md="4">
                    <v-menu
                      :close-on-content-click="false"
                      :close-on-click-outside="true"
                      :persistent="false"
                    >
                      <template v-slot:activator="{ props }">
                        <v-text-field
                          v-model="formattedStartDate"
                          label="Start Date"
                          variant="outlined"
                          :error-messages="form.errors.start_date"
                          prepend-inner-icon="mdi-calendar-start"
                          readonly
                          density="compact"
                          color="gray"
                          v-bind="props"
                          class="date-field-modern"
                        ></v-text-field>
                      </template>
                      <v-date-picker
                        v-model="form.start_date"
                        @update:model-value="updateStartDate"
                        show-adjacent-months
                        color="gray"
                        @click:date="() => {}"
                      ></v-date-picker>
                    </v-menu>
                  </v-col>
                  
                  <v-col cols="12" md="4">
                    <v-menu
                      :close-on-content-click="false"
                      :close-on-click-outside="true"
                      :persistent="false"
                    >
                      <template v-slot:activator="{ props }">
                        <v-text-field
                          v-model="formattedEndDate"
                          label="Target End Date"
                          variant="outlined"
                          :error-messages="form.errors.end_date"
                          prepend-inner-icon="mdi-calendar-check"
                          readonly
                          density="compact"
                          color="primary"
                          v-bind="props"
                          class="date-field-modern"
                        ></v-text-field>
                      </template>
                      <v-date-picker
                        v-model="form.end_date"
                        @update:model-value="updateEndDate"
                        show-adjacent-months
                        color="gray"
                        @click:date="() => {}"
                      ></v-date-picker>
                    </v-menu>
                  </v-col>
                  
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="form.status"
                      :items="statusOptions"
                      item-title="label"
                      item-value="value"
                      density="compact"
                      label="Current Status"
                      variant="outlined"
                      color="primary"
                      :error-messages="form.errors.status"
                      class="select-modern"
                    >
                      <template v-slot:prepend-inner>
                        <v-icon>mdi-progress-clock</v-icon>
                      </template>
                    </v-select>
                  </v-col>
                </v-row>
              </div>

              <!-- Tags and Technologies -->
              <div class="mb-2">
                <div class="flex items-center mb-8">
                  <div class="w-1 h-8 bg-gradient-to-b from-green-500 to-emerald-500 rounded-full mr-4"></div>
                  <h2 class="text-2xl font-bold text-slate-800">Tags & Technologies</h2>
                </div>
                
                <v-row>
                  <!-- Tags -->
                  <v-col cols="12" md="6">
                    <v-combobox
                      v-model="form.tags"
                      v-model:search="newTag"
                      :items="[]"
                      label="Add Tag"
                      placeholder="Enter or select a tag"
                      variant="outlined"
                      @click:append-inner="addTag"
                      :hide-no-data="false"
                      multiple
                      chips
                      closable-chips
                      hide-selected
                      clearable
                      density="compact"
                      color="primary"
                      class="text-field-modern"
                    >
                      <template v-slot:prepend-inner>
                        <v-icon>mdi-tag</v-icon>
                      </template>

                      <template v-slot:no-data>
                        <v-list-item>
                          <v-list-item-title>
                            No results matching
                            <strong>"{{ newTag || 'your input' }}"</strong>. Press <kbd>enter</kbd> to create a new one.
                          </v-list-item-title>
                        </v-list-item>
                      </template>
                    </v-combobox>
                  </v-col>

                  <!-- Technologies -->
                  <v-col cols="12" md="6">
                    <v-combobox
                      v-model="form.technologies"
                      v-model:search="newTechnology"
                      :items="userTechnologies"
                      label="Add Technology"
                      placeholder="Select or type a technology"
                      variant="outlined"
                      @click:append-inner="addTechnology"
                      :hide-no-data="false"
                      multiple
                      chips
                      closable-chips
                      hide-selected
                      clearable
                      density="compact"
                      color="primary"
                      class="select-modern"
                    >
                      <template v-slot:prepend-inner>
                        <v-icon>mdi-cog</v-icon>
                      </template>

                      <template v-slot:no-data>
                        <v-list-item>
                          <v-list-item-title>
                            No results matching
                            <strong>"{{ newTechnology || 'your input' }}"</strong>.
                            Press <kbd>enter</kbd> to create a new one.
                          </v-list-item-title>
                        </v-list-item>
                      </template>
                    </v-combobox>
                  </v-col>
                </v-row>
              </div>

              <!-- Links -->
              <div class="mb-8">
                <div class="flex items-center mb-8">
                  <div class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full mr-4"></div>
                  <h2 class="text-2xl font-bold text-slate-800">Links</h2>
                </div>
                
                <div>
                  <!-- Github Link -->
                  <div v-if="!hasLinkWithTitle('Github')">
                    <v-row>
                      <v-col cols="12" md="10">
                        <v-text-field
                          v-model="githubUrl"
                          label="Github"
                          placeholder="https://github.com/..."
                          variant="outlined"
                          density="compact"
                          color="primary"
                          class="text-field-modern compact-field"
                        >
                          <template v-slot:prepend-inner>
                            <v-icon icon="mdi-github"></v-icon>
                          </template>
                        </v-text-field>
                      </v-col>
                      
                      <v-col cols="12" md="2" class="self-center">
                        <v-btn
                          variant="outlined"
                          @click="addGithubLink"
                          :disabled="!githubUrl.trim()"
                          class="w-auto"
                          density="compact"
                        >
                          <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                          Add
                        </v-btn>
                      </v-col>
                    </v-row>
                  </div>

                  <!-- Project Demo Link -->
                  <div v-if="!hasLinkWithTitle('Project Demo')">
                    <v-row>
                      <v-col cols="12" md="10">
                        <v-text-field
                          v-model="projectDemo"
                          label="Project Demo"
                          placeholder="https://..."
                          variant="outlined"
                          density="compact"
                          class="text-field-modern compact-field"
                        >
                          <template v-slot:prepend-inner>
                            <v-icon icon="mdi-web"></v-icon>
                          </template>
                        </v-text-field>
                      </v-col>
                      
                      <v-col cols="12" md="2" class="self-center">
                        <v-btn
                          variant="outlined"
                          @click="addProjectDemoLink"
                          :disabled="!projectDemo.trim()"
                          class="w-auto"
                          density="compact"
                        >
                          <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                          Add
                        </v-btn>
                      </v-col>
                    </v-row>
                  </div>
                </div>
                
                <div>
                  <v-row>
                    <v-col cols="12" md="5">
                      <v-text-field
                        v-model="newLinkTitle"
                        label="Link Title"
                        placeholder="e.g., Documentation"
                        variant="outlined"
                        @keyup.enter="addLink"
                        density="compact"
                        class="text-field-modern compact-field"
                      >
                        <template v-slot:prepend-inner>
                          <v-icon>mdi-link-variant</v-icon>
                        </template>
                      </v-text-field>
                    </v-col>
                    
                    <v-col cols="12" md="5">
                      <v-text-field
                        v-model="newLinkUrl"
                        label="URL"
                        placeholder="https://..."
                        variant="outlined"
                        @keyup.enter="addLink"
                        density="compact"
                        class="text-field-modern compact-field"
                      >
                        <template v-slot:prepend-inner>
                          <v-icon>mdi-web</v-icon>
                        </template>
                      </v-text-field>
                    </v-col>
                    
                    <v-col cols="12" md="2" class="self-center">
                      <v-btn
                        variant="outlined"
                        @click="addLink"
                        :disabled="!newLinkTitle.trim() || !newLinkUrl.trim()"
                        class="w-auto"
                        density="compact"
                      >
                        <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                        Add
                      </v-btn>
                    </v-col>
                  </v-row>
                </div>
                
                <div v-if="form.links.length > 0" class="mb-4 mt-4">
                  <div class="flex flex-wrap gap-2">
                    <v-chip
                      v-for="(link, index) in form.links"
                      :key="index"
                      variant="outlined"
                      density="compact"
                      class="max-w-full text-sm !py-3 !px-4"
                    >
                      <div class="flex grow items-center gap-2 w-full">
                        <!-- Left: icon, title and URL -->
                        <div class="flex items-center flex-1 min-w-0 gap-2">
                          <v-icon 
                            :icon="getLinkIcon(link.title)" 
                            size="small"
                          ></v-icon>
                          <div class="flex-1 min-w-0 flex gap-1">
                            <span class="truncate font-medium max-w-[120px]">
                              {{ link.title }}:
                            </span>
                            <a
                              :href="link.url"
                              target="_blank"
                              class="text-blue-600 hover:underline truncate max-w-[200px]"
                            >
                              {{ link.url || 'Click to add URL' }}
                            </a>
                          </div>
                        </div>

                        <!-- Right: buttons -->
                        <div class="flex-none items-center shrink-0">
                          <v-btn
                            icon="mdi-open-in-new"
                            variant="text"
                            size="x-small"
                            color="primary"
                            :href="link.url"
                            target="_blank"
                            :disabled="!link.url"
                          />
                          <v-btn
                            icon="mdi-close"
                            variant="text"
                            size="x-small"
                            color="error"
                            @click="removeLink(index)"
                          />
                        </div>
                      </div>
                    </v-chip>
                  </div>
                </div>
              </div>

              <!-- File Upload -->
              <div class="mb-8">
                <div class="flex items-center mb-8">
                  <div class="w-1 h-8 bg-gradient-to-b from-gray-500 to-orange-500 rounded-full mr-4"></div>
                  <h2 class="text-2xl font-bold text-slate-800">Assets</h2>
                </div>
                
                <!-- Existing Assets -->
                <div v-if="form.existingAssets.length > 0" class="mb-4">
                  <h4 class="text-lg font-medium mb-4">Existing Assets</h4>
                  <div class="flex flex-wrap gap-2 mb-4">
                    <v-chip
                      v-for="(asset, index) in form.existingAssets"
                      :key="index"
                      variant="outlined"
                      density="compact"
                      class="max-w-full !p-6"
                    >
                      <div class="flex items-center gap-3 w-full">
                        <!-- Left: icon and file info -->
                        <div class="flex items-center flex-1 min-w-0">
                          <v-icon
                            :icon="getFileIcon(asset.type || asset.name)"
                            :color="getFileColor(asset.type || asset.name)"
                            size="large"
                            class="mr-2"
                          />
                          <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate max-w-[180px]">
                              {{ asset.name }}
                            </div>
                            <div class="text-xs text-gray-500">
                              {{ getFileType(asset.type || asset.name) }}
                            </div>
                          </div>
                        </div>

                        <!-- Right: buttons -->
                        <div class="flex items-center gap-2">
                          <v-btn
                            icon="mdi-eye"
                            variant="text"
                            size="medium"
                            color="primary"
                            :href="asset.url"
                            target="_blank"
                            v-if="isPreviewable(asset.type || asset.name)"
                          />
                          <v-btn
                            icon="mdi-close"
                            variant="text"
                            size="medium"
                            color="error"
                            @click="removeExistingAsset(index)"
                          />
                        </div>
                      </div>
                    </v-chip>
                  </div>
                </div>
                
                <div class="mb-4">
                  <div 
                    class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors duration-200 bg-gray-50 hover:bg-gray-50 cursor-pointer"
                    @click="openFileDialog"
                    @dragover.prevent
                    @drop.prevent="handleFileDrop"
                  >
                    <div class="flex flex-col items-center justify-center !p-4">
                      <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center">
                        <v-icon size="32">mdi-cloud-upload</v-icon>
                      </div>
                      <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Upload Project Assets</h3>
                        <p class="text-gray-500 mb-4">Drag and drop files here, or click to browse</p>
                        <v-btn
                          variant="outlined"
                          size="large"
                          class="font-medium"
                          @click.stop="openFileDialog"
                        >
                          <v-icon icon="mdi-folder-open" class="mr-2"></v-icon>
                          Choose Files
                        </v-btn>
                      </div>
                      <div class="text-xs text-gray-400 mt-1">
                        Supports: Images, PDFs, Documents, Spreadsheets, Archives
                      </div>
                    </div>
                    
                    <input
                      ref="fileInputRef"
                      type="file"
                      multiple
                      accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
                      @change="handleFileUpload"
                      style="display: none;"
                    />
                  </div>
                </div>
                
                <div v-if="form.assets.length > 0" class="flex flex-wrap gap-2 mb-8">
                  <v-chip
                    v-for="(file, index) in form.assets"
                    :key="index"
                    variant="outlined"
                    density="compact"
                    class="max-w-full !p-6"
                  >
                    <div class="flex items-center gap-3 w-full">
                      <!-- Left: icon and file info -->
                      <div class="flex items-center flex-1 min-w-0">
                        <v-icon
                          :icon="getFileIcon(file.type || file.name)"
                          :color="getFileColor(file.type || file.name)"
                          size="large"
                          class="mr-2"
                        />
                        <div class="min-w-0">
                          <div class="text-sm font-medium text-gray-800 truncate max-w-[180px]">
                            {{ file.name }}
                          </div>
                          <div class="text-xs text-gray-500">
                            {{ formatFileSize(file.size) }} • {{ getFileType(file.type || file.name) }}
                          </div>
                        </div>
                      </div>

                      <!-- Right: buttons -->
                      <div class="flex items-center gap-2">
                        <v-btn
                          icon="mdi-eye"
                          variant="text"
                          size="medium"
                          color="primary"
                          @click="previewFile(file)"
                          v-if="isPreviewable(file.type || file.name)"
                        />
                        <v-btn
                          icon="mdi-close"
                          variant="text"
                          size="medium"
                          color="error"
                          @click="removeAsset(index)"
                        />
                      </div>
                    </div>
                  </v-chip>
                </div>
              </div>
              <div class="flex gap-4 justify-end">
                <v-btn
              variant="outlined"
              @click="cancelEdit"
            >
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              @click="updateProject"
              :loading="form.processing"
            >
              Update Project
            </v-btn>
              </div>
          </v-col>

          <!-- Preview Section -->
          <v-col cols="12" lg="5">
              <div class="d-flex align-center mb-4">
                <h3 class="text-lg font-semibold">Preview</h3>
                <v-btn
                  icon="mdi-cog"
                  size="small"
                  variant="text"
                  @click="showPreviewSettings = true"
                ></v-btn>
              </div>
              
              <ProjectCard
                :project="{
                  name: form.name || 'Project Name',
                  description: form.description,
                  category: form.category,
                  status: form.status,
                  start_date: form.start_date,
                  end_date: form.end_date,
                  tags: form.tags,
                  technologies: form.technologies,
                  links: form.links,
                  assets: allAssets,
                }"
                :categories="categories"
                :statuses="statuses"
                :preview-settings="previewSettings"
              />
          </v-col>
        </v-row>

        <!-- Preview Settings Modal -->
        <PreviewSettings
          v-model="showPreviewSettings"
          :settings="form.preview_settings"
        />

        <!-- Snackbar -->
        <v-snackbar
          v-model="snackbar"
          :color="snackbarColor"
          timeout="3000"
        >
          {{ snackbarMessage }}
        </v-snackbar>
      </v-container>
    </v-main>
  </AppLayout>
</template> 