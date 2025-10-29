<template>
    <main>
        <div class="text-center">
            <h1 class="title">Üdvözöljük A Duna Bisztró weboldalán!</h1>
            <p>Fedezze fel ínycsiklandó ételeinket és barátságos légkörünket!</p>
            <RouterLink class="block text-blue-500 hover:underline" :to="{ name: 'menu' }">Tekintse meg menünket</RouterLink>
            <br/>
            <RouterLink v-if="subscription" class="block text-blue-500 hover:underline" :to="{ name: 'subscription-edit'}">Heti Menü módosítása</RouterLink>
            <RouterLink v-else class="block text-blue-500 hover:underline" :to="{ name: 'subscription' }">Feliratkozás a heti menüre</RouterLink>
            <br/>
            <RouterLink class="block text-blue-500 hover:underline" :to="{ name: 'new-reservation' }">asztalfoglalás</RouterLink>
            <br/>
            <RouterLink class="block text-blue-500 hover:underline" :to="{ name: 'confirm-reservation' }">foglalás megerősítése</RouterLink>
            <br/>
                <RouterLink class="block text-blue-500 hover:underline" :to="{ name: 'search-reservation' }">foglalás keresése</RouterLink>
            <br/>
            <RouterLink class="block text-blue-500 hover:underline" :to="{ name: 'user-orders' }">ételrendelés</RouterLink>
            <div v-if="orders" class="mt-6">
                <h2 class="subtitle">Saját rendeléseim</h2>
                <ul>
                    <li v-for="order in orders" :key="order.id">
                        <RouterLink :to="{ name: 'user-order-status', params: { id: order.id } }">{{ order.id }}</RouterLink>
                    </li>
                </ul>
            </div>
        </div>
    </main>
</template>

<script setup>
import { useSubscriptionStore } from "@/stores/subscription";
import { useOrdersStore } from "@/stores/orders";
import { ref, onMounted, Text } from "vue";

const subscription = ref(null);
const orders = ref([]);
const error = ref(null);
const subscriptionStore = useSubscriptionStore();
const ordersStore = useOrdersStore();


   onMounted(async () => {
       // load the authenticated user's subscription for next week
       subscriptionStore.fetchUserWeek().then(data => {
           subscription.value = data ? data.subscription : null;
       }).catch(err => {
           // ignore 404 (no subscription) but surface other errors
           console.error('fetchUserWeek failed', err);
           if (err && err.message) error.value = err.message;
       });
       ordersStore.fetchCurrentUserOrders().then(data => {
           orders.value = data;
       }).catch(err => {
           console.error('fetchCurrentUserOrders failed', err);
           if (err && err.message) error.value = err.message;
       });
   });

</script>


