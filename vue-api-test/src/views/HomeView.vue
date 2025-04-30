<script setup>
  import { ref } from 'vue';
  import { usePostsStore } from '@/stores/posts'
  import { onMounted } from 'vue';
  import { RouterLink } from 'vue-router';
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
      <div v-if="posts.length" >
        <div v-for="post in posts" :key="post.id" class="border p-4 rounded">
          <h2 class="text-xl font-bold">{{ post.title }} - {{ post.user.name }}</h2>
            <RouterLink :to="{name:'post', params: { id: post.id }}" class="text-blue-500 hover:underline">
              <span class="text-sm">tovább</span> 
            </RouterLink>
        </div>

      </div>
      <div v-else>
        <p class="text-xl">nincs bejegyzés</p>
      </div>
      <h2>ez itt egy technikai próba</h2>
    </div>
  </main>
</template>
