<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);

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
  status: 'planning',
  priority: 'medium',
  budget: '',
  team_members: [] as string[],
  client: '',
  technologies: [] as string[],
  notes: ''
});

// Available options
const categories = [
  'Web Development',
  'Mobile Development',
  'Design',
  'Marketing',
  'Research',
  'Other'
];

const statusOptions = [
  { value: 'planning', label: 'Planning' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'review', label: 'Under Review' },
  { value: 'completed', label: 'Completed' },
  { value: 'on_hold', label: 'On Hold' }
];

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' }
];

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

const addTechnology = () => {
  if (newTechnology.value.trim() && !form.technologies.includes(newTechnology.value.trim())) {
    form.technologies.push(newTechnology.value.trim());
    newTechnology.value = '';
  }
};

const removeTechnology = (index: number) => {
  form.technologies.splice(index, 1);
};

const addTeamMember = () => {
  if (newTeamMember.value.trim() && !form.team_members.includes(newTeamMember.value.trim())) {
    form.team_members.push(newTeamMember.value.trim());
    newTeamMember.value = '';
  }
};

const removeTeamMember = (index: number) => {
  form.team_members.splice(index, 1);
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
</script>

<template>
  <AppLayout>
    <v-main>
      <v-container class="py-8 max-w-4xl">
        <div class="mb-8">
          <v-btn
            icon="mdi-arrow-left"
            variant="text"
            @click="cancel"
            class="mb-4"
          ></v-btn>
          <h1 class="text-3xl font-bold text-gray-800">Create New Project</h1>
          <p class="text-gray-600 mt-2">Fill in the details below to create your new project</p>
        </div>

        <v-form @submit.prevent="submit">
          <v-card class="pa-6">
            <!-- Basic Information -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Basic Information</h2>
              
              <v-row>
                <v-col cols="12" md="8">
                  <v-text-field
                    v-model="form.name"
                    label="Project Name"
                    placeholder="Enter project name"
                    variant="outlined"
                    :error-messages="form.errors.name"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.category"
                    :items="categories"
                    label="Category"
                    placeholder="Select category"
                    variant="outlined"
                    :error-messages="form.errors.category"
                  ></v-select>
                </v-col>
              </v-row>

              <v-textarea
                v-model="form.description"
                label="Description"
                placeholder="Describe your project..."
                variant="outlined"
                rows="4"
                :error-messages="form.errors.description"
              ></v-textarea>
            </div>

            <!-- Project Details -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Project Details</h2>
              
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.start_date"
                    label="Start Date"
                    type="date"
                    variant="outlined"
                    :error-messages="form.errors.start_date"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.end_date"
                    label="End Date"
                    type="date"
                    variant="outlined"
                    :error-messages="form.errors.end_date"
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-row>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.status"
                    :items="statusOptions"
                    item-title="label"
                    item-value="value"
                    label="Status"
                    variant="outlined"
                    :error-messages="form.errors.status"
                  ></v-select>
                </v-col>
                
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.priority"
                    :items="priorityOptions"
                    item-title="label"
                    item-value="value"
                    label="Priority"
                    variant="outlined"
                    :error-messages="form.errors.priority"
                  ></v-select>
                </v-col>
                
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="form.budget"
                    label="Budget"
                    placeholder="e.g., $10,000"
                    variant="outlined"
                    :error-messages="form.errors.budget"
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-text-field
                v-model="form.client"
                label="Client"
                placeholder="Client name or company"
                variant="outlined"
                :error-messages="form.errors.client"
              ></v-text-field>
            </div>

            <!-- Tags -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Tags</h2>
              
              <div class="mb-4">
                <v-text-field
                  v-model="newTag"
                  label="Add Tag"
                  placeholder="Enter a tag"
                  variant="outlined"
                  @keyup.enter="addTag"
                  append-inner-icon="mdi-plus"
                  @click:append-inner="addTag"
                ></v-text-field>
              </div>
              
              <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
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
            </div>

            <!-- Technologies -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Technologies</h2>
              
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
                ></v-autocomplete>
              </div>
              
              <div v-if="form.technologies.length > 0" class="flex flex-wrap gap-2">
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
            </div>

            <!-- Team Members -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Team Members</h2>
              
              <div class="mb-4">
                <v-text-field
                  v-model="newTeamMember"
                  label="Add Team Member"
                  placeholder="Enter email or name"
                  variant="outlined"
                  @keyup.enter="addTeamMember"
                  append-inner-icon="mdi-plus"
                  @click:append-inner="addTeamMember"
                ></v-text-field>
              </div>
              
              <div v-if="form.team_members.length > 0" class="flex flex-wrap gap-2">
                <v-chip
                  v-for="(member, index) in form.team_members"
                  :key="index"
                  closable
                  @click:close="removeTeamMember(index)"
                  color="success"
                  variant="tonal"
                >
                  {{ member }}
                </v-chip>
              </div>
            </div>

            <!-- Links -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Links</h2>
              
              <v-row class="mb-4">
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="newLinkTitle"
                    label="Link Title"
                    placeholder="e.g., GitHub Repository"
                    variant="outlined"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="newLinkUrl"
                    label="URL"
                    placeholder="https://..."
                    variant="outlined"
                    @keyup.enter="addLink"
                    append-inner-icon="mdi-plus"
                    @click:append-inner="addLink"
                  ></v-text-field>
                </v-col>
              </v-row>
              
              <div v-if="form.links.length > 0" class="space-y-2">
                <v-card
                  v-for="(link, index) in form.links"
                  :key="index"
                  variant="outlined"
                  class="pa-3"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">{{ link.title }}</div>
                      <div class="text-sm text-gray-600">{{ link.url }}</div>
                    </div>
                    <v-btn
                      icon="mdi-delete"
                      variant="text"
                      color="error"
                      @click="removeLink(index)"
                    ></v-btn>
                  </div>
                </v-card>
              </div>
            </div>

            <!-- File Upload -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Assets</h2>
              
              <v-file-input
                v-model="form.assets"
                label="Upload Files"
                placeholder="Select files to upload"
                variant="outlined"
                multiple
                prepend-icon="mdi-file-upload"
                :error-messages="form.errors.assets"
                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
              ></v-file-input>
            </div>

            <!-- Notes -->
            <div class="mb-8">
              <h2 class="text-xl font-semibold mb-4 text-gray-700">Additional Notes</h2>
              
              <v-textarea
                v-model="form.notes"
                label="Notes"
                placeholder="Any additional notes or requirements..."
                variant="outlined"
                rows="3"
                :error-messages="form.errors.notes"
              ></v-textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 justify-end">
              <v-btn
                variant="outlined"
                size="large"
                @click="cancel"
              >
                Cancel
              </v-btn>
              
              <v-btn
                type="submit"
                color="primary"
                size="large"
                :loading="form.processing"
                :disabled="form.processing"
              >
                Create Project
              </v-btn>
            </div>
          </v-card>
        </v-form>
      </v-container>
    </v-main>
  </AppLayout>
</template> 