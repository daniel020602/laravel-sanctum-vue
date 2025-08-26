<template>
    
    <div class="m-auto flex flex-col items-center justify-center">
        <h1>Heti menük</h1>
        <div class="flex items-center mb-4">
            <input type="checkbox" v-model="showHistoric" @change="handleChange" class="mr-2"/>
            <label for="showHistoric">korábbi hetek mutatása</label>
        </div>
        <div v-for="week in selectedWeek" :key="week.id" class="week-list">
            {{ week.week }}
        </div>
    </div>
</template>

<script setup>
import { useWeeksStore } from '@/stores/weeks';
import { onMounted, computed, ref } from 'vue';

const weeksStore = useWeeksStore();
const weeks = computed(() => weeksStore.items);
const showHistoric = ref(false);

const currentWeek = computed(() => {
    const now = new Date();
    const start = new Date(now.getFullYear(), 0, 1);
    const diff = (now - start + ((start.getTimezoneOffset() - now.getTimezoneOffset()) * 60000)) / 86400000;
    return Math.ceil((diff + start.getDay() + 1) / 7);
});

const selectedWeek = computed(() => {
    if (showHistoric.value) {
        return weeks.value;
    } else {
        return weeks.value.filter(w => w.week >= currentWeek.value);
    }
});

onMounted(() => {
    weeksStore.fetchWeeks();
});
</script>