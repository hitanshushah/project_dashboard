<template>
  <v-dialog v-model="modelValue" max-width="600">
    <v-card :class="[isDarkMode ? 'bg-black' : 'bg-white']">
      <v-card-title class="text-lg font-semibold">
        <v-icon icon="mdi-cog" class="mr-2"></v-icon>
        Customize Project Preview
      </v-card-title>

      <v-card-text>
        <p class="text-sm text-gray-600 mb-4">
          Choose which elements to display in the project preview. You can keep some details disabled for metadata and project filtering purposes.
        </p>

        <div class="!space-y-0">
          <template v-for="(label, key) in toggleOptions" :key="key">
            <div class="flex items-center justify-between h-[60px]">
              <div>
                <div class="font-medium">{{ label }}</div>
                <div class="text-sm text-gray-500">Show {{ label.toLowerCase() }} in preview</div>
              </div>
              <v-switch v-model="settings[key]" color="primary" />
            </div>
          </template>
        </div>
      </v-card-text>

      <v-card-actions class="pa-6">
        <v-spacer></v-spacer>
        <v-btn variant="outlined" @click="modelValue = false">
          Close
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { computed } from 'vue';

const props = defineProps<{
  modelValue: boolean;
  settings: Record<string, boolean>;
}>();

const emit = defineEmits(['update:modelValue']);

const modelValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});

const { isDark } = useAppearance();
const isDarkMode = computed(() => isDark.value);

const settings = props.settings;

const toggleOptions: Record<string, string> = {
  showDescription: 'Project Description',
  showCategory: 'Category',
  showStatus: 'Status',
  showDates: 'Dates',
  showTags: 'Tags',
  showTechnologies: 'Technologies',
  showLinks: 'Links',
  showAssets: 'Assets',
};
</script>
