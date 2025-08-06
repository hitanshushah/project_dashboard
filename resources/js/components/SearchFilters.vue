<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

// Props
interface Props {
  categories: Array<{ name: string; key: string }>;
  statuses: Array<{ name: string; key: string }>;
  technologies: string[];
  currentFilters: {
    search: string;
    categories: string[];
    statuses: string[];
    technologies: string[];
    sort_by: string;
    sort_direction: string;
  };
  resultsCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
  resultsCount: 0
});

// Emits
const emit = defineEmits<{
  filtersChanged: [filters: {
    search: string;
    categories: string[];
    statuses: string[];
    technologies: string[];
    sort_by: string;
    sort_direction: string;
  }];
}>();

// Local state
const searchQuery = ref(props.currentFilters.search || '');
const selectedCategories = ref([...props.currentFilters.categories] || []);
const selectedStatuses = ref([...props.currentFilters.statuses] || []);
const selectedTechnologies = ref([...props.currentFilters.technologies] || []);
const sortBy = ref(props.currentFilters.sort_by || 'created_at');
const sortDirection = ref(props.currentFilters.sort_direction || 'desc');

// UI state
const showFilters = ref(false);

// Sort options for dropdown
const sortOptions = [
  { text: 'Newest First', value: 'created_at', direction: 'desc' },
  { text: 'Oldest First', value: 'created_at', direction: 'asc' },
  { text: 'Recently Updated', value: 'updated_at', direction: 'desc' },
  { text: 'Least Recently Updated', value: 'updated_at', direction: 'asc' },
  { text: 'Name A-Z', value: 'name', direction: 'asc' },
  { text: 'Name Z-A', value: 'name', direction: 'desc' },
];

const selectedSortOption = computed({
  get: () => {
    return sortOptions.find(opt => 
      opt.value === sortBy.value && opt.direction === sortDirection.value
    )?.text || 'Newest First';
  },
  set: (value: string) => {
    const option = sortOptions.find(opt => opt.text === value);
    if (option) {
      sortBy.value = option.value;
      sortDirection.value = option.direction;
      applyFilters();
    }
  }
});

const hasActiveFilters = computed(() => {
  return searchQuery.value ||
         selectedCategories.value.length > 0 ||
         selectedStatuses.value.length > 0 ||
         selectedTechnologies.value.length > 0 ||
         sortBy.value !== 'created_at' ||
         sortDirection.value !== 'desc';
});

// Filter functionality
const applyFilters = () => {
  const params = new URLSearchParams();
  
  if (searchQuery.value) {
    params.append('search', searchQuery.value);
  }
  
  selectedCategories.value.forEach(cat => {
    params.append('categories[]', cat);
  });
  
  selectedStatuses.value.forEach(status => {
    params.append('statuses[]', status);
  });
  
  selectedTechnologies.value.forEach(tech => {
    params.append('technologies[]', tech);
  });
  
  if (sortBy.value !== 'created_at') {
    params.append('sort_by', sortBy.value);
  }
  
  if (sortDirection.value !== 'desc') {
    params.append('sort_direction', sortDirection.value);
  }
  
  const queryString = params.toString();
  const url = queryString ? `/?${queryString}` : '/';
  
  router.visit(url, {
    preserveScroll: true,
    preserveState: true
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  selectedCategories.value = [];
  selectedStatuses.value = [];
  selectedTechnologies.value = [];
  sortBy.value = 'created_at';
  sortDirection.value = 'desc';
  router.visit('/', {
    preserveScroll: true,
    preserveState: true
  });
};

// Watch for changes and apply filters
watch([searchQuery], () => {
  // Debounced search
  if (searchQuery.value !== props.currentFilters.search) {
    setTimeout(() => {
      if (searchQuery.value !== props.currentFilters.search) {
        applyFilters();
      }
    }, 300);
  }
});

watch([selectedCategories, selectedStatuses, selectedTechnologies], () => {
  applyFilters();
}, { deep: true });

// Expose methods and computed properties for parent component
defineExpose({
  clearFilters,
  hasActiveFilters: computed(() => hasActiveFilters.value)
});
</script>

<template>
  <div class="search-filters">
    <!-- Search Bar -->
    <div class="d-flex gap-3 mb-4">
      <v-text-field
        v-model="searchQuery"
        prepend-inner-icon="mdi-magnify"
        placeholder="Search projects by name, description, or key..."
        variant="outlined"
        density="compact"
        hide-details
        clearable
        class="flex-grow-1"
      ></v-text-field>
      
      <v-btn
        :color="showFilters ? 'primary' : 'default'"
        :variant="showFilters ? 'flat' : 'outlined'"
        prepend-icon="mdi-filter"
        @click="showFilters = !showFilters"
      >
        Filters
        <v-badge
          v-if="hasActiveFilters"
          color="error"
          content="!"
          inline
        ></v-badge>
      </v-btn>

      <v-select
        v-model="selectedSortOption"
        :items="sortOptions.map(opt => opt.text)"
        prepend-inner-icon="mdi-sort"
        variant="outlined"
        density="compact"
        hide-details
        style="min-width: 200px;"
        placeholder="Sort by..."
      ></v-select>
    </div>

    <!-- Expanded Filters -->
    <v-expand-transition>
      <v-card v-if="showFilters" class="pa-4 mb-4" variant="outlined">
        <div class="d-flex justify-space-between align-center mb-3">
          <h3 class="text-h6">Filters</h3>
          <v-btn
            v-if="hasActiveFilters"
            variant="text"
            color="error"
            size="small"
            @click="clearFilters"
          >
            Clear All
          </v-btn>
        </div>
        
        <v-row>
          <!-- Category Filter -->
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedCategories"
              :items="categories"
              item-title="name"
              item-value="key"
              label="Categories"
              multiple
              chips
              variant="outlined"
              density="compact"
              hide-details
            >
              <template v-slot:selection="{ item, index }">
                <v-chip
                  v-if="index < 2"
                  size="small"
                  closable
                  @click:close="selectedCategories.splice(selectedCategories.indexOf(item.value), 1)"
                >
                  {{ item.title }}
                </v-chip>
                <span v-if="index === 2" class="text-grey text-caption align-self-center">
                  (+{{ selectedCategories.length - 2 }} others)
                </span>
              </template>
            </v-select>
          </v-col>

          <!-- Status Filter -->
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedStatuses"
              :items="statuses"
              item-title="name"
              item-value="key"
              label="Status"
              multiple
              chips
              variant="outlined"
              density="compact"
              hide-details
            >
              <template v-slot:selection="{ item, index }">
                <v-chip
                  v-if="index < 2"
                  size="small"
                  closable
                  @click:close="selectedStatuses.splice(selectedStatuses.indexOf(item.value), 1)"
                >
                  {{ item.title }}
                </v-chip>
                <span v-if="index === 2" class="text-grey text-caption align-self-center">
                  (+{{ selectedStatuses.length - 2 }} others)
                </span>
              </template>
            </v-select>
          </v-col>

          <!-- Technology Filter -->
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedTechnologies"
              :items="technologies"
              label="Technologies"
              multiple
              chips
              variant="outlined"
              density="compact"
              hide-details
            >
              <template v-slot:selection="{ item, index }">
                <v-chip
                  v-if="index < 2"
                  size="small"
                  closable
                  @click:close="selectedTechnologies.splice(selectedTechnologies.indexOf(item.value), 1)"
                >
                  {{ item.value }}
                </v-chip>
                <span v-if="index === 2" class="text-grey text-caption align-self-center">
                  (+{{ selectedTechnologies.length - 2 }} others)
                </span>
              </template>
            </v-select>
          </v-col>
        </v-row>
      </v-card>
    </v-expand-transition>

    <!-- Active Filters Summary -->
    <div v-if="hasActiveFilters && !showFilters" class="mb-4">
      <div class="d-flex flex-wrap gap-2 align-center">
        <span class="text-body-2 text-grey-darken-1">Active filters:</span>
        
        <v-chip
          v-if="searchQuery"
          size="small"
          closable
          @click:close="searchQuery = ''"
        >
          Search: "{{ searchQuery }}"
        </v-chip>
        
        <v-chip
          v-for="category in selectedCategories"
          :key="`cat-${category}`"
          size="small"
          closable
          @click:close="selectedCategories.splice(selectedCategories.indexOf(category), 1)"
        >
          {{ categories.find(c => c.key === category)?.name }}
        </v-chip>
        
        <v-chip
          v-for="status in selectedStatuses"
          :key="`stat-${status}`"
          size="small"
          closable
          @click:close="selectedStatuses.splice(selectedStatuses.indexOf(status), 1)"
        >
          {{ statuses.find(s => s.key === status)?.name }}
        </v-chip>
        
        <v-chip
          v-for="tech in selectedTechnologies"
          :key="`tech-${tech}`"
          size="small"
          closable
          @click:close="selectedTechnologies.splice(selectedTechnologies.indexOf(tech), 1)"
        >
          {{ tech }}
        </v-chip>
        
        <v-chip
          v-if="sortBy !== 'created_at' || sortDirection !== 'desc'"
          size="small"
          closable
          @click:close="sortBy = 'created_at'; sortDirection = 'desc'; applyFilters()"
        >
          Sort: {{ selectedSortOption }}
        </v-chip>
      </div>
    </div>

    <!-- Results Summary -->
    <div v-if="resultsCount !== undefined" class="d-flex justify-space-between align-center mb-4">
      <div class="text-body-1 text-grey-darken-1">
        <span v-if="hasActiveFilters">
          Found {{ resultsCount }} project{{ resultsCount !== 1 ? 's' : '' }}
        </span>
        <span v-else>
          {{ resultsCount }} project{{ resultsCount !== 1 ? 's' : '' }} total
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.search-filters {
  width: 100%;
}
</style>