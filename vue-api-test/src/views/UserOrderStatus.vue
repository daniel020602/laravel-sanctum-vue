<template>
    <div>
        <div v-if="loading">Loading order…</div>
        <div v-else-if="notFound">Order not found.</div>
        <div v-else>
          <h1>Rendelés befogadva!</h1>
          <p>Köszönjük, hogy nálunk rendelt! A rendelését feldolgozzuk és hamarosan értesítjük a részletekről.</p>
          <p>Rendelés azonosítója: <strong>{{ order?.id }}</strong></p>
          <p>Összeg: <strong>{{ order?.total }} Ft</strong></p>
          <p>Állapot: <strong>{{ order?.status }}</strong></p>
        </div>
        <RouterLink :to="{ name: 'user-change-order', params: { id: order?.id } }" class="text-blue-500 hover:underline">Vissza az ételrendelésekhez</RouterLink>
    </div>
</template>

<script setup>
import { useOrdersStore } from '@/stores/orders';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const orderStore = useOrdersStore();
const route = useRoute();
const order = ref(null);
const loading = ref(true);
const notFound = ref(false);

async function load() {
    const id = route.params.id;
    if (!id) {
        notFound.value = true;
        loading.value = false;
        return;
    }
    try {
        const data = await orderStore.fetchOrder(id);
        order.value = data;
    } catch (err) {
        console.error('Failed to fetch order by id', err);
        notFound.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>
