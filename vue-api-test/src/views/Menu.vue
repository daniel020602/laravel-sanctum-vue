<template>
    <div class="container mx-auto p-4">
        <h1 class="title">Étlap</h1>
        <div v-if="menus.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4  ">
            <div v-for="(menu) in mainMenus" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                {{ menu.name }} - {{ menu.price }} Ft
            </div>
        </div>
        <p v-else>Nincs elérhető étlap.</p>
    </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useMenuStore } from '@/stores/menus'

const menuStore = useMenuStore()
const menus = computed(() => menuStore.items)
const mainMenus = computed(() => menus.value.filter(menu => menu.type == 'main'))
console.log(mainMenus.value)
onMounted(() => {
    if (!menuStore.items.length) {
        menuStore.fetchMenuItems()
    }
})
</script>
