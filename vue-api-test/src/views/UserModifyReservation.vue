<template>
    <div class="max-w-lg mx-auto p-4">
        <h1 class="text-2xl font-semibold mb-4">Foglalás módosítása</h1>

        <div v-if="!hasReservation">
            <p class="text-red-600">Nem érkezett foglalás adat. Kérjük, használja a foglalás keresését és indítsa a módosítást onnan.</p>
        </div>

        <div v-else>
            <form @submit.prevent="onSubmit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Dátum</label>
                    <input type="date" v-model="form.date" class="w-full border rounded p-2" required />
                </div>

                <div>
                    <label class="block text-sm font-medium">Idő</label>
                    <select v-model="form.time" :disabled="!isTimeEnabled" class="w-full border rounded p-2" required>
                        <option disabled value="">Válasszon időpontot</option>
                        <option v-for="t in allowedTimes" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Asztal</label>
                    <select v-model.number="form.table_id" class="w-full border rounded p-2" required>
                        <option :value="null">-- válassz --</option>
                        <option v-for="t in tables" :key="t.id" :value="t.id">{{ t.name || ('Asztal ' + t.id) }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="saving">Mentés</button>
                    <button type="button" class="ml-auto text-sm text-white bg-red-500 px-3 py-2 rounded" @click="onDelete" :disabled="deleting">Törlés</button>
                    <router-link class="ml-2 text-sm text-gray-600" :to="{ name: 'home' }">Vissza</router-link>
                </div>
            </form>

            <div v-if="message" class="mt-4 p-3 bg-green-100 text-green-800 rounded">{{ message }}</div>
            <div v-if="error" class="mt-4 p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useReservationStore } from '@/stores/reservations';
import { useRoute } from 'vue-router';
import { useTableStore } from '@/stores/table';

// receive prop from router (props function in router config)
const props = defineProps({ reservationData: { type: Object, default: null } });
const reservationStore = useReservationStore();
const route = useRoute();

// Prefer the explicit prop; fallback to history.state if necessary
const stateReservation = (typeof window !== 'undefined' && window.history && window.history.state && window.history.state.reservation)
    ? window.history.state.reservation
    : null;

// fallback: try to read from localStorage with TTL handling
function readLocalReservation(id) {
        if (!id || typeof window === 'undefined' || !window.localStorage) return null;
        try {
                const raw = localStorage.getItem(`reservation_${id}`);
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                // expected shape: { data: <reservation>, savedAt: <ms>, ttlMinutes: <number> }
                if (!parsed || !parsed.data) return null;
                const savedAt = parsed.savedAt || 0;
                const ttlMinutes = parsed.ttlMinutes || 0;
                const expired = Date.now() > (savedAt + ttlMinutes * 60 * 1000);
                if (expired) {
                        try { localStorage.removeItem(`reservation_${id}`); } catch (e) { /* ignore */ }
                        return null;
                }
                return parsed.data;
        } catch (e) {
                console.warn('Failed to read reservation from localStorage', e);
                return null;
        }
}

const localStoredReservation = stateReservation ? null : readLocalReservation(route.params.id);

// form state and helpers
const tableStore = useTableStore();
const tables = ref([]);
const form = reactive({ date: '', time: '', table_id: null });
const saving = ref(false);
const deleting = ref(false);
const message = ref('');
const error = ref('');

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
const isTimeEnabled = computed(() => !!form.date);

function fillFormFromReservation(r) {
    form.date = r.date || '';
    // normalize time to HH:MM (strip seconds if present)
    form.time = (function normalizeTime(ts) {
        if (!ts) return '';
        const s = String(ts);
        const parts = s.split(':');
        if (parts.length >= 2) return parts[0].padStart(2,'0') + ':' + parts[1].padStart(2,'0');
        return s;
    })(r.time);
    form.table_id = r.table_id ?? r.table?.id ?? null;
}

async function onSubmit() {
    saving.value = true;
    error.value = '';
    message.value = '';
    try {
        const payload = { date: form.date, time: form.time, table_id: form.table_id };
        const reservationObj = reservation.value;
        const code = reservationObj?.reservation_code || reservationObj?.code || '';
        if (!code) throw new Error('Nincs foglalási kód elérhető a módosításhoz.');
        await reservationStore.updateReservation(route.params.id, payload, code);
        message.value = 'Foglalás sikeresen frissítve.';
        // update localStorage cache
        try {
            const key = `reservation_${route.params.id}`;
            const stored = localStorage.getItem(key);
            if (stored) {
                    const parsed = JSON.parse(stored);
                    // ensure time stored matches HH:MM
                    const normalized = { ...parsed.data, ...payload };
                    if (normalized.time) {
                        const p = String(normalized.time).split(':');
                        if (p.length >= 2) normalized.time = p[0].padStart(2,'0') + ':' + p[1].padStart(2,'0');
                    }
                    parsed.data = normalized;
                    localStorage.setItem(key, JSON.stringify(parsed));
                }
        } catch (e) { /* ignore */ }
    } catch (e) {
        console.error(e);
        error.value = e.message || 'Hiba a mentés során';
    } finally {
        saving.value = false;
    }
}

async function onDelete() {
    if (!confirm('Biztosan törli a foglalást?')) return;
    deleting.value = true;
    error.value = '';
    try {
        const reservationObj = reservation.value;
        const code = reservationObj?.reservation_code || reservationObj?.code || '';
        if (!code) throw new Error('Nincs foglalási kód elérhető a törléshez.');
        await reservationStore.deleteReservation(route.params.id, code);
        // remove localStorage cache
        try { localStorage.removeItem(`reservation_${route.params.id}`); } catch (e) { /* ignore */ }
        message.value = 'Foglalás törölve.';
    } catch (e) {
        console.error(e);
        error.value = e.message || 'Hiba a törlés során';
    } finally {
        deleting.value = false;
    }
}

const reservation = computed(() => {
    if (props.reservationData) return props.reservationData;
    if (stateReservation) return stateReservation;
    if (localStoredReservation) return localStoredReservation;
    return null;
});

const hasReservation = computed(() => !!(reservation.value && reservation.value.id && String(reservation.value.id) === String(route.params.id)));

const reservationPreview = computed(() => reservation.value ? JSON.stringify(reservation.value, null, 2) : 'Nincs adat');

onMounted(() => {
    console.log('Received reservationData prop:', props.reservationData);
    console.log('history.state.reservation:', stateReservation);
    // prepare tables and fill form if reservation available
    (async () => {
        try { tables.value = tableStore.items && tableStore.items.length ? tableStore.items : await tableStore.fetchTables(); } catch (e) { tables.value = tableStore.items || []; }
        if (reservation.value) fillFormFromReservation(reservation.value);
    })();
});
</script>