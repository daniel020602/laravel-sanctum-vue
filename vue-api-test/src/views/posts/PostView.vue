<template>
    <main>
      <div v-if="Post" >
        <div class="border p-4 rounded">
          <h2 class="text-xl font-bold">{{ Post.post.title }}</h2>
          <p>{{ Post.post.body }}
          </p>
          <div v-if="Post.post.user.id === authStore.user.id" class="flex justify-between mt-4">
            <form @submit.prevent="Post.deletePost(postId)">
                <button>delete</button>
            </form>
          </div>
        </div>

      </div>
      <div v-else>
        <p class="text-xl">nincs bejegyzés</p>
      </div>
    </main>
</template>

<script setup>
import { onMounted } from 'vue';
import { usePostsStore } from '@/stores/posts';
import { useRoute } from 'vue-router';
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
const {getPost} = usePostsStore();
const authStore = useAuthStore()
const route = useRoute()
const postId = route.params.id;
const Post = ref(null);
onMounted(async() => {
    Post.value= await getPost(postId)
    console.log(authStore.user)
})
</script>