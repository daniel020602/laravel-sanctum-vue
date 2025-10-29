<template>
    <div>
        <h1>Foglalás keresése</h1>
        <p>Kérjük, adja meg a foglalás részleteit:</p>
        <form @submit.prevent="searchReservation">
            <input type="number" v-model="searchId" placeholder="Foglalás azonosító" />
            <input type="text" v-model="reservationCode" placeholder="Foglalás kód" />
            <button type="submit">Keresés</button>
        </form>
    <div v-if="reservation">
            <h2>Foglalás részletei:</h2>
            <p>ID: {{ reservation.id }}</p>
            <p>Név: {{ reservation.name }}</p>
            <p>Dátum: {{ reservation.date }}</p>
            <p>Idő: {{ reservation.time }}</p>
            <p>Hány fő: {{ reservation.table.capacity }}</p>
            <p>Telefonszám: {{ reservation.phone }}</p>
            <p>Email: {{ reservation.email }}</p>
            <p>Állapot: <span v-if="reservation.is_confirmed">Megerősítve</span><span v-else>Nincs megerősítve</span></p>
            <RouterLink :to="{ name: 'modify-user-reservation' }">Foglalás módosítása</RouterLink>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useReservationStore } from '@/stores/reservations';
import { RouterLink } from 'vue-router';

const searchId = ref('');
const reservationCode = ref('');
const reservation = ref(null);
const reservationStore = useReservationStore();

const searchReservation = () => {
    reservationStore.fetchReservationById(searchId.value, reservationCode.value)
        .then(data => {
            // normalize response shapes (data, data.reservation, data.data)
            const r = data && data.id ? data : (data.reservation ? data.reservation : (data.data ? data.data : data));
            reservation.value = r;
            // persist to localStorage with a TTL so modify page can pick it up if navigation state is lost
            try {
                if (r && r.id) {
                    // pick a TTL between 5 and 10 minutes
                    const ttlMinutes = 5 + Math.floor(Math.random() * 6); // 5..10
                    const payload = { data: r, savedAt: Date.now(), ttlMinutes };
                    localStorage.setItem(`reservation_${r.id}`, JSON.stringify(payload));
                }
            } catch (e) {
                console.warn('Failed to store reservation in localStorage', e);
            }
            console.log('Foglalás megtalálva:', r);
        })
        .catch(err => {
            console.error('Hiba a foglalás keresésekor:', err);
            reservation.value = null;
        });
};

</script>