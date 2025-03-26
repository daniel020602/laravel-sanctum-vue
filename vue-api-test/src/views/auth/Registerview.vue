<script setup>
import {useAuthStore} from '@/stores/auth'
import {reactive} from 'vue'
    const authStore=useAuthStore()
    const formData = reactive({
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
    })
    
</script>

<template>
  <main>
    <h1 class="title">register a new account</h1>
    <form @submit.prevent="()=>{authStore.authenticate('register',formData)}" class="w-1/2 mx-auto space-y-6">
        <div>
            <input type="text" placeholder="username" v-model="formData.name"/>
        </div>
        <div>
            <input type="email" name="email" placeholder="email@email.com"  v-model="formData.email"/>
        </div>
        <div>
            <input type="password" placeholder="password" v-model="formData.password"/>
        </div>
        <div>
            <input type="password" placeholder="confirm password" v-model="formData.password_confirmation"/>
        </div>
        <button type="submit">register</button>
    </form>
  </main>
</template>
