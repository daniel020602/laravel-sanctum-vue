<template>
    <div>
        <h1>feliratkozás menüre</h1>
        <div>
            <button @click="handleClick">fizetés</button>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import router from '@/router';
const route = useRoute();             

import { useSubscriptionStore } from '@/stores/subscription';
    const subscriptionStore = useSubscriptionStore();

    function handleClick() {
        subscriptionStore.createSubscription(route.params.id);
    }
</script>