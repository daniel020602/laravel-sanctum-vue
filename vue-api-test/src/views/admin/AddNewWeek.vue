<template>
    <div class="p-4">
        <h1 class="text-2xl mb-4">Új heti menü kiírása</h1>
        <h2 class="text-lg mb-4"><RouterLink :to="{ name: 'admin-weeks' }" class="primary-btn">vissza a hetekehez</RouterLink></h2>
        <form @submit.prevent="submitForm">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block">Év</label>
                    <input v-model.number="year" type="number" class="w-full" />
                </div>
                <div>
                    <label class="block">Hét száma</label>
                    <input v-model.number="weekNumber" type="number" class="w-full" />
                </div>
                <div>
                    <label class="block">Kezdő dátum</label>
                    <input v-model="startDate" type="date" class="w-full" />
                </div>
                <div>
                    <label class="block">Záró dátum</label>
                    <input v-model="endDate" type="date" class="w-full" />
                </div>
            </div>

            <div v-for="(day, idx) in days" :key="idx" class="mb-6 border p-3 bg-amber-50">
                <h2 class="font-semibold mb-2">{{ idx + 1 }}. nap </h2>

                <div class="grid grid-cols-4 gap-3 ">
                    <div>
                        <label class="block">Leves</label>
                        <select v-model.number="day.soup" class="w-full">
                            <option :value="null">-- válassz --</option>
                            <option v-for="m in soups" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block">A</label>
                        <select v-model.number="day.a" class="w-full">
                            <option :value="null">-- válassz --</option>
                            <option v-for="m in mains" :key="m.id + '-a'" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block">B</label>
                        <select v-model.number="day.b" class="w-full">
                            <option :value="null">-- válassz --</option>
                            <option v-for="m in mains" :key="m.id + '-b'" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block">C</label>
                        <select v-model.number="day.c" class="w-full">
                            <option :value="null">-- válassz --</option>
                            <option v-for="m in mains" :key="m.id + '-c'" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="primary-btn">Létrehozás</button>
                <button type="button" @click="resetForm" class="btn">Visszaállít</button>
            </div>
        </form>

        <div v-if="result" class="mt-4">
            <h3 v-if="result.status === 201" class="font-semibold">Sikeres létrehozás</h3>
            <div v-else-if="result.status === 422">
                <h3  class="font-semibold">Hiba történt</h3>
                <p>Hibák:</p>
                <ul v-if="result.body && result.body.errors">
                    <li v-for="(errors, field) in result.body.errors" :key="field">
                        {{ field }}: {{ errors.join(', ') }}
                    </li>
                </ul>
                <div v-else-if="result.body && result.body.message" class="text-sm text-red-600">
                    {{ result.body.message }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useWeeksStore } from '@/stores/weeks';
import { watch } from 'vue';

const year = ref(new Date().getFullYear());
const weekNumber = ref();
const startDate = ref('2025-01-01');
const endDate = ref('2025-01-05');

// days state: day1..day5 each with soup,a,b,c
const days = reactive([
    { soup: null, a: null, b: null, c: null },
    { soup: null, a: null, b: null, c: null },
    { soup: null, a: null, b: null, c: null },
    { soup: null, a: null, b: null, c: null },
    { soup: null, a: null, b: null, c: null },
]);

const menus = ref([]);
const soups = ref([]);
const mains = ref([]);
const result = ref(null);

// compute start (Monday) and end (Friday) for ISO week
function getDateOfISOWeek(week, year) {
    week = Number(week);
    year = Number(year);
    if (!Number.isFinite(week) || !Number.isFinite(year) || week < 1 || week > 53) {
        return null;
    }
    const simple = new Date(year, 0, 1 + (week - 1) * 7);
    if (isNaN(simple.getTime())) return null;
    const dow = simple.getDay();
    const ISOweekStart = new Date(simple);
    // adjust to Monday
    const dayDiff = (dow <= 1) ? (1 - dow) : (8 - dow);
    ISOweekStart.setDate(simple.getDate() + dayDiff);
    if (isNaN(ISOweekStart.getTime())) return null;
    return ISOweekStart;
}

// format a Date as local YYYY-MM-DD to avoid UTC timezone shifts from toISOString
function formatDateLocal(d) {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

watch([year, weekNumber], () => {
    const monday = getDateOfISOWeek(Number(weekNumber.value), Number(year.value));
    if (!monday) {
        startDate.value = '';
        endDate.value = '';
        return;
    }
    const friday = new Date(monday);
    friday.setDate(monday.getDate() + 4);
    startDate.value = formatDateLocal(monday);
    endDate.value = formatDateLocal(friday);
}, { immediate: true });

onMounted(async () => {
            try {
                // use the weeks store to fetch menus (will include credentials if needed)
                const store = useWeeksStore();
                await store.fetchMenus();
                menus.value = store.menus;
                soups.value = store.soups;
                mains.value = store.mains;
            } catch (e) {
                console.error(e);
            }
});

function buildPayload() {
    return {
        year: Number(year.value),
        week_number: Number(weekNumber.value),
        start_date: startDate.value,
        end_date: endDate.value,
        menus: {
            day1: { soup: days[0].soup, a: days[0].a, b: days[0].b, c: days[0].c },
            day2: { soup: days[1].soup, a: days[1].a, b: days[1].b, c: days[1].c },
            day3: { soup: days[2].soup, a: days[2].a, b: days[2].b, c: days[2].c },
            day4: { soup: days[3].soup, a: days[3].a, b: days[3].b, c: days[3].c },
            day5: { soup: days[4].soup, a: days[4].a, b: days[4].b, c: days[4].c },
        }
    };
}

async function submitForm() {
    const payload = buildPayload();
        try {
            // Ensure Sanctum CSRF cookie is present (SPA auth flow)
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

            // Use Pinia store action to create the week (includes credentials)
            const store = useWeeksStore();
            const res = await store.createWeek(payload);
            result.value = res;
        } catch (e) {
            result.value = { error: String(e) };
        }
}

function resetForm() {
    year.value = new Date().getFullYear();
    weekNumber.value =  null;// set to current week number
    startDate.value = null;
    endDate.value = null;
    for (const d of days) {
        d.soup = d.a = d.b = d.c = null;
    }
    result.value = null;
}
</script>