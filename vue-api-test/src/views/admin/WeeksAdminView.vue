<template>
    
    <div class="m-auto flex flex-col items-center justify-center">
        <h1 class="text-2xl font-semibold mb-4">Heti menük</h1>

        <div class="flex items-center mb-4">
            <input id="showHistoric" type="checkbox" v-model="showHistoric" class="mr-2"/>
            <label for="showHistoric">korábbi hetek mutatása</label>
            <button v-if="!loading" @click="refresh" class="ml-4 px-2 py-1 bg-gray-200 rounded">Frissít</button>
        </div>

        <div v-if="loading" class="text-gray-500">Betöltés…</div>

        <div v-else>
            <div v-if="visibleWeeks.length === 0" class="text-gray-600">Nincs elérhető hét.</div>

            <ul v-else class="w-full max-w-xl">
                <li v-for="week in visibleWeeks" :key="week.id" class="p-3 border-b last:border-b-0">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium">Hét {{ week.week_number }} — {{ week.year }}</div>
                            <div class="text-sm text-gray-500">{{ formatDate(week.start_date) }} — {{ formatDate(week.end_date) }}</div>
                        </div>
                        <div class="text-sm text-gray-600">ID: {{ week.id }}</div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { useWeeksStore } from '@/stores/weeks';
import { onMounted, computed, ref } from 'vue';

const weeksStore = useWeeksStore();
const weeks = computed(() => weeksStore.items ?? []);
const showHistoric = ref(false);
const loading = ref(true);

const currentWeek = computed(() => {
    const now = new Date();
    const start = new Date(now.getFullYear(), 0, 1);
    const diff = (now - start + ((start.getTimezoneOffset() - now.getTimezoneOffset()) * 60000)) / 86400000;
    return Math.ceil((diff + start.getDay() + 1) / 7);
});

const visibleWeeks = computed(() => {
    // normalize structure, ensure array
    const list = Array.isArray(weeks.value) ? weeks.value.slice() : [];
    // sort by start_date ascending
    list.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

    if (showHistoric.value) return list;
    return list.filter(w => (w.week_number ?? w.week ?? 0) >= currentWeek.value);
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
        return new Date(dateStr).toLocaleDateString();
    } catch (e) {
        return dateStr;
    }
}

async function refresh() {
    loading.value = true;
    try {
        await weeksStore.fetchWeeks();
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await refresh();
});
</script>