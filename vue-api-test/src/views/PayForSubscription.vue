<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-6">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-2">Előfizetés heti menüre</h2>
            <p class="text-sm text-gray-600 mb-4">Ez egy teszt fizetés (nem valós). Csak kattints a gombra a folytatáshoz.</p>

            <div class="flex items-baseline justify-between mb-6">
                <div>
                    <div class="text-xs text-gray-500">Havi / heti</div>
                    <div class="text-2xl font-bold">1 990 Ft</div>
                </div>
                <div class="text-right text-sm text-gray-500">Card ending •••• 4242</div>
            </div>

            <div class="flex gap-3">
                <button @click="pay" :disabled="loading || success" class="flex-1 primary-btn">
                    <span v-if="!loading && !success">Mock Pay</span>
                    <span v-else-if="loading">Processing…</span>
                    <span v-else>Paid ✓</span>
                </button>
                <button @click="cancel" class="btn" :disabled="loading">Mégse</button>
            </div>

            <p v-if="success" class="mt-4 text-sm text-green-700">Sikeres fizetés (teszt). Köszönjük!</p>
            <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
        </div>
    </div>
</template>


<script setup>
    import { ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useSubscriptionStore } from '@/stores/subscription';

    const route = useRoute();
    const router = useRouter();
    const subscriptionStore = useSubscriptionStore();

    const loading = ref(false);
    const success = ref(false);
    const error = ref(null);

    async function pay() {
        loading.value = true;
        error.value = null;
        try {
            await subscriptionStore.createSubscription(route.params.id);
            success.value = true;
            // optional: navigate back or to a confirmation after short delay
            setTimeout(() => router.push({ name: 'home' }), 1000);
        } catch (e) {
            error.value = e.message || 'Hiba történt a fizetés során';
        } finally {
            loading.value = false;
        }
    }

    function cancel() {
        router.back();
    }
</script>

<style scoped>
.primary-btn { background: #2563eb; color: #fff; padding: 0.6rem 1rem; border-radius: 6px; border: none }
.btn { padding: 0.5rem 1rem; border: 1px solid #ccc; background: #fff }
</style>