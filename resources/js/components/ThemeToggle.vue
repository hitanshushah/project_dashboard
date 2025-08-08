<template>
  <div class="flex items-center">
    <div class="cursor-pointer select-none" @click="toggleTheme">
      <div 
        class="relative w-11 h-6 bg-gray-300 rounded-full transition-all duration-300 ease-in-out flex items-center p-0.5"
        :class="{ 'bg-gray-600': isDark }"
      >
        <div 
          class="absolute left-0.5 w-5 h-5 rounded-full transition-all duration-300 ease-in-out flex items-center justify-center z-10"
          :class="isDark ? 'left-[calc(100%-1.375rem)] bg-gray-500' : 'bg-orange-500'"
        >
          <img 
            v-if="!isDark" 
            :src="sunIcon" 
            alt="Sun" 
            class="w-3 h-3 brightness-0 invert" 
          />
          <img 
            v-else 
            :src="moonIcon" 
            alt="Moon" 
            class="w-3 h-3 brightness-0 invert" 
          />
        </div>
        <div 
          class="absolute right-1.5 flex items-center justify-center z-0"
          :class="{ 'right-auto left-1.5': isDark }"
        >
          <img 
            v-if="!isDark" 
            :src="moonIcon" 
            alt="Moon" 
            class="w-2.5 h-2.5 brightness-0 saturate-100 invert-[45%] sepia-0 saturate-0 hue-rotate-0 brightness-90 contrast-90" 
          />
          <img 
            v-else 
            :src="sunIcon" 
            alt="Sun" 
            class="w-2.5 h-2.5 brightness-0 saturate-100 invert-[65%] sepia-0 saturate-0 hue-rotate-0 brightness-90 contrast-90" 
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import sunIcon from '@/assets/icons/sun.svg';
import moonIcon from '@/assets/icons/moon.svg';

const { isDark, updateAppearance } = useAppearance();

const toggleTheme = () => {
  updateAppearance(isDark.value ? 'light' : 'dark');
};
</script> 