<template>
    <main>
        <div class="text-center">
            <h1 class="title">Üdvözöljük A Duna Bisztró weboldalán!</h1>
            <p>Fedezze fel ínycsiklandó ételeinket és barátságos légkörünket!</p>
            <RouterLink :class="{ 'text-blue-500 hover:underline': true }" :to="{ name: 'menu' }">Tekintse meg menünket</RouterLink>
            <br/>
            <RouterLink :class="{ 'text-blue-500 hover:underline': true }" :to="{ name: 'subscription' }">Iratkozzon fel a jövő heti menüre</RouterLink>
            <br/>
            <RouterLink v-if="subscription" :class="{ 'text-blue-500 hover:underline': true }" :to="{ name: 'subscription-edit', params: { id: subscription.id } }">Heti Menü módosítása</RouterLink>
        </div>
    </main>
</template>

<script setup>
import { useSubscriptionStore } from "@/stores/subscription";
import { ref, onMounted } from "vue";

const subscription = ref(null);
const error = ref(null);
const subscriptionStore = useSubscriptionStore();

   onMounted(async () => {
       // load the authenticated user's subscription for next week
       subscriptionStore.fetchUserWeek().then(data => {
           subscription.value = data ? data.subscription : null;
       }).catch(err => {
           // ignore 404 (no subscription) but surface other errors
           console.error('fetchUserWeek failed', err);
           if (err && err.message) error.value = err.message;
       });
   });

</script>


