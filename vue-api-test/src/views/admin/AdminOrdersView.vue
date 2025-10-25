<template>
<div class="container mx-auto p-4 self-center bg-cyan-100 rounded-lg mt-4">
    <h1 class="text-2xl font-bold mb-4">Folyamatban lévő rendelések</h1>
    <div v-if="orders.length === 0">
        Nincsenek folyamatban lévő rendelések.
    </div>
    <div v-else>
        <table class="min-w-full border-collapse block md:table">
        <thead class="block md:table-header-group">
            <tr class="border border-gray-300 md:border-none block md:table-row absolute -top-full md:top-auto -left-full md:left-auto md:relative">
            <th class="bg-gray-200 p-2 text-left font-medium md:border md:border-gray-300 block md:table-cell">Rendelés ID</th>
            <th class="bg-gray-200 p-2 text-left font-medium md:border md:border-gray-300 block md:table-cell">Felhasználó</th>
            <th class="bg-gray-200 p-2 text-left font-medium md:border md:border-gray-300 block md:table-cell">Összeg</th>
            <th class="bg-gray-200 p-2 text-left font-medium md:border md:border-gray-300 block md:table-cell">Státusz</th>
            </tr>
        </thead>
    <tbody class="block md:table-row-group">
      <tr v-for="order in orders" :key="order.id"
        class="border border-gray-300 md:border-none block md:table-row hover:bg-gray-50 cursor-pointer"
        role="link"
        tabindex="0"
        @click="goToOrder(order.id)"
        @keydown.enter="goToOrder(order.id)">
        <td class="p-2 md:border md:border-gray-300 block md:table-cell">{{ order.id }}</td>
        <td class="p-2 md:border md:border-gray-300 block md:table-cell">{{ order.user?.name || 'N/A' }}</td>
        <td class="p-2 md:border md:border-gray-300 block md:table-cell">{{ order.total_amount }} Ft</td>
        <td class="p-2 md:border md:border-gray-300 block md:table-cell">{{ order.status }}</td>
      </tr>
    </tbody>
    </table>
    
  </div>
</div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useOrdersStore } from '@/stores/orders';
import { useRouter } from 'vue-router';

const ordersStore = useOrdersStore();
const router = useRouter();
const orders = ref([]);

onMounted(async () => {
  try {
    orders.value = await ordersStore.fetchInProgressOrders();
  } catch (e) {
    console.error('Failed to load in-progress orders', e);
  }
});

function goToOrder(id) {
  if (!id) return;
  router.push({ name: 'admin-orders-status', params: { id } });
}
</script>