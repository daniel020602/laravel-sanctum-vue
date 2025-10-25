<template>
  <div class="container mx-auto p-4">
    <h1 class="title">Heti menü kiválasztása</h1>

    <div v-if="isLoading">Betöltés...</div>
    <div v-else-if="!week">Nincs elérhető következő hét.</div>

    <form v-else @submit.prevent="saveChoices">
      <div class="grid grid-cols-1 gap-4">
        <div v-for="day in days" :key="day.day" class="p-4 border rounded">
          <h2 class="font-bold">{{ dayLabels[day.day] }} ({{ day.date }})</h2>
          <div class="mt-2">
            <label class="block text-sm">Válassz egy ételt a napra</label>
            <select v-model.number="selected[day.day]" class="w-full">
              <option :value="null">— Válassz —</option>
              <option v-for="wm in allOptionsByDay(day.day)" :key="wm.id" :value="wm.id">{{ wm.option.toUpperCase() }} - {{ wm.menu.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="mt-4 flex gap-2">
          <button class="primary-btn" type="submit">Mentés</button>
          <button class="secondary-btn" type="button" @click="onDelete" v-if="selected.subscriptionId">Törlés</button>
        </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useWeeksStore } from '@/stores/weeks';
import { useSubscriptionStore } from '@/stores/subscription';
import { useRouter } from 'vue-router';

const weeksStore = useWeeksStore();
const subscriptionStore = useSubscriptionStore();
const router = useRouter();

const isLoading = ref(true);
const week = ref(null);
const days = ref([]);
const optionsByDay = reactive({});
const selected = reactive({});

const dayLabels = { 1: 'Hétfő', 2: 'Kedd', 3: 'Szerda', 4: 'Csütörtök', 5: 'Péntek' };

function getDateOfISOWeek(weekNum, year, dayNumber = 1) {
  // Use Jan 4th to find the Monday of ISO week 1, then add (weekNum-1)*7 and day offset
  const w = Number(weekNum);
  const y = Number(year);
  if (!Number.isFinite(w) || !Number.isFinite(y) || w < 1 || w > 53) return '';
  const jan4 = new Date(y, 0, 4);
  let jan4Dow = jan4.getDay();
  if (jan4Dow === 0) jan4Dow = 7;
  const week1Monday = new Date(jan4);
  week1Monday.setDate(jan4.getDate() - (jan4Dow - 1));
  const target = new Date(week1Monday);
  target.setDate(week1Monday.getDate() + (w - 1) * 7 + (dayNumber - 1));
  return target.toISOString().slice(0,10);
}

onMounted(async () => {
  try {
    await weeksStore.fetchMenus();

    // fetch the canonical next week
    await weeksStore.fetchNextWeek();
    week.value = weeksStore.week;
    if (!week.value) return;

    // fetch week record with its week_menus
    const weekData = await weeksStore.fetchWeek(week.value.id);
    const wms = weekData.menus ?? weekData.week_menus ?? [];

    const wnum = Number(week.value.week_number);
    const wyr = Number(week.value.year);
    days.value = [1,2,3,4,5].map(d => ({ day: d, date: getDateOfISOWeek(wnum, wyr, d) }));

    // init
    for (let d=1; d<=5; d++) { optionsByDay[d] = { a: [], b: [], c: [] }; selected[d] = null; }

    const menuCatalog = (weeksStore.menus || []).reduce((acc, m) => { acc[m.id] = m; return acc; }, {});
    wms.forEach(wm => {
      const day = Number(wm.day_of_week ?? wm.day_of_week);
      const opt = (wm.option || 'a').toLowerCase();
      const entry = { id: wm.id, menu_id: wm.menu_id, option: opt };
      entry.menu = menuCatalog[entry.menu_id] ?? { name: 'N/A' };
      if (optionsByDay[day] && optionsByDay[day][opt]) optionsByDay[day][opt].push(entry);
    });

    // load the user's subscription and their choices for next week — do not create
    const userWeek = await subscriptionStore.fetchUserWeek();
    if (userWeek && userWeek.subscription) {
      selected.subscriptionId = userWeek.subscription.id;
      (userWeek.choices || []).forEach(c => { selected[Number(c.day)] = c.week_menu_id; });
    }
  } catch (e) {
    console.error(e);
  } finally { isLoading.value = false; }
});

function allOptionsByDay(day) {
  const opts = [];
  ['a','b','c'].forEach(o => { if (optionsByDay[day] && Array.isArray(optionsByDay[day][o])) optionsByDay[day][o].forEach(x => opts.push(x)); });
  return opts;
}

async function saveChoices() {
  // build payload
  const choices = [];
  for (let d=1; d<=5; d++) { if (selected[d]) choices.push({ day: d, week_menu_id: selected[d] }); }

  try {
    const userWeek = await subscriptionStore.fetchUserWeek();
    if (!userWeek || !userWeek.subscription) {
      alert('Nincs előfizetés a következő hétre. A változtatás csak meglévő előfizetésen frissíthető.');
      return;
    }
    const subId = userWeek.subscription.id;
    await subscriptionStore.updateSubscription(subId, week.value.id, choices);
    alert('Mentve');
  } catch (e) {
    console.error(e);
    alert('Hiba történt a mentés közben: ' + (e.message || String(e)));
  }
}

async function onDelete() {
  try {
    const userWeek = await subscriptionStore.fetchUserWeek();
    if (!userWeek || !userWeek.subscription) {
      alert('Nincs előfizetés a következő hétre.');
      return;
    }
    const subId = userWeek.subscription.id;
    if (!confirm('Biztosan törlöd az előfizetést a következő hétre?')) return;
    await subscriptionStore.deleteSubscription(subId);
    // navigate to homepage
    router.push('/');
  } catch (e) {
    console.error(e);
    alert('Hiba történt a törlés közben: ' + (e.message || String(e)));
  }
}
</script>
