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
const selectedCategories = ref([...props.currentFilters.categories]);
const selectedStatuses = ref([...props.currentFilters.statuses]);
const selectedTechnologies = ref([...props.currentFilters.technologies]);
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
    <v-text-field
      v-model="searchQuery"
      prepend-inner-icon="mdi-magnify"
      placeholder="Search projects..."
      variant="outlined"
      density="compact"
      clearable
      class="mb-2 w-96"
    />

    <!-- Filters + Sort Row -->
    <div class="d-flex align-center gap-3 mb-2">
      <v-btn
        color="blue"
        :variant="showFilters ? 'tonal' : 'outlined'"
        class="px-3"
        @click="showFilters = !showFilters"
        title="Toggle filters"
      >
        <v-icon left>mdi-filter-variant</v-icon> Filters
        <v-badge
          v-if="hasActiveFilters"
          color="success"
          dot
          class="ml-2 mb-5"
        />
      </v-btn>

      <div>
      <v-select
        v-model="selectedSortOption"
        :items="sortOptions.map(opt => opt.text)"
        prepend-inner-icon="mdi-sort"
        variant="outlined"
        density="compact"
        hide-details
        placeholder="Sort by"
      />
      </div>
    </div>

    <!-- Compact Filters Panel -->
    <v-expand-transition>
      <v-row
        v-if="showFilters"
        class="mb-4"
      >
        <v-col cols="12" sm="4" md="3" lg="2">
          <v-select
            v-model="selectedCategories"
            :items="categories"
            item-title="name"
            item-value="key"
            label="Categories"
            multiple
            chips
            closable-chips
            density="compact"
            hide-details
            clearable
            :menu-props="{ maxHeight: '220px' }"
          />
        </v-col>

        <v-col cols="12" sm="4" md="3" lg="2">
          <v-select
            v-model="selectedStatuses"
            :items="statuses"
            item-title="name"
            item-value="key"
            label="Status"
            multiple
            chips
            closable-chips
            density="compact"
            hide-details
            clearable
            :menu-props="{ maxHeight: '220px' }"
          />
        </v-col>

        <v-col cols="12" sm="4" md="3" lg="2">
          <v-select
            v-model="selectedTechnologies"
            :items="technologies"
            label="Technologies"
            multiple
            chips
            closable-chips
            density="compact"
            hide-details
            clearable
            :menu-props="{ maxHeight: '220px' }"
          />
        </v-col>

        <v-col cols="12" sm="12" md="3" lg="3" class="d-flex align-center">
          <v-btn
            v-if="hasActiveFilters"
            color="error"
            variant="text"
            density="compact"
            @click="clearFilters"
          >
            Clear All
          </v-btn>
        </v-col>
      </v-row>
    </v-expand-transition>

  </div>
</template>

<style scoped>
.search-filters {
  width: 100%;
}
</style>