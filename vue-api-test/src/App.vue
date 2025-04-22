<script setup>
import { RouterLink, RouterView } from 'vue-router'
const authStore = useAuthStore()
import { useAuthStore } from '@/stores/auth'
</script>

<template>
  <header>
    
      <nav>
        <RouterLink :to="{name:'home'}" class="nav-link">Home</RouterLink>
        <div v-if="!authStore.user">
          <RouterLink :to="{name: 'register'}" class="nav-link">Register</RouterLink>
          <RouterLink :to="{name: 'login'}" class="nav-link">Login</RouterLink>
        </div>
        <div v-if="authStore.user" class="flex items-center space-x-6">
          <p  >{{ authStore.user.name }}</p>
          <RouterLink :to="{name: 'create'}" class="nav-link">New post</RouterLink>
          <form @submit.prevent="authStore.logout">
            <button type="submit" class="nav-link">logout</button>
          </form>
        </div>
      </nav>

  </header>

  <RouterView />
  

</template>

