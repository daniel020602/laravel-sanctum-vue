<template>
    <div class="container mx-auto p-4">
        <h1 class="title">Étlap</h1>
        <div v-if="menus.length" class="space-y-8">
            <section v-if="soups.length">
                <h2 class="text-lg font-semibold mb-2">Levesek</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(menu) in soups" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                        {{ menu.name }} - {{ menu.price }} Ft
                    </div>
                </div>
            </section>

            <section v-if="mainMenus.length">
                <h2 class="text-lg font-semibold mb-2">Főételek</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(menu) in mainMenus" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                        {{ menu.name }} - {{ menu.price }} Ft
                    </div>
                </div>
            </section>

            <section v-if="garnishes.length">
                <h2 class="text-lg font-semibold mb-2">Köretek</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(menu) in garnishes" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                        {{ menu.name }} - {{ menu.price }} Ft
                    </div>
                </div>
            </section>

            <section v-if="desserts.length">
                <h2 class="text-lg font-semibold mb-2">Deszertek</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(menu) in desserts" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                        {{ menu.name }} - {{ menu.price }} Ft
                    </div>
                </div>
            </section>

            <section v-if="drinks.length">
                <h2 class="text-lg font-semibold mb-2">Italok</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(menu) in drinks" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                        {{ menu.name }} - {{ menu.price }} Ft
                    </div>
                </div>
            </section>
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
const soups = computed(() => menus.value.filter(menu => menu.type == 'soup'))
const desserts = computed(() => menus.value.filter(menu => menu.type == 'dessert'))
const garnishes = computed(() => menus.value.filter(menu => menu.type == 'garnish'))
const drinks = computed(() => menus.value.filter(menu => menu.type == 'drink'))
console.log(mainMenus.value)
onMounted(() => {
    if (!menuStore.items.length) {
        menuStore.fetchMenuItems()
    }
})
</script>
