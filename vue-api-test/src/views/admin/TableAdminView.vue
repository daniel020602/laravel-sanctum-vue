<template>
    <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Asztalok kezelése</h1>
        <div v-if="tables.length">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b border-gray-200">Asztal ID</th>
                        <th class="py-2 px-4 border-b border-gray-200">Ülőhelyek Száma</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="table in tables" :key="table.id">
                        <td class="py-2 px-4 border-b border-gray-200">{{ table.id }}</td>
                        <td class="py-2 px-4 border-b border-gray-200">{{ table.capacity }}</td>
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
    </div>
</template>

<script setup>
import { useTableStore } from "@/stores/table";
import { computed, ref, onMounted } from "vue";

const tableStore = useTableStore();
const tables = computed(() => tableStore.items || []);

onMounted(async () => {
    try {
        await tableStore.fetchTables();
        console.log("Fetched tables:", tableStore.items);
    } catch (e) {
        console.error("Failed to load tables", e);
    }
});
const newTableCapacity = ref(1);

const createTable = async () => {
  const result = await tableStore.createTable({ capacity: newTableCapacity.value });
  if (result) {
    newTableCapacity.value = 1;
  }
};

</script>