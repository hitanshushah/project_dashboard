<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { VDataTable } from 'vuetify/components';

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);
const users = computed(() => page.props.users as Array<{ name: string; email: string }>);

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Email', value: 'email' },
];
</script>


<template>
  <h1>Users</h1>

  <div v-if="currentUser">
    <p>Logged in as: <strong>{{ currentUser.name }}</strong> ({{ currentUser.username }})</p>
  </div>

  <v-data-table
    :headers="headers"
    :items="users"
    class="elevation-1"
    item-value="email"
  />
</template>
