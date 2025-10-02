<template>
    <div>
        <h1 v-if="allReservations.length">foglalások</h1>
        <form @submit.prevent="createReservation">
            <input type="text" v-model="newReservation.name" placeholder="Név">
            <input type="date" v-model="newReservation.date">
                <select v-model="newReservation.time" :disabled="!isTimeEnabled">
                    <option disabled value="">Válasszon időpontot</option>
                    <option v-for="t in allowedTimes" :key="t" :value="t">{{ t }}</option>
                </select>
            <input type="text" v-model="newReservation.phone" placeholder="Telefonszám">
            <input type="email" v-model="newReservation.email" placeholder="Email">
            <select v-model="newReservation.table_id" :disabled="!isTableEnabled">
                <option disabled value="">Válasszon asztalt</option>
                <template v-if="availableTables.length > 0">
                    <option v-for="table in availableTables" :key="table.id" :value="table.id">
                        Asztal {{ table.id }} ({{ table.capacity }} fő)
                    </option>
                </template>
                <option v-else disabled value="">Nincs elérhető asztal erre az időpontra</option>
            </select>
            <button type="submit">Foglalás</button>
        </form>
        <!-- success modal -->
        <div v-if="showSuccess" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white p-6 rounded shadow-lg w-96 text-center">
                <h2 class="text-xl font-bold mb-2">Sikeres foglalás!</h2>
                <p class="mb-2">Foglalás ID: <strong>{{ createdReservation?.id }}</strong></p>
                <p class="mb-4">Küldünk egy megerősítő kódot az email címre.</p>
                <button @click="() => {}" class="px-4 py-2 bg-blue-600 text-white rounded">Rendben</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useReservationStore } from '@/stores/reservations';
import { useTableStore } from '@/stores/table';
import { useAuthStore } from '@/stores/auth';
import { onMounted, reactive, computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

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

const router = useRouter();
const showSuccess = ref(false);
const createdReservation = ref(null);

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



// UI enabling flags
const isTimeEnabled = computed(() => !!newReservation.date);
const isTableEnabled = computed(() => !!newReservation.time && !!newReservation.date);

// cached reservations (all active) from store — normalize into an array
const allReservations = computed(() => {
    const items = reservationStore.items;
    if (Array.isArray(items)) return items;
    if (!items) return [];
    if (Array.isArray(items.data)) return items.data;
    if (Array.isArray(items.reservations)) return items.reservations;
    if (typeof items === 'object') return Object.values(items);
    return [];
});

// fetch reservations that match currently selected date/time when both selected
async function fetchReservationsForSelection() {
    if (!newReservation.date || !newReservation.time) return;
    // The store has a fetchReservations() that fetches all active reservations.
    // For now, ensure store items are fresh and then filter client-side.
    await reservationStore.fetchReservations();
}

// compute available tables by excluding those that already have a reservation at the same date/time
const availableTables = computed(() => {
    // if date or time not set, return all tables
    if (!newReservation.date || !newReservation.time) return tables.value;

    // normalize time string to HH:MM (drop seconds)
    const normalizeTime = (ts) => {
        if (!ts) return '';
        // ts might be '10:30:00' or '10:30'
        const parts = String(ts).split(':');
        if (parts.length >= 2) return parts[0].padStart(2,'0') + ':' + parts[1].padStart(2,'0');
        return String(ts);
    };

    const selTime = normalizeTime(newReservation.time);
    const selDate = String(newReservation.date);

    // helper to extract table id from a reservation object in many possible shapes
    const extractTableId = (r) => {
        if (!r) return null;
        if (r.table_id !== undefined) return r.table_id;
        if (r.tableId !== undefined) return r.tableId;
        if (r.table && r.table.id !== undefined) return r.table.id;
        if (r.table && r.table.table_id !== undefined) return r.table.table_id;
        // fallback: if reservation has an id and a mapping in another field
        if (r.reservation && r.reservation.table_id !== undefined) return r.reservation.table_id;
        return null;
    };

    const conflictingIds = new Set();

    for (const r of allReservations.value) {
        try {
            const rDate = r.date ? String(r.date) : '';
            // try multiple possible time fields
            const rTimeCandidate = r.time ?? r.start_time ?? r.time_from ?? r.reservation_time ?? null;
            const rTime = normalizeTime(rTimeCandidate);

            if (rDate === selDate && rTime === selTime) {
                const tid = extractTableId(r);
                if (tid !== null && tid !== undefined) conflictingIds.add(Number(tid));
            }
        } catch (e) {
            // ignore malformed entries
            continue;
        }
    }

    // Return tables that are not in conflictingIds
    return tables.value.filter(t => !conflictingIds.has(Number(t.id)));
});

// watchers to reset dependent selects and fetch reservations when selections change
watch(() => newReservation.date, (newDate, oldDate) => {
    // Reset time and table when date changes
    newReservation.time = '';
    newReservation.table_id = '';
});

watch(() => newReservation.time, async (newTime, oldTime) => {
    // Reset table when time changes
    newReservation.table_id = '';
    if (newTime) {
        await fetchReservationsForSelection();
    }
});

const createReservation = async () => {
    const result = await reservationStore.createReservation(newReservation);
    if (result && result.id) {
        // Push to store items and reset form
        reservationStore.items.push(result);
        newReservation.name = '';
        newReservation.date = '';
        newReservation.time = '';
        newReservation.table_id = '';
        newReservation.phone = '';
        newReservation.email = '';
        createdReservation.value = result;
        showSuccess.value = true;
        // auto-redirect after short delay and pass reservation object via state
        setTimeout(() => {
            router.push({ name: 'confirm-reservation', query: { id: result.id }, state: { reservation: result } });
        }, 1400);
    } else if (result && result.errors) {
        console.error('Reservation validation errors', result.errors);
    } else {
        console.error('Failed to create reservation', result);
    }
};

</script>
