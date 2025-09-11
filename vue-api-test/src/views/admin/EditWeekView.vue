<template>
  <div class="p-4">
    <h1 class="text-2xl mb-4">Hét szerkesztése</h1>
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
        <button type="submit" class="primary-btn">Mentés</button>
        <button type="button" @click="resetForm" class="btn">Visszaállít</button>
      </div>
    </form>

    <div v-if="result" class="mt-4">
      <h3 v-if="result.status === 200" class="font-semibold">Sikeres mentés</h3>
      <div v-else-if="result.status === 422">
        <h3 class="font-semibold">Hiba történt</h3>
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
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const id = route.params.id;

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

onMounted(async () => {
  try {
    const store = useWeeksStore();
    await store.fetchMenus();
    menus.value = store.menus;
    soups.value = store.soups;
    mains.value = store.mains;

    // fetch existing week data
    const data = await store.fetchWeek(id);
    if (data.week) {
      year.value = data.week.year;
      weekNumber.value = data.week.week_number;
      startDate.value = data.week.start_date;
      endDate.value = data.week.end_date;

      // populate days from returned week_menus (expecting array)
      const wm = data.week_menus ?? data.menus ?? [];

      // clear existing day selections first
      for (let i = 0; i < 5; i++) {
        days[i].soup = days[i].a = days[i].b = days[i].c = null;
      }

      // assign directly; normalize option to lowercase and coerce menu_id to Number
      for (const item of wm) {
        const idx = Number(item.day_of_week) - 1;
        if (idx < 0 || idx > 4) continue;
        const opt = String(item.option ?? '').toLowerCase();
        const menuId = item.menu_id !== undefined ? Number(item.menu_id) : null;
        if (!menuId) continue;
        if (['soup','a','b','c'].includes(opt)) {
          days[idx][opt] = menuId;
        }
      }

      // Quietly ensure any referenced menu ids are present in the target select lists
      const ensureInList = (menuId, optionType) => {
        const idNum = Number(menuId);
        if (!idNum) return;
        const targetList = optionType === 'soup' ? soups.value : mains.value;
        if (targetList.find(m => Number(m.id) === idNum)) return;
        const full = menus.value.find(m => Number(m.id) === idNum);
        if (full) {
          // reuse the existing object (shallow copy) so the select can show it
          targetList.push(Object.assign({}, full));
          return;
        }
        // create a minimal placeholder so the select renders the current id
        targetList.push({ id: idNum, name: `#${idNum}`, type: optionType === 'soup' ? 'soup' : 'main' });
      };

      for (const item of wm) {
        const menuId = item.menu_id !== undefined ? Number(item.menu_id) : null;
        if (!menuId) continue;
        const opt = String(item.option ?? '').toLowerCase();
        if (opt === 'soup') ensureInList(menuId, 'soup'); else ensureInList(menuId, 'main');
      }
    }
  } catch (e) {
    // keep console error for unexpected runtime errors
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
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
    const store = useWeeksStore();
    const res = await store.updateWeek(id, payload);
    result.value = res;
    if (res.status === 200) {
      // navigate back to weeks list after save
      router.push({ name: 'admin-weeks' });
    }
  } catch (e) {
    result.value = { error: String(e) };
  }
}

function resetForm() {
  // reload the week data
  window.location.reload();
}
</script>

<style scoped>
.btn { padding: .5rem 1rem; border: 1px solid #ccc; background: #fff; cursor: pointer }
.btn-primary { background: #2563eb; color: #fff; border-color: #1e40af }
</style>