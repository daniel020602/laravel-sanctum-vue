<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-6">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-2">Fizetés rendeléshez</h2>
            <p class="text-sm text-gray-600 mb-4">Ez egy teszt fizetés (nem valós). Csak kattints a gombra a folytatáshoz.</p>

            <div class="flex items-baseline justify-between mb-6">
                <div>
                    <div class="text-xs text-gray-500">Összeg</div>
                    <div class="text-2xl font-bold">{{ amount }} Ft</div>
                </div>
                <div class="text-right text-sm text-gray-500">Order #{{ id }}</div>
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
    import { ref, onMounted } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useOrdersStore } from '@/stores/orders';

    const route = useRoute();
    const router = useRouter();
    const ordersStore = useOrdersStore();

    const id = route.params.id;
    const amount = ref('—');
    const loading = ref(false);
    const success = ref(false);
    const error = ref(null);

    onMounted(async () => {
        if (!id) return;
        try {
            const order = await ordersStore.fetchOrder(id);
            amount.value = order?.total_amount ?? '—';
        } catch (e) {
            console.error('Failed to load order', e);
        }
    });

    async function pay() {
        if (!id) return;
        loading.value = true;
        error.value = null;
        try {
            // Use centralized store action to perform payment (sends stripeToken)
            const data = await ordersStore.payOrder(id, 'tok_visa');
            success.value = true;
            // update store (payOrder already updates current, but keep local sync)
            if (data && data.order) {
                ordersStore.current = data.order;
            }
            // optional: navigate back after a short delay
            setTimeout(() => router.push({ name: 'user-orders' }), 800);
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
