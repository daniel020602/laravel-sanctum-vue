<template>
  <div>
    <h2>Mains</h2>
    <ul>
      <li v-for="item in mains" :key="item.id">{{ item.name }}</li>
    </ul>
  </div>
</template>

<script setup>
import { useMenuStore } from '@/stores/menus';
import { computed } from 'vue';

const menusStore = useMenuStore();

// trigger fetch but do not await here so setup() remains synchronous
if (typeof menusStore.fetchMenuItems === 'function') {
  menusStore.fetchMenuItems().catch(err => {
    // log errors but don't make setup async
    // consider adding a dedicated error state in the store if needed
    // eslint-disable-next-line no-console
    console.error('Failed to fetch menu items', err);
  });
}
console.log('menusStore items:', menusStore.items);
// computed list of mains (category === 'main') directly from store.items
const mains = computed(() => {
  const list = Array.isArray(menusStore.items) ? menusStore.items : [];
  return list.filter(item => item && item.type === 'main');
});
</script>