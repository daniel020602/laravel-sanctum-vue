<template>
  <div class="container mx-auto p-4 grid">
    <dic>
      <div>
        <h2 class="text-center mt-4">Levesek</h2>
        <ul class="grid grid-cols-1 justify-items-center">
          <li class="w-160 bg-gray-200 m-1 text-center rounded" v-for="item in soups" :key="item.id">
            {{ item.name }} - {{ item.price }} Ft
            <button class="bg-blue-500 text-white rounded px-2 py-1 mt-1" @click="addToOrder(item)">Add to Order</button>
          </li>
        </ul>
      </div>
      <div>
        <h2 class="text-center ">Főételek</h2>
        <ul class="grid grid-cols-1 justify-items-center w-160">
          <li class="w-160 bg-gray-200 m-1 text-center rounded" v-for="item in mains" :key="item.id">
            {{ item.name }} - {{ item.price }} Ft
            <button class="bg-blue-500 text-white rounded px-2 py-1 mt-1" @click="addToOrder(item)">Add to Order</button>
            <div v-if="showGarnish === item.id">
              <h3 class="mt-2">Select Garnish</h3>
              <ul class="grid grid-cols-1 justify-items-center">
                <li class="w-60 bg-green-200 m-1 text-center rounded" v-for="garnish in garnishes" :key="garnish.id">
                  {{ garnish.name }} - {{ garnish.price }} Ft
                  <button class="bg-blue-500 text-white rounded w-40 px-2 py-1 mt-1" @click="addToOrder(garnish)">Add to Order</button>
                </li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
      <div>
        <h2 class="text-center mt-4">Desszertek</h2>
        <ul class="grid grid-cols-1 justify-items-center">
          <li class="w-160 bg-gray-200 m-1 text-center rounded" v-for="item in desserts" :key="item.id">
            {{ item.name }} - {{ item.price }} Ft
            <button class="bg-blue-500 text-white rounded px-2 py-1 mt-1" @click="addToOrder(item)">Add to Order</button>
          </li>
        </ul>
        </div>
      <div>
        <h2 class="text-center mt-4">Italok</h2>
        <ul class="grid grid-cols-1 justify-items-center">
          <li class="w-160 bg-gray-200 m-1 text-center rounded" v-for="item in drinks" :key="item.id">
            {{ item.name }} - {{ item.price }} Ft
            <button class="bg-blue-500 text-white rounded px-2 py-1 mt-1" @click="addToOrder(item)">Add to Order</button>
          </li>
        </ul>
      </div>
    </dic>
    <div class="mt-6">
      <h2 class="text-center">Your Order</h2>
      <ul class="grid grid-cols-1 justify-items-center">
        <li class="w-160 bg-yellow-200 m-1 text-center rounded" v-for="(item, index) in order" :key="index">
          {{ item.name }} - {{ item.price }} Ft <span v-if="item.quantity">x {{ item.quantity }}</span>
          <button class="bg-red-500 text-white rounded px-2 py-1 mt-1" @click="removeFromOrder(index)">Remove</button>
        </li>
      </ul>
    </div>
      <div class="text-center mt-4">
        <button v-if="!id" class="bg-green-600 text-white rounded px-4 py-2" @click="createOrder" :disabled="order.length === 0">Create Order</button>
        <button v-else class="bg-blue-600 text-white rounded px-4 py-2" @click="updateOrder" :disabled="order.length === 0">Update Order</button>
        <button  class="bg-red-600 text-white rounded px-4 py-2" @click="clearOrder" :disabled="order.length === 0">Clear Order</button>
        
    </div>
  </div>
</template>

<script setup>
import router from '@/router';
import { useMenuStore } from '@/stores/menus';
import { useOrdersStore } from '@/stores/orders';
import { computed, ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';

const menusStore = useMenuStore();
const ordersStore = useOrdersStore();
const showGarnish = ref(null);
const order = ref([]);
const route = useRoute();
const id = route.params.id || null;
if (typeof menusStore.fetchMenuItems === 'function') {
  menusStore.fetchMenuItems().catch(err => {
    console.error('Failed to fetch menu items', err);
  });
}

// Prefill order when route contains an order id (run immediately like `created` and on param changes)
onMounted(() => {
  
  if (!id) return;

  useOrdersStore().fetchOrder(id).then(async (data) => {
    try {
      if (data && Array.isArray(data.items)) {
        // New normalized shape: { items: [{ id, quantity }] }
        order.value = denormalizeOrder(data);
      } else if (data && Array.isArray(data.orderproducts)) {
        // Legacy shape: orderproducts array -> convert to quantity-based entries
        const reconstructed = [];
        for (const it of data.orderproducts) {
          const menu = menusStore.items.find(m => Number(m.id) === Number(it.menu_id));
          if (menu) {
            reconstructed.push({ ...menu, quantity: it.quantity || 1 });
          }
        }
        order.value = reconstructed;
      } else {
        console.warn('Unknown order shape returned from API', data);
      }
    } catch (e) {
      console.error('Failed to process fetched order', e);
    }
  }).catch(err => {
    console.error('Failed to fetch order by id', err);
  });
});

const mains = computed(() => {
  const list = Array.isArray(menusStore.items) ? menusStore.items : [];
  return list.filter(item => item && item.type === 'main');
});
const soups = computed(() => {
  const list = Array.isArray(menusStore.items) ? menusStore.items : [];
  return list.filter(item => item && item.type === 'soup');
});
const garnishes = computed(() => {
  const list = Array.isArray(menusStore.items) ? menusStore.items : [];
  return list.filter(item => item && item.type === 'garnish');
});
const desserts = computed(() => {
  const list = Array.isArray(menusStore.items) ? menusStore.items : [];
  return list.filter(item => item && item.type === 'dessert');
});
const drinks = computed(() => {
  const list = Array.isArray(menusStore.items) ? menusStore.items : [];
  return list.filter(item => item && item.type === 'drink');
});

function addToOrder(item) {
  // find existing entry by id
  const id = Number(item.id ?? item.menu_id);
  const existing = order.value.find(o => Number(o.id) === id);
  if (existing) {
    existing.quantity = (Number(existing.quantity) || 1) + 1;
  } else {
    order.value.push({ ...item, quantity: 1 });
  }
  if (item.type === 'main') {
    showGarnish.value = item.id;
  } else if (item.type === 'garnish') {
    showGarnish.value = null;
  }
  console.log('Order:', order.value);
}
function removeFromOrder(index) {
  const item = order.value[index];
  if (!item) return;
  if ((Number(item.quantity) || 0) > 1) {
    item.quantity = Number(item.quantity) - 1;
  } else {
    order.value.splice(index, 1);
  }
}
function clearOrder() { 
  order.value = [];
  showGarnish.value = null;
}
// normalize order into { items: [{ id, quantity }, ...] }
function normalizeOrder(orderArray) {
  const counts = new Map();
  for (const it of orderArray) {
    // item may be an object with id or menu_id
    const id = Number(it.id ?? it.menu_id ?? it.items?.id);
    if (!id) continue;
    const qty = Number(it.quantity) || 1; // default quantity
    counts.set(id, (counts.get(id) || 0) + qty);
  }
  const items = Array.from(counts.entries()).map(([id, quantity]) => ({ id, quantity }));
  return { items };
}

// Reverse of normalizeOrder: take { items: [{id, quantity}] } and return an array
// of menu item objects (from menusStore.items) augmented with quantity so the UI
// can display them. Missing menu ids are ignored.
function denormalizeOrder(normalized) {
  const items = Array.isArray(normalized?.items) ? normalized.items : [];
  const result = [];
  for (const it of items) {
    const id = Number(it.id);
    const quantity = Number(it.quantity) || 1;
    if (!id) continue;
    const menuItem = menusStore.items.find(m => Number(m.id) === id);
    if (!menuItem) continue;
    result.push({ ...menuItem, quantity });
  }
  return result;
}

async function createOrder() {
  const payload = normalizeOrder(order.value);
  try {
    const newOrder = await ordersStore.createOrder(payload);
    order.value = [];
    showGarnish.value = null;
    const id = newOrder?.id || (localStorage.getItem('lastOrder') ? JSON.parse(localStorage.getItem('lastOrder')).id : null);
    if (id) {
      router.push({ name: 'user-order-status', params: { id } });
    } else {
      console.error('Create order succeeded but no id returned', newOrder);
    }
  } catch (err) {
    console.error('Failed to create order', err);
  }
}

async function updateOrder()
{
  const payload = normalizeOrder(order.value);
  try {
    await ordersStore.updateOrder(id, payload);
    order.value = [];
    showGarnish.value = null;
    router.push({ name: 'user-order-status', params: { id } });
  } catch (err) {
    console.error('Failed to update order', err);
  }
}
</script>