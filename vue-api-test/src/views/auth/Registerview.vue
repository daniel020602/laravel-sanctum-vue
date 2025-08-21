<script setup>
import {useAuthStore} from '@/stores/auth'
import {reactive, onMounted} from 'vue'
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
    onMounted(() => {
        errors.value = {}
    })
</script>

<template>
  <main>
    <h1 class="title">Regisztráció</h1>
    <form @submit.prevent="authenticate('register',formData)" class="w-1/2 mx-auto space-y-6">
        <div>
            <input type="text" placeholder="felhasználónév" v-model="formData.name"/>
            <p v-if="errors.name" class="error" >{{ errors.name[0] }}</p>
        </div>
        <div>
            <input type="email" name="email" placeholder="email@email.com"  v-model="formData.email"/>
            <p v-if="errors.email" class="error" >{{ errors.email[0] }}</p>
        </div>
        <div>
            <input type="password" placeholder="jelszó" v-model="formData.password"/>
            <p v-if="errors.password" class="error" >{{ errors.password[0] }}</p>
        </div>
        <div>
            <input type="password" placeholder="jelszó megerősítése" v-model="formData.password_confirmation"/>
            <p v-if="errors.password_confirmation" class="error" >{{ errors.password_confirmation[0] }}</p>
        </div>
        <button type="submit" class="primary-btn">Regisztráció</button>
    </form>
  </main>
</template>
