<script setup lang="ts">
// import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import Navbar from '@/components/Navbar.vue';
import { useAppearance } from '@/composables/useAppearance';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { isDark } = useAppearance();
</script>

<template>
    <v-app :theme="isDark ? 'dark' : 'light'">
        <Navbar />
        <slot />
    </v-app>
</template>
