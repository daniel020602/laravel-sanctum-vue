<template>
  <main>
    <div class="text-center">
        <h1 class="title">Felhasználói adatok</h1>
        <p><strong>Név:</strong> {{ user.name }}</p>
        <p><strong>Email:</strong> {{ user.email }}</p>
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
import { reactive, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
const authStore = useAuthStore()
const route = useRoute()
const successMessage = ref('')

const user = ref(authStore.user || {})

const formData = reactive({
    address: user.value.address || '',
    phone: user.value.phone || ''
})

async function loadUserIfAdmin() {
  const id = route.params.id;
  if (id && authStore.user && authStore.user.is_admin) {
    const res = await fetch(`/api/auth/show-user/${id}`, { headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') } });
    if (res.ok) {
      const data = await res.json();
      user.value = data.user;
      formData.address = user.value.address || '';
      formData.phone = user.value.phone || '';
    }
  } else {
    user.value = authStore.user || {};
    formData.address = user.value.address || '';
    formData.phone = user.value.phone || '';
  }
}

onMounted(async () => {
  await authStore.getUser();
  await loadUserIfAdmin();
});

async function handleUpdate() {
  // If admin editing another user, call admin change-data endpoint
  const id = route.params.id;
  try {
    if (id && authStore.user && authStore.user.is_admin) {
      const res = await fetch(`/api/auth/change-data/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        body: JSON.stringify(formData)
      });
      if (!res.ok) throw new Error('Failed to update');
      const data = await res.json();
      successMessage.value = 'Sikeres frissítés!';
      user.value = data.user;
    } else {
      await authStore.updateUserData(formData);
      if (!authStore.errors || Object.keys(authStore.errors).length === 0) {
        successMessage.value = 'Sikeres frissítés!';
      }
    }
    setTimeout(() => successMessage.value = '', 3000);
  } catch (e) {
    alert('Hiba: ' + (e.message || e));
  }
}
</script>