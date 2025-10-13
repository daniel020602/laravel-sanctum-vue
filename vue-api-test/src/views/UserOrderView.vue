<template>
  <div class="container mx-auto p-4">
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
      <ul class="grid grid-cols-1 justify-items-center">
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
    <div class="mt-6">
      <h2 class="text-center">Your Order</h2>
      <ul class="grid grid-cols-1 justify-items-center">
        <li class="w-160 bg-yellow-200 m-1 text-center rounded" v-for="(item, index) in order" :key="index">
          {{ item.name }} - {{ item.price }} Ft
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { useMenuStore } from '@/stores/menus';
import { computed, ref } from 'vue';

const menusStore = useMenuStore();
const showGarnish = ref(null);
const order = ref([]);

if (typeof menusStore.fetchMenuItems === 'function') {
  menusStore.fetchMenuItems().catch(err => {
    console.error('Failed to fetch menu items', err);
  });
}

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
  order.value.push(item);
  if (item.type === 'main') {
    showGarnish.value = item.id;
  } else if (item.type === 'garnish') {
    showGarnish.value = null;
  } 
  console.log('Order:', order.value);
}
</script>