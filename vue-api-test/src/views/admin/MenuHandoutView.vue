<template>
    <div class="container mx-auto p-4 self-center bg-cyan-100 rounded-lg mt-4">
        <h1 class="text-2xl font-bold text-center p-4 m-4 items-center">Napi menü kiadása</h1>
        <div>
            <label for="menu">felhasználó keresése email cím szerint:</label>
            <input
                id="menu"
                v-model="searchEmail"
                class="border border-gray-300 rounded py-1 mt-2  w-3/4 align-center"
                @input="handleInput"
            />
            
        </div>
        <div>
            <div v-if="res && res.users && res.users.length">
                <h2 class="text-xl font-semibold mt-4">Találatok:</h2>
                <div class="mt-2">
                    <div v-for="user in res.users" :key="user.id" class="border border-gray-300 bg-gray-300 rounded p-2 mb-2">
                        <p><strong>Név:</strong> {{ user.name }}</p>
                        <p><strong>Email:</strong> {{ user.email }}</p>
                    </div>
                    <div v-for="(choice, index) in res.choices" :key="choice.id" class="rounded p-2 mb-2">
                        <p>
                            {{ days[index] }}
                            - választás: {{ choice.week_menu.option }}
                            <span class="ml-2">- {{ menuMap[choice.week_menu.menu_id] }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div v-else-if="res && (!res.users || !res.users.length)" class="mt-2 text-red-500">
                Nincs találat.
            </div>
            <div v-else class="mt-4 text-gray-500">
                Kérem, írjon be legalább 3 karaktert a kereséshez.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSubscriptionStore } from '@/stores/subscription'
import { useMenuStore } from '@/stores/menus'

const days = ['hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek']
const searchEmail = ref('')
const subscriptionStore = useSubscriptionStore()
const menuStore = useMenuStore()

const res = ref(null)

// load menus on mount and expose a map id->name for fast lookup
onMounted(async () => {
    try {
        await menuStore.fetchMenuItems()
    } catch (e) {
        console.error('Failed to load menus', e)
    }
})

const menuMap = computed(() => {
    const map = {}
    for (const m of (menuStore.items || [])) map[m.id] = m.name
    return map
})

const handleInput = () => {
    if (searchEmail.value.length > 2) {
        subscriptionStore.searchUserByEmail(searchEmail.value)
            .then(response => {
                res.value = response
                console.log('Fetched user:', res.value)
            })
    }
}

</script>