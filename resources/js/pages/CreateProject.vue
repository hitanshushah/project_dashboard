<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectCard from '@/components/ProjectCard.vue';

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);

// Get data from backend
const categories = computed(() => page.props.categories || []);
const statuses = computed(() => page.props.statuses || []);

// Form data
const form = useForm({
  name: '',
  description: '',
  category: '',
  tags: [] as string[],
  assets: [] as File[],
  links: [] as Array<{ title: string; url: string }>,
  start_date: '',
  end_date: '',
  status: '',
  technologies: [] as string[],
});

// Available options
const statusOptions = computed(() => 
  statuses.value?.map((status: any) => ({
    value: status.key,
    label: status.name
  })) || []
);



const technologyOptions = [
  'React', 'Vue.js', 'Angular', 'Laravel', 'Node.js', 'Python', 'PHP',
  'JavaScript', 'TypeScript', 'HTML/CSS', 'MySQL', 'PostgreSQL', 'MongoDB',
  'Docker', 'AWS', 'Azure', 'Git', 'Figma', 'Adobe Creative Suite'
];

// Local state
const newTag = ref('');
const newLinkTitle = ref('');
const newLinkUrl = ref('');
const newTechnology = ref('');
const newTeamMember = ref('');
const newAssets = ref<File[]>([]);

// Methods
const addTag = () => {
  if (newTag.value.trim() && !form.tags.includes(newTag.value.trim())) {
    form.tags.push(newTag.value.trim());
    newTag.value = '';
  }
};

const removeTag = (index: number) => {
  form.tags.splice(index, 1);
};

const addLink = () => {
  const title = newLinkTitle.value.trim();
  const url = newLinkUrl.value.trim();
  
  if (!title || !url) {
    return;
  }
  
  // Basic URL validation
  let validUrl = url;
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    validUrl = 'https://' + url;
  }
  
  form.links.push({
    title: title,
    url: validUrl
  });
  
  // Clear the input fields
  newLinkTitle.value = '';
  newLinkUrl.value = '';
};

const removeLink = (index: number) => {
  form.links.splice(index, 1);
};

const addTechnology = () => {
  if (newTechnology.value.trim() && !form.technologies.includes(newTechnology.value.trim())) {
    form.technologies.push(newTechnology.value.trim());
    newTechnology.value = '';
  }
};

const removeTechnology = (index: number) => {
  form.technologies.splice(index, 1);
};


const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    form.assets = Array.from(target.files);
  }
};

const submit = () => {
  form.post('/projects', {
    onSuccess: () => {
      router.visit('/');
    }
  });
};

const cancel = () => {
  router.visit('/');
};

// Formatted date display
const formattedStartDate = computed(() => {
  if (!form.start_date) return '';
  return formatDateForDisplay(form.start_date);
});

const formattedEndDate = computed(() => {
  if (!form.end_date) return '';
  return formatDateForDisplay(form.end_date);
});

// Date formatting function
const formatDateForDisplay = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric'
  });
};

// Update handlers
const updateStartDate = (value: string) => {
  form.start_date = value;
};

const updateEndDate = (value: string) => {
  form.end_date = value;
};

// Asset management functions
const addAssets = (files: File | File[]) => {
  const fileArray = Array.isArray(files) ? files : [files];
  if (fileArray && fileArray.length > 0) {
    form.assets.push(...fileArray);
    newAssets.value = [];
  }
};

const removeAsset = (index: number) => {
  form.assets.splice(index, 1);
};

const getFileIcon = (fileType: string) => {
  if (fileType.startsWith('image/')) return 'mdi-image';
  if (fileType.includes('pdf')) return 'mdi-file-pdf-box';
  if (fileType.includes('word') || fileType.includes('doc')) return 'mdi-file-word-box';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'mdi-file-excel-box';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'mdi-folder-zip';
  if (fileType.includes('text')) return 'mdi-file-document';
  return 'mdi-file';
};

const getFileColor = (fileType: string) => {
  if (fileType.startsWith('image/')) return 'green';
  if (fileType.includes('pdf')) return 'red';
  if (fileType.includes('word') || fileType.includes('doc')) return 'blue';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'green';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'orange';
  return 'grey';
};

const getFileType = (fileType: string) => {
  if (fileType.startsWith('image/')) return 'Image';
  if (fileType.includes('pdf')) return 'PDF';
  if (fileType.includes('word') || fileType.includes('doc')) return 'Word Document';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'Excel Spreadsheet';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'Archive';
  if (fileType.includes('text')) return 'Text File';
  return 'File';
};

const formatFileSize = (bytes: number) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const isPreviewable = (fileType: string) => {
  return fileType.startsWith('image/') || fileType.includes('pdf') || fileType.includes('text');
};

const previewFile = (file: File) => {
  if (file.type.startsWith('image/')) {
    const url = URL.createObjectURL(file);
    window.open(url, '_blank');
  } else if (file.type.includes('pdf')) {
    const url = URL.createObjectURL(file);
    window.open(url, '_blank');
  } else {
    // For text files, could implement a text preview modal
    console.log('Preview not implemented for this file type');
  }
};

</script>

<template>
  <AppLayout>
    <v-main>
      <v-container class="py-8 max-w-7xl">
        <div class="mb-2">
          <v-btn
            icon="mdi-arrow-left"
            variant="text"
            @click="cancel"
            class="mb-4"
          ></v-btn>
          <h1 class="text-3xl font-bold text-gray-800">Create New Project</h1>
          <p class="text-gray-600 mt-2">Fill in the details below to create your new project</p>
        </div>

        <v-row>
          <!-- Left Section - Form -->
          <v-col cols="12" lg="7">
            <v-form @submit.prevent="submit">
              <v-card class="pa-6">
            <!-- Basic Information -->
            <div class="mb-2">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Project Information</h2>
              
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.name"
                    label="Project Name"
                    placeholder="Enter project name"
                    variant="outlined"
                    :error-messages="form.errors.name"
                    density="compact"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.category"
                    :items="categories"
                    item-title="name"
                    item-value="key"
                    label="Category"
                    placeholder="Select category"
                    variant="outlined"
                    :error-messages="form.errors.category"
                    density="compact"
                  ></v-select>
                </v-col>
              </v-row>

              <v-textarea
                v-model="form.description"
                label="Description"
                placeholder="Describe your project..."
                variant="outlined"
                rows="4"
                density="compact"
                :error-messages="form.errors.description"
              ></v-textarea>
            </div>

            <!-- Project Details -->
            <div class="mb-2">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Project Details</h2>
              
              <v-row>
                <v-col cols="12" md="4">
                  <v-menu>
                    <template v-slot:activator="{ props }">
                      <v-text-field
                        v-model="formattedStartDate"
                        label="Start Date"
                        variant="outlined"
                        :error-messages="form.errors.start_date"
                        prepend-icon="mdi-calendar"
                        readonly
                        density="compact"
                        v-bind="props"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="form.start_date"
                      @update:model-value="updateStartDate"
                      show-adjacent-months
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <v-col cols="12" md="4">
                  <v-menu>
                    <template v-slot:activator="{ props }">
                      <v-text-field
                        v-model="formattedEndDate"
                        label="End Date"
                        variant="outlined"
                        :error-messages="form.errors.end_date"
                        prepend-icon="mdi-calendar"
                        readonly
                        density="compact"
                        v-bind="props"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="form.end_date"
                      @update:model-value="updateEndDate"
                      show-adjacent-months
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
                    label="Status"
                    variant="outlined"
                    :error-messages="form.errors.status"
                  ></v-select>
                </v-col>
              </v-row>
            </div>

            <!-- Tags and Technologies -->
            <div class="mb-2">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Tags & Technologies</h2>
              
              <v-row>
                <!-- Tags -->
                <v-col cols="12" md="6">
                  <div>
                    <v-text-field
                      v-model="newTag"
                      label="Add Tag"
                      placeholder="Enter a tag"
                      variant="outlined"
                      @keyup.enter="addTag"
                      append-inner-icon="mdi-plus"
                      @click:append-inner="addTag"
                      density="compact"
                    ></v-text-field>
                  </div>
                  
                  <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2 mb-4">
                    <v-chip
                      v-for="(tag, index) in form.tags"
                      :key="index"
                      closable
                      @click:close="removeTag(index)"
                      color="primary"
                      variant="tonal"
                    >
                      {{ tag }}
                    </v-chip>
                  </div>
                </v-col>

                <!-- Technologies -->
                <v-col cols="12" md="6">
                  <div class="mb-4">
                    <v-autocomplete
                      v-model="newTechnology"
                      :items="technologyOptions"
                      label="Add Technology"
                      placeholder="Select or type a technology"
                      variant="outlined"
                      @keyup.enter="addTechnology"
                      append-inner-icon="mdi-plus"
                      @click:append-inner="addTechnology"
                      density="compact"
                    ></v-autocomplete>
                  </div>
                  
                  <div v-if="form.technologies.length > 0" class="flex flex-wrap gap-2 mb-4">
                    <v-chip
                      v-for="(tech, index) in form.technologies"
                      :key="index"
                      closable
                      @click:close="removeTechnology(index)"
                      color="secondary"
                      variant="tonal"
                    >
                      {{ tech }}
                    </v-chip>
                  </div>
                </v-col>
              </v-row>
            </div>

            <!-- Links -->
            <div class="mb-2">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Links</h2>
              
              <div class="mb-4">
                <v-row>
                  <v-col cols="12" md="5">
                    <v-text-field
                      v-model="newLinkTitle"
                      label="Link Title"
                      placeholder="e.g., GitHub Repository"
                      variant="outlined"
                      @keyup.enter="addLink"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="5">
                    <v-text-field
                      v-model="newLinkUrl"
                      label="URL"
                      placeholder="https://..."
                      variant="outlined"
                      @keyup.enter="addLink"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="2">
                    <v-btn
                      color="primary"
                      variant="tonal"
                      @click="addLink"
                      :disabled="!newLinkTitle.trim() || !newLinkUrl.trim()"
                      class="w-full"
                    >
                      <v-icon icon="mdi-plus" class="mr-1"></v-icon>
                      Add
                    </v-btn>
                  </v-col>
                </v-row>
              </div>
              
              <div v-if="form.links.length > 0" class="mb-4">
                <v-row dense>
                  <v-col
                    v-for="(link, index) in form.links"
                    :key="index"
                    cols="12"
                    md="6"
                    class="mb-2"
                  >
                    <v-card variant="outlined" class="px-3 py-0">
                      <div class="flex justify-between items-center">
                        <div class="flex-1 min-w-0">
                          <div class="text-sm text-gray-800 truncate">
                            <span class="font-medium">{{ link.title }}:</span>
                            <a
                              :href="link.url"
                              target="_blank"
                              class="text-blue-600 hover:underline break-all"
                            >
                              {{ link.url }}
                            </a>
                          </div>
                        </div>

                        <div class="flex items-center ml-2 shrink-0">
                          <v-btn
                            icon="mdi-open-in-new"
                            variant="text"
                            size="small"
                            color="primary"
                            :href="link.url"
                            target="_blank"
                            class="mr-1"
                          ></v-btn>
                          <v-btn
                            icon="mdi-delete"
                            variant="text"
                            size="small"
                            color="error"
                            @click="removeLink(index)"
                          ></v-btn>
                        </div>
                      </div>
                    </v-card>
                  </v-col>
                </v-row>
              </div>

              <div v-else class="text-center text-gray-500">
                <v-icon icon="mdi-link-variant" size="large" class="mb-2"></v-icon>
                <div>No links added yet</div>
                <div class="text-sm">Add links to your project resources</div>
              </div>
            </div>

            <!-- File Upload -->
            <div class="mb-2">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Assets</h2>
              
              <div class="mb-4">
                <v-file-input
                  v-model="newAssets"
                  label="Upload Files"
                  placeholder="Select files to upload"
                  variant="outlined"
                  multiple
                  prepend-icon="mdi-file-upload"
                  :error-messages="form.errors.assets"
                  accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
                  @update:model-value="addAssets"
                  clearable
                ></v-file-input>
              </div>
              
              <div v-if="form.assets.length > 0" class="flex flex-wrap gap-3 mb-8">
                <div
                  v-for="(file, index) in form.assets"
                  :key="index"
                  class="w-full md:w-[48%] lg:w-[32%]"
                >
                  <v-card variant="outlined" class="p-3 h-full">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1 min-w-0">
                        <v-icon
                          :icon="getFileIcon(file.type || file.name)"
                          :color="getFileColor(file.type || file.name)"
                          class="mr-3"
                          size="large"
                        ></v-icon>
                        <div class="flex-1 min-w-0">
                          <div class="text-sm font-medium text-gray-800 truncate">
                            {{ file.name }}
                          </div>
                          <div class="text-xs text-gray-500">
                            {{ formatFileSize(file.size) }} • {{ getFileType(file.type || file.name) }}
                          </div>
                        </div>
                      </div>

                      <div class="flex items-center ml-2 shrink-0">
                        <v-btn
                          icon="mdi-eye"
                          variant="text"
                          size="small"
                          color="primary"
                          @click="previewFile(file)"
                          class="mr-1"
                          v-if="isPreviewable(file.type || file.name)"
                        ></v-btn>
                        <v-btn
                          icon="mdi-delete"
                          variant="text"
                          size="small"
                          color="error"
                          @click="removeAsset(index)"
                        ></v-btn>
                      </div>
                    </div>
                  </v-card>
                </div>
              </div>

              
              <div v-else class="text-center text-gray-500 mb-8">
                <v-icon icon="mdi-file-multiple" size="large" class="mb-2"></v-icon>
                <div>No files uploaded yet</div>
                <div class="text-sm">Upload project assets and resources</div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 justify-end">
              <v-btn
                variant="outlined"
                size="large"
                @click="cancel"
                density="compact"
              >
                Cancel
              </v-btn>
              
              <v-btn
                type="submit"
                color="primary"
                density="compact"
                size="large"
                :loading="form.processing"
                :disabled="form.processing"
              >
                Create Project
              </v-btn>
            </div>
          </v-card>
        </v-form>
          </v-col>

          <!-- Right Section - Live Preview -->
          <v-col cols="12" lg="5">
            <v-card class="pa-6 h-fit sticky top-4">
              <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Live Preview</h2>
                <div class="text-sm text-gray-500 mb-4">Preview your project as you type</div>
              </div>

              <!-- Project Preview Card -->
              <ProjectCard 
                :project="form"
                :categories="categories"
                :statuses="statuses"
                :show-meta-info="false"
              />
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </AppLayout>
</template> 