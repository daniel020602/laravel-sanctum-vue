<template>
  <main>
    <div class="text-center">
        <h1 class="title">Felhasználói adatok</h1>
        <p><strong>Név:</strong> {{ authStore.user.name }}</p>
        <p><strong>Email:</strong> {{ authStore.user.email }}</p>
        <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
        <form @submit.prevent="handleUpdate" class="w-1/2 mx-auto space-y-6">
            <input class="" type="text" placeholder="cím" v-model="formData.address" />
            <input class="" type="text" placeholder="telefonszám" v-model="formData.phone" />
            <button type="submit" class="primary-btn">Frissítés</button>
        </form>
    </div>

  </main>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { reactive, ref } from 'vue'
const authStore = useAuthStore()
const { updateUserData } = authStore
const formData = reactive({
    address: authStore.user.address,
    phone: authStore.user.phone
})
const successMessage = ref('')
console.log(formData)
async function handleUpdate() {
  await updateUserData(formData);
  if (!authStore.errors || Object.keys(authStore.errors).length === 0) {
    successMessage.value = 'Sikeres frissítés!';
    setTimeout(() => successMessage.value = '', 3000); // Hide after 3s
  }
}
</script>