<script setup>
import { useAuthStore } from '@/stores/auth'
import { onMounted } from 'vue';
const authStore = useAuthStore();
onMounted(() => {

  authStore.getUser();
});
</script>

<template>
  <main>
    <div class="text-center">
      <h1 class="title">óheló</h1>
      <h2>ez itt egy technikai próba</h2>
      <p v-if="authStore.user">be vagy jelentkezve!</p>
    </div>
  </main>
</template>
