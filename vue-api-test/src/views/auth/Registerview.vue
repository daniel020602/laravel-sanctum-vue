<script setup>
import {useAuthStore} from '@/stores/auth'
import {reactive} from 'vue'
import {storeToRefs} from 'pinia'
    const authStore= useAuthStore()
    const {authenticate} = useAuthStore()
    const formData = reactive({
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
    })
    const {errors}= storeToRefs(authStore)
    
</script>

<template>
  <main>
    <h1 class="title">register a new account</h1>
    <form @submit.prevent="authenticate('register',formData)" class="w-1/2 mx-auto space-y-6">
        <div>
            <input type="text" placeholder="username" v-model="formData.name"/>
            <p v-if="errors.name" class="error" >{{ errors.name[0] }}</p>
        </div>
        <div>
            <input type="email" name="email" placeholder="email@email.com"  v-model="formData.email"/>
            <p v-if="errors.email" class="error" >{{ errors.email[0] }}</p>
        </div>
        <div>
            <input type="password" placeholder="password" v-model="formData.password"/>
            <p v-if="errors.password" class="error" >{{ errors.password[0] }}</p>
        </div>
        <div>
            <input type="password" placeholder="confirm password" v-model="formData.password_confirmation"/>
            <p v-if="errors.password_confirmation" class="error" >{{ errors.password_confirmation[0] }}</p>
        </div>
        <button type="submit" class="primary-btn">register</button>
    </form>
  </main>
</template>
