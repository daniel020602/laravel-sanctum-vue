<script setup>
import { RouterLink, RouterView } from 'vue-router'
const authStore = useAuthStore()
import { useAuthStore } from '@/stores/auth'
</script>

<template>
  <header>
    
      <nav>
        <RouterLink :to="{name:'home'}" class="nav-link">Főoldal</RouterLink>
        <div v-if="!authStore.user">
          <RouterLink :to="{name: 'register'}" class="nav-link">Regisztráció</RouterLink>
          <RouterLink :to="{name: 'login'}" class="nav-link">Bejelentkezés</RouterLink>
        </div>
        <div v-if="authStore.user" class="flex items-center space-x-6">
          <RouterLink :to="{name: 'userdata'}">{{ authStore.user.name }}</RouterLink>
          <form @submit.prevent="authStore.logout">
            <button type="submit" class="nav-link">Kijelentkezés</button>
          </form>
        </div>
      </nav>

  </header>

  <RouterView />
  

</template>

