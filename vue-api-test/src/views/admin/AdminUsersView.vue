<template>
  <div class="container mx-auto p-4 bg-white rounded">
    <h1 class="text-2xl font-bold mb-4">Felhasználók kezelése</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <h2 class="font-semibold">Felhasználók</h2>
        <div v-if="adminUsers.isLoading">Betöltés...</div>
        <div v-else>
          <div v-if="users.length === 0" class="text-sm text-gray-500">Nincsenek felhasználók (vagy nincs jogosultság)</div>
          <ul v-else>
            <li v-for="u in users" :key="u.id" class="p-2 border-b hover:bg-gray-50">
              <button class="w-full text-left block" @click="selectUser(u)">{{ u.name }} ({{ u.email }})</button>
            </li>
          </ul>
        </div>
      </div>

      <div class="md:col-span-2">
        <div v-if="!selectedUser">Válassz egy felhasználót a szerkesztéshez</div>

        <div v-else>
          <h2 class="font-semibold">Szerkesztés: {{ selectedUser.name }}</h2>
          <form @submit.prevent="saveChanges" class="space-y-4">
            <div>
              <label class="block">Név</label>
              <input v-model="form.name" type="text" class="w-full" />
            </div>
            <div>
              <label class="block">Email</label>
              <input v-model="form.email" type="email" class="w-full" />
            </div>
            <div>
              <label class="block">Telefon</label>
              <input v-model="form.phone" type="text" class="w-full" />
            </div>
            <div>
              <label class="block">Cím</label>
              <input v-model="form.address" type="text" class="w-full" />
            </div>

            <div class="flex gap-2">
              <button class="primary-btn" type="submit">Mentés</button>
              <button class="secondary-btn" type="button" @click="toggleAdmin" v-if="selectedUser">{{ selectedUser.is_admin ? 'Demote' : 'Promote' }}</button>
              <button class="danger-btn" type="button" @click="removeUser">Törlés</button>
            </div>
          </form>

          <div v-if="message" class="mt-2 text-green-600">{{ message }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useAdminUsersStore } from '@/stores/adminUsers';

const adminUsers = useAdminUsersStore();
const users = computed(() => adminUsers.users);
const selectedUser = ref(null);
const form = reactive({ name: '', email: '', phone: '', address: '' });
const message = ref('');

async function fetchUsers() {
  try {
    const list = await adminUsers.fetchUsers();
    console.log('adminUsers.fetchUsers returned', list);
    // if a user is selected, refresh the selected user object reference
    if (selectedUser.value) {
      const refreshed = adminUsers.users.find(u => u.id === selectedUser.value.id);
      if (refreshed) selectedUser.value = refreshed;
    }
  } catch (e) {
    console.error('Failed to load users', e);
    message.value = 'Nem sikerült betölteni a felhasználókat: ' + (e.message || e);
  }
}

function selectUser(u) {
  selectedUser.value = u;
  form.name = u.name || '';
  form.email = u.email || '';
  form.phone = u.phone || '';
  form.address = u.address || '';
}

async function saveChanges() {
  try {
    const updated = await adminUsers.updateUser(selectedUser.value.id, form);
    message.value = 'Sikeres mentés';
    await fetchUsers();
    selectedUser.value = updated;
  } catch (e) {
    alert('Hiba: ' + e.message);
  }
}

async function toggleAdmin() {
  if (!selectedUser.value) return;
  try {
    const makeAdmin = !selectedUser.value.is_admin;
    await adminUsers.toggleAdmin(selectedUser.value.id, makeAdmin);
    message.value = makeAdmin ? 'Felhasználó admin lett' : 'Felhasználó visszaminősítve';
    await fetchUsers();
  } catch (e) {
    alert(e.message);
  }
}

async function removeUser() {
  if (!selectedUser.value) return;
  if (!confirm('Biztosan törlöd a felhasználót?')) return;
  try {
    await adminUsers.deleteUser(selectedUser.value.id);
    selectedUser.value = null;
    message.value = 'Felhasználó törölve';
  } catch (e) {
    alert(e.message);
  }
}

onMounted(async () => {
  await fetchUsers();
});
</script>
