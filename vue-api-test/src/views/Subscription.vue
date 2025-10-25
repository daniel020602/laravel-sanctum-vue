<template>
    <div v-if="week && week.id" class="container mx-auto p-4 self-center bg-amber-50 rounded-lg mt-4">
        <h1 class="text-2xl font-bold text-center p-4 m-4 items-center">Előfizetés heti menüre</h1>
        <h1  class="text-l font-semibold text-center p-2 m-2">{{ week.week_number }}. hét menü</h1>
        <RouterLink :to="{ name: 'pay-for-subscription', params: { id: week.id } }" class="primary-btn text-l m-auto mb-4">Előfizetés erre a hétre</RouterLink>
        <div v-for="(dayMapping, idx) in weekMappings" :key="idx" class="mb-6 border p-3 bg-white">
            <h2 class="font-semibold mb-2">{{ idx + 1 }}. nap </h2>
            <div class="grid grid-cols-4 gap-1">
                <div>
                    <label class="block font-semibold">Leves</label>
                    <div v-if="dayMapping.soup">
                        {{ menus.find(m => m.id === dayMapping.soup).name }}
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">A</label>
                    <div v-if="dayMapping.a">
                        {{ menus.find(m => m.id === dayMapping.a).name }}
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">B</label>
                    <div v-if="dayMapping.b">
                        {{ menus.find(m => m.id === dayMapping.b).name }}
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">C</label>
                    <div v-if="dayMapping.c">
                        {{ menus.find(m => m.id === dayMapping.c).name }}
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</template>


<script setup>
import { onMounted, ref } from 'vue';
import { useWeeksStore } from '@/stores/weeks';

const weeksStore = useWeeksStore();
const menus = ref([]);
const week = ref(null);
const weekMappings = ref(null);
onMounted(async () => {
    try {
        // fetch the next week into the store (fetchNextWeek sets weeksStore.week)
        await weeksStore.fetchNextWeek();
        // fetch menus into the store, then read them
        await weeksStore.fetchMenus();
        menus.value = weeksStore.menus;

    week.value = weeksStore.week;

    if (!week.value || !week.value.id) {
        console.warn('No next week available or week id missing');
        return;
    }

    // fetch the week record (with its week_menus) so we have the flat menu rows
    const weekData = await weeksStore.fetchWeek(week.value.id);
    const flat = weekData.menus ?? weekData.week_menus ?? [];
    const days = [
        { soup: null, a: null, b: null, c: null },
        { soup: null, a: null, b: null, c: null },
        { soup: null, a: null, b: null, c: null },
        { soup: null, a: null, b: null, c: null },
        { soup: null, a: null, b: null, c: null },
    ];

    for (const row of flat) {
        const dayIndex = (Number(row.day_of_week) || 1) - 1;
        if (dayIndex < 0 || dayIndex > 4) continue;
        const opt = String(row.option || '').toLowerCase();
        if (['soup', 'a', 'b', 'c'].includes(opt)) {
            days[dayIndex][opt] = row.menu_id !== undefined ? Number(row.menu_id) : null;
        }
    }

    weekMappings.value = days;

    console.log('Fetched menus (catalog):', menus.value);
    console.log('Fetched week:', week.value);
    console.log('Fetched week mappings:', weekMappings.value);
    } catch (err) {
        console.error('Error fetching data:', err);
    }
});
</script>
