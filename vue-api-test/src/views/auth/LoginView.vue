<script setup>
import {useAuthStore} from '@/stores/auth'
import {onMounted, reactive} from 'vue'
import {storeToRefs} from 'pinia'
    const authStore= useAuthStore()
    const {authenticate} = useAuthStore()
    const formData = reactive({
        email: '',
        password: '',
    })
    const {errors}= storeToRefs(authStore)
    onMounted(() => {
        errors.value = {}
    })
</script>

<template>
  <main>
    <h1 class="title">Log in to your account</h1>
    <form @submit.prevent="authenticate('login',formData)" class="w-1/2 mx-auto space-y-6">
        <div>
            <input type="email" name="email" placeholder="email@email.com"  v-model="formData.email"/>
            <p v-if="errors.email" class="error" >{{ errors.email[0] }}</p>
        </div>
        <div>
            <input type="password" placeholder="password" v-model="formData.password"/>
            <p v-if="errors.password" class="error" >{{ errors.password[0] }}</p>
        </div>
        <button type="submit" class="primary-btn">login</button>
    </form>
  </main>
</template>
