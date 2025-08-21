<script setup>
  import { ref } from 'vue';
  import { usePostsStore } from '@/stores/posts'
  import { onMounted } from 'vue';
  import { RouterLink } from 'vue-router';
  import Introduction from './Introduction.vue';
  const { getAllPosts } = usePostsStore()
  const posts= ref([])
  onMounted(async () => {
    posts.value = await getAllPosts()
  });
</script>

<template>
  <main>
    <div class="text-center">
      <h1 class="title">óheló</h1>
      <h2>ez itt egy technikai próba</h2>
      <Introduction />
    </div>
  </main>
</template>
