<template>
    <div>
        <h1 v-if="reservationStore.items.length">foglalások</h1>
        <form @submit.prevent="createReservation">
            <input type="text" v-model="newReservation.name" placeholder="Név">
            <input type="date" v-model="newReservation.date">
            <select v-model="newReservation.time">
                <option disabled value="">Válasszon időpontot</option>
                <option v-for="t in allowedTimes" :key="t" :value="t">{{ t }}</option>
            </select>
            <input type="text" v-model="newReservation.phone" placeholder="Telefonszám">
            <input type="email" v-model="newReservation.email" placeholder="Email">
            <select v-model="newReservation.table_id">
                <option disabled value="">Válasszon asztalt</option>
                <option v-for="table in tables" :key="table.id" :value="table.id">
                    Asztal {{ table.id }} ({{ table.capacity }} fő)
                </option>
            </select>
            <button type="submit">Foglalás</button>
        </form>
    </div>
</template>

<script setup>
import { useReservationStore } from '@/stores/reservations';
import { useTableStore } from '@/stores/table';
import { useAuthStore } from '@/stores/auth';
import { onMounted, reactive, computed, ref } from 'vue';

const reservationStore = useReservationStore();
const tableStore = useTableStore();
const authStore = useAuthStore();
const user = ref(authStore.user || null);
// reactive view over store tables
const tables = computed(() => tableStore.items || []);
const newReservation = reactive({
    name: '',
    date: '',
    time: '',
    table_id: '',
    phone: '',
    email: ''

});

// generate allowed times between min and max with a step (seconds)
function buildTimes(min = '10:30', max = '21:00', stepSeconds = 5400) {
    const toSec = s => {
        const [h, m] = s.split(':').map(Number);
        return h * 3600 + m * 60;
    };
    const toHHMM = sec => {
        const hh = String(Math.floor(sec / 3600)).padStart(2,'0');
        const mm = String(Math.floor((sec % 3600) / 60)).padStart(2,'0');
        return `${hh}:${mm}`;
    };
    const start = toSec(min);
    const end = toSec(max);
    const arr = [];
    for (let t = start; t <= end; t += stepSeconds) arr.push(toHHMM(t));
    return arr;
}

const allowedTimes = buildTimes();
onMounted(async () => {
    try {
        // fetch tables and reservations when component mounts
        await tableStore.fetchTables();
        await reservationStore.fetchReservations();
    } catch (e) {
        console.error('Failed to load reservations or tables', e);
    }
    if (user.value) {
        newReservation.name = user.value.name || '';
        newReservation.email = user.value.email || '';
        newReservation.phone = user.value.phone || '';
    }
});



const createReservation = async () => {
    // TODO: implement create via reservationStore
    const result = await reservationStore.createReservation(newReservation);
};

</script>
