<template>
    <div class="container mx-auto p-4">
        <h1 class="title">Étlap</h1>
        <div v-if="menus.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4  ">
            <div v-for="(menu, idx) in menus" :key="menu.id" class="bg-amber-50 p-4 rounded text-center">
                {{ menu.name }} - {{ menu.price }} Ft
            </div>
        </div>
        <p v-else>Nincs elérhető étlap.</p>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
const menus = ref([])

onMounted(async () => {
    try {
        const res = await fetch('/api/menus')
        if (!res.ok) throw new Error('Hiba az étlap lekérdezésekor')
        const data = await res.json()
        menus.value = data
    } catch (e) {
        menus.value = []
    }
})
</script>
