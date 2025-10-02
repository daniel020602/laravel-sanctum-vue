<template>
  <div class="max-w-xl mx-auto p-6 bg-white rounded shadow mt-8">
    <h1 class="text-2xl font-bold mb-4">Foglalás megerősítése</h1>

    <div v-if="message" :class="messageClass" class="p-3 rounded mb-4">{{ message }}</div>

    <form @submit.prevent="confirmReservation" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Foglalás ID</label>
        <input v-model="reservationId" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Pl. 123" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Megerősítő kód</label>
        <input v-model="code" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Kód a levélből" />
      </div>

      <div class="flex items-center justify-between">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Megerősítés</button>
        <button type="button" @click="clear" class="px-3 py-2 border rounded">Törlés</button>
      </div>
    </form>

    <div v-if="reservation" class="mt-6 p-4 border rounded bg-gray-50">
      <h2 class="font-semibold mb-2">Foglalás részletei</h2>
      <p><strong>ID:</strong> {{ reservation.id }}</p>
      <p><strong>Dátum:</strong> {{ reservation.date }}</p>
      <p><strong>Idő:</strong> {{ reservation.time }}</p>
      <p><strong>Asztal:</strong> {{ reservation.table_id }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useReservationStore } from '@/stores/reservations'

const reservationId = ref('')
const code = ref('')
const reservation = ref(null)
const message = ref('')
const messageClass = computed(() => message.value.includes('siker') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')

const route = useRoute()
const router = useRouter()
const reservationStore = useReservationStore()

onMounted(() => {
  // If navigated from new-reservation, the reservation object may be in history.state
  const navState = window.history.state && window.history.state.back ? window.history.state : window.history.state || {}
  if (navState && navState.reservation) {
    reservation.value = navState.reservation
    reservationId.value = navState.reservation.id
  } else {
    // If id passed as query param, prefill
    const id = route.query.id
    if (id) reservationId.value = id
  }
})

const confirmReservation = async () => {
  message.value = ''
  reservation.value = null
  if (!reservationId.value || !code.value) {
    message.value = 'Kérem adja meg a foglalás ID-t és a kódot.'
    return
  }
  try {
    const data = await reservationStore.confirmReservation(reservationId.value, code.value)
    message.value = data.message || 'Sikeres megerősítés.'
    reservation.value = data.reservation || data
  } catch (e) {
    console.error(e)
    message.value = e.message || 'Hiba a megerősítés során.'
  }
}

const clear = () => {
  reservationId.value = ''
  code.value = ''
  reservation.value = null
  message.value = ''
}
</script>
