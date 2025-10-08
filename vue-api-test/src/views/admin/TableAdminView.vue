<template>
    <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Asztalok kezelése</h1>
        <div v-if="tables.length">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b border-gray-200">Asztal ID</th>
                        <th class="py-2 px-4 border-b border-gray-200">Ülőhelyek Száma</th>
                        <th class="py-2 px-4 border-b border-gray-200">Foglalások</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="table in tables" :key="table.id">
                        <td class="py-2 px-4 border-b border-gray-200">{{ table.id }}</td>
                        <td class="py-2 px-4 border-b border-gray-200">{{ table.capacity }}</td>
                        <td class="py-2 px-4 border-b border-gray-200">
                            <div v-if="(reservationsByTable[table.id] || []).length">
                                <div class="text-sm text-gray-700 font-medium">{{ (reservationsByTable[table.id] || []).length }} db</div>
                                <ul class="text-sm mt-1">
                                    <li v-for="res in reservationsByTable[table.id]" :key="res.id" class="py-1">
                                        <RouterLink :to="{ name: 'modify-reservation', params: { id: res.id } }" class="block">
                                            <span class="font-semibold">{{ res.name }}</span>
                                            <span class="text-gray-500"> — {{ res.date }} {{ res.time }}</span>
                                            <span v-if="res.is_confirmed" class="ml-2 text-green-600">(megerősítve)</span>
                                            <span v-else class="ml-2 text-red-600">(nem megerősítve)</span>
                                        </RouterLink>
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="text-gray-500 text-sm">Nincs foglalás</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="text-gray-500">
            Nincsenek elérhető asztalok.
        </div>
        <form action="" @submit.prevent="createTable" class="mt-4">
            <input type="number" v-model.number="newTableCapacity" placeholder="Ülőhelyek száma" class="border border-gray-300 rounded py-1 px-2 mr-2" min="1" required />
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Új asztal létrehozása
            </button>
        </form>
        <div class="mt-4">
            <p class="text-gray-700">Megerősítetlen foglalások száma: <span class="font-bold">{{ unconfirmedCount }}</span></p>
            <button @click="fetchUnconfirmedCount" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Frissítés
            </button>
            <button @click="deleteUnconfirmedReservations" class="mt-2 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                Minden megerősítetlen foglalás törlése
            </button>
        </div>
    </div>
</template>

<script setup>
import { useTableStore } from "@/stores/table";
import { computed, ref, onMounted } from "vue";
import { useResAdminStore } from "@/stores/resAdmin";


const tableStore = useTableStore();
const resAdminStore = useResAdminStore();
const tables = computed(() => tableStore.items || []);
const unconfirmedCount = computed(() => resAdminStore.unconfirmedCount);
const reservations = computed(() => resAdminStore.reservations || []);
const reservationsByTable = computed(() => {
    const map = {};
    for (const r of reservations.value) {
        const tableId = r.table_id ?? r.table?.id ?? null;
        if (tableId == null) continue;
        if (!map[tableId]) map[tableId] = [];
        map[tableId].push(r);
    }
    return map;
});
onMounted(async () => {
    try {
        await tableStore.fetchTables();
        // load the unconfirmed reservations count when the view mounts
        await resAdminStore.fetchUnconfirmedCount();
        await resAdminStore.fetchReservations();
        console.log("Fetched tables:", tableStore.items);
    } catch (e) {
        console.error("Failed to load tables or unconfirmed count", e);
    }
});
const newTableCapacity = ref(1);

const createTable = async () => {
  const result = await tableStore.createTable({ capacity: newTableCapacity.value });
  if (result) {
    newTableCapacity.value = 1;
  }
};
const fetchUnconfirmedCount = async () => {
    try {
        await resAdminStore.fetchUnconfirmedCount();
    } catch (e) {
        console.error("Failed to fetch unconfirmed count", e);
    }
};
const deleteUnconfirmedReservations = async () => {
    try {
        await resAdminStore.deleteUnconfirmedReservations();
        // Refresh the count after deletion
        await fetchUnconfirmedCount();
    } catch (e) {
        console.error("Failed to delete unconfirmed reservations", e);
    }
};

</script>