<template>
    <div class="container mx-auto p-4 self-center bg-cyan-100 rounded-lg mt-4">
        <h1>Rendelés adatai</h1>
        <div v-if="order">
            <h2 class="text-xl font-bold mb-4">Rendelés #{{ order.id }}</h2>
            <p><strong>Felhasználó:</strong> {{ order.user?.name || 'N/A' }}</p>
            <p><strong>Összeg:</strong> {{ order.total_amount }} Ft</p>
            <p><strong>Státusz:</strong> {{ order.status }}</p>

            <h3 class="text-lg font-semibold mt-4 mb-2">Rendelt termékek:</h3>
            <table class="min-w-full border-collapse">
                <thead>
                    <tr>
                        <th class="border p-2 text-left">Termék név</th>
                        <th class="border p-2 text-left">Mennyiség</th>
                        <th class="border p-2 text-left">Ár</th>
                    </tr>
                </thead>
                <tbody>
                        <tr v-for="item in order.orderproducts" :key="item.id">
                            <td class="border p-2">{{ getMenuName(item.menu_id) }}</td>
                            <td class="border p-2">{{ item.quantity }}</td>
                            <td class="border p-2">{{ item.price }} Ft</td>
                        </tr>
                </tbody>
            </table>
            <div>
                <select @change="changeStatus">
                    <option value="pending" :selected="order.status === 'pending'">Függőben</option>
                    <!-- use 'prepared' which matches backend allowed status names -->
                    <option value="prepared" :selected="order.status === 'prepared'">Folyamatban</option>
                    <option value="completed" :selected="order.status === 'completed'">Teljesítve</option>
                </select>
            </div>
            <RouterLink v-if="order.status=='pending' && !order.is_paid" :to="{ name: 'user-change-order' }" class="mt-4 inline-block bg-blue-500 text-white py-2 px-4 rounded">
                rendelés módosítása
            </RouterLink>
            <button @click="deleteOrder" class="mt-4 inline-block bg-red-500 text-white py-2 px-4 rounded">
                Rendelés törlése
            </button>
            <button @click="markAsPaid" class="mt-4 inline-block bg-green-500 text-white py-2 px-4 rounded">
                kifizették helyben
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useOrdersStore } from '@/stores/orders';
import { useRoute, useRouter } from 'vue-router';
import { useMenuStore } from '@/stores/menus';


const ordersStore = useOrdersStore();
const menuStore = useMenuStore();
const route = useRoute();
const router = useRouter();
const order = ref(null);
const id = ref(null);
onMounted(async () => {
    id.value = route.params.id;
    // fetchOrder is the store action to load a single order
    order.value = await ordersStore.fetchOrder(id.value);
    // populate menus into the menu store so we can lookup names
    await menuStore.fetchMenuItems();
    console.log('Fetched order:', order.value);
});

function getMenuName(menuId) {
    if (!menuId) return 'N/A';
    const found = menuStore.items.find(m => Number(m.id) === Number(menuId));
    return found ? found.name : 'N/A';
}

async function changeStatus(event) {
    const newStatus = event.target.value;
    try {
        
        if (newStatus === 'completed') {
            if(confirm('Biztosan teljesíti a rendelést?')) {
                await ordersStore.changeStatus(id.value, newStatus);
                router.push({ name: 'admin-orders' });
                order.value = await ordersStore.fetchOrder(id.value);
            }
            else{
                order.value = await ordersStore.fetchOrder(id.value);
            }
        }
        else {
            // send status change to the server
            await ordersStore.changeStatus(id.value, newStatus);
            // re-fetch the updated order so this component's reactive `order` updates
            order.value = await ordersStore.fetchOrder(id.value);
        }
    } catch (e) {
        console.error('Failed to change order status', e);
        alert('Hiba a státusz változtatásakor: ' + (e.message || e));
    }
}
async function deleteOrder() {
    if(confirm('Biztosan törli a rendelést?')) {
        try {
            await ordersStore.deleteOrder(id.value);
            router.push({ name: 'admin-orders' });
        } catch (e) {
            console.error('Failed to delete order', e);
            alert('Hiba a rendelés törlésekor: ' + (e.message || e));
        }
    }
}
async function markAsPaid() {
    try {
        await ordersStore.markAsPaid(id.value);
        order.value = await ordersStore.fetchOrder(id.value);
    } catch (e) {
        console.error('Failed to mark order as paid', e);
        alert('Hiba a rendelés kifizetésének jelölésekor: ' + (e.message || e));
    }
}
</script>