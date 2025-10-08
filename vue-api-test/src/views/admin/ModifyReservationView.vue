<template>
    <div class="max-w-2xl mx-auto p-4">
        <h1 class="text-2xl font-semibold mb-4">Foglalás módosítása</h1>

        <div v-if="loading" class="p-4">Betöltés...</div>

        <div v-else>
            <form @submit.prevent="onSubmit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Név</label>
                    <input v-model="form.name" class="w-full border rounded p-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium">Email</label>
                    <input v-model="form.email" class="w-full border rounded p-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium">Telefon</label>
                    <input v-model="form.phone" class="w-full border rounded p-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Dátum</label>
                        <input type="date" v-model="form.date" class="w-full border rounded p-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Idő</label>
                        <select v-model="form.time" :disabled="!isTimeEnabled" class="w-full border rounded p-2">
                            <option disabled value="">Válasszon időpontot</option>
                            <option v-for="t in allowedTimes" :key="t" :value="t">{{ t }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Asztal</label>
                    <select v-model.number="form.table_id" class="w-full border rounded p-2">
                        <option :value="null">-- válassz --</option>
                        <option v-for="t in tables" :key="t.id" :value="t.id">{{ t.name || ('Asztal ' + t.id) }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="saving">Mentés</button>
                    <button type="button" class="bg-red-600 text-white px-4 py-2 rounded" @click="onDelete" :disabled="deleting">Törlés</button>
                    <button type="button" class="bg-gray-600 text-white px-4 py-2 rounded" @click="completeReservation">Foglalás teljesítése</button>
                    <router-link class="ml-auto text-sm text-gray-600" :to="{ name: 'admin' }">Vissza</router-link>
                </div>
            </form>

            <div v-if="message" class="mt-4 p-3 bg-green-100 text-green-800 rounded">{{ message }}</div>
            <div v-if="error" class="mt-4 p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted,computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useResAdminStore } from '@/stores/resAdmin';
import { useTableStore } from '@/stores/table';

const route = useRoute();
const router = useRouter();
const resAdmin = useResAdminStore();
const tableStore = useTableStore();

const id = route.params.id;
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const message = ref('');
const error = ref('');

const form = reactive({
    name: '',
    email: '',
    phone: '',
    date: '',
    time: '',
    table_id: null,
});

const tables = ref([]);

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

function normalizeTime(ts) {
    if (!ts) return '';
    const s = String(ts);
    const parts = s.split(':');
    if (parts.length >= 2) return parts[0].padStart(2,'0') + ':' + parts[1].padStart(2,'0');
    return s;
}

async function load() {
    loading.value = true;
    error.value = '';
    try {
        // fetch tables if not present
        try { tables.value = tableStore.items && tableStore.items.length ? tableStore.items : await tableStore.fetchTables(); } catch (e) { tables.value = tableStore.items || []; }
        const data = await resAdmin.fetchReservation(id);
        // controller returns reservation object
        const r = data && data.id ? data : (data.data ? data.data : data);
        form.name = r.name || '';
        form.email = r.email || '';
        form.phone = r.phone || '';
    form.date = r.date || '';
    form.time = normalizeTime(r.time || '');
        form.table_id = r.table_id ?? null;
    } catch (e) {
        console.error(e);
        error.value = e.message || 'Hiba a foglalás betöltésekor';
    } finally {
        loading.value = false;
    }
}

async function onSubmit() {
    saving.value = true;
    error.value = '';
    message.value = '';
        try {
        const payload = {
            name: form.name,
            email: form.email,
            phone: form.phone,
            date: form.date,
            time: normalizeTime(form.time),
            table_id: form.table_id,
        };
        const updated = await resAdmin.updateReservation(id, payload);
        message.value = 'Mentve';
    } catch (e) {
        console.error(e);
        error.value = e.message || 'Hiba a mentés során';
    } finally {
        saving.value = false;
    }
}

async function onDelete() {
    if (!confirm('Biztosan törlöd a foglalást?')) return;
    deleting.value = true;
    error.value = '';
    try {
        await resAdmin.deleteReservation(id);
        message.value = 'Törölve';
        // after delete, go back to admin main
        router.push({ name: 'admin' });
    } catch (e) {
        console.error(e);
        error.value = e.message || 'Hiba a törlés során';
    } finally {
        deleting.value = false;
    }
}
async function completeReservation() {
    if (!confirm('Biztosan teljesíted a foglalást? Ezzel a foglalás státusza "teljesített"-re változik.')) return;
    error.value = '';
    message.value = '';
    try {
        await resAdmin.completeReservation(id);
        message.value = 'Foglalás teljesítve.';
        router.push({ name: 'admin' });
    } catch (e) {
        console.error(e);
        error.value = e.message || 'Hiba a foglalás teljesítése során';
    }
}

onMounted(() => { load(); });

</script>