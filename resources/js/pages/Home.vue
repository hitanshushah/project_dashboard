<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Navbar from '@/components/Navbar.vue';

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);
const users = computed(() => page.props.users as Array<{ name: string; email: string }>);

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Email', value: 'email' },
];
</script>

<template>
  <!-- Add the navbar at the top -->
  <v-app>
    <Navbar />
    
    <!-- Main content with proper spacing from navbar -->
    <v-main>
      <v-container class="py-8">
        <h1 class="text-3xl font-bold mb-6">Users</h1>

        <div v-if="currentUser" class="mb-6">
          <v-alert type="info" variant="tonal" class="mb-4">
            Logged in as: <strong>{{ currentUser.name }}</strong> ({{ currentUser.username }})
          </v-alert>
        </div>

        <v-data-table
          :headers="headers"
          :items="users"
          class="elevation-1"
          item-value="email"
        />
      </v-container>
    </v-main>
  </v-app>
</template>