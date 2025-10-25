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
            <div v-if="order?.items && order.items.length">
                <h2>Rendelés részletei:</h2>
                <ul>
                <li v-for="item in order.items" :key="item.id">
                    {{ item.name }} x {{ item.quantity }} - {{ item.price * item.quantity }} Ft
                </li>
                </ul>
            </div>
            <h3>Összesen: {{ order?.total }} Ft</h3>
            <h3 v-if="order?.is_paid">fizetve</h3>
        </div>
        <RouterLink v-if="!order?.is_paid" :to="{ name: 'user-change-order', params: { id: order?.id } }" class="text-blue-500 hover:underline">Vissza az ételrendelésekhez</RouterLink>
        <button v-if="!order?.is_paid" @click="deleteOrder" class="text-red-500 hover:underline">Rendelés törlése</button>
        <RouterLink v-if="!order?.is_paid" :to="{ name: 'pay-for-order', params: { id: order?.id } }" class="text-green-500 hover:underline">Fizetés</RouterLink>
    </div>
</template>

<script setup>
import { useOrdersStore } from '@/stores/orders';
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const orderStore = useOrdersStore();
const route = useRoute();
const router = useRouter();
const order = ref(null);
const loading = ref(true);
const notFound = ref(false);

async function deleteOrder() {
    if (!order.value || !order.value.id) return;
    try {
    await orderStore.deleteOrder(order.value.id);
    order.value = null;
    router.push({ name: 'home' });
    } catch (err) {
        console.error('Failed to delete order', err);
    }
}


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
