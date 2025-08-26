<template>
    <div class="container mx-auto p-4 self-center text-center">
        <h1 class="title">Étlap szerkesztése</h1>
        <div class="mb-4 flex flex-col sm:flex-row gap-2 items-center justify-center">
            <label class="sr-only">Szűrő</label>
            <select v-model="selectedType">
                <option value="all">Összes</option>
                <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
            </select>
            <input v-model="query" type="search" placeholder="Keresés név szerint" />
        </div>

    <ul class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 justify-items-center pb-4 max-h-96 overflow-y-auto">
            <li v-for="(menu) in filteredItems" :key="menu.id" class="bg-amber-50 p-4 rounded text-center w-80 shadow">
                <div v-if="editingId !== menu.id">
                    <div class="font-medium">{{ menu.name }}</div>
                    <div class="text-sm text-slate-600">{{ menu.price }} Ft</div>
                    <div class="mt-2 flex gap-2 justify-center">
                        <button @click="startEdit(menu)" class="text-sm text-blue-600">Szerkeszt</button>
                        <button @click="confirmDelete(menu.id)" class="text-sm text-red-600">Töröl</button>
                    </div>
                </div>
                <div v-else class="space-y-2">
                    <input v-model="editModel.name" type="text" class="w-full p-2 rounded" />
                    <input v-model.number="editModel.price" type="number" class="w-full p-2 rounded" />
                    <select v-model="editModel.type" class="w-full p-2 rounded">
                        <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                    </select>
                    <div class="flex gap-2 justify-center">
                        <button @click="saveEdit(menu.id)" class="primary-btn">Mentés</button>
                        <button @click="cancelEdit" class="text-sm">Mégse</button>
                    </div>
                </div>
            </li>
        </ul>
    <form @submit.prevent="handleCreate">
            <!-- stretch children so they match heights, apply consistent control height -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-stretch">
                <input v-model="formData.name" type="text" placeholder="Név" required class="h-12 p-2 w-full" />
                <input v-model.number="formData.price" type="number" placeholder="Ár" required class="h-12 p-2 w-full" />
                <select v-model="formData.type" required class="h-12 p-2 w-full">
                    <option value="">Válassz típust</option>
                    <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                </select>
                <button type="submit" class="primary-btn h-12 flex items-center justify-center">Hozzáadás</button>
            </div>
        </form>
    </div>

</template>

<script setup>
    import { onMounted, ref, computed, reactive} from 'vue'
    import { useMenuStore } from '@/stores/menus'

    const menuStore = useMenuStore()
    const selectedType = ref('all')
    const query = ref('')
    const formData = reactive({ name: '', price: '', type: '' })
    const errors = ref({})

    const types = computed(() => {
        const set = new Set(menuStore.items.map(i => i.type).filter(Boolean))
        return Array.from(set)
    })

    const filteredItems = computed(() => {
        return menuStore.items.filter(item => {
            const matchesType = selectedType.value === 'all' || item.type === selectedType.value
            const matchesQuery = !query.value || item.name.toLowerCase().includes(query.value.toLowerCase())
            return matchesType && matchesQuery
        })
    })

    onMounted(() => {
        menuStore.fetchMenuItems()
    })

    console.log(menuStore.items)

    async function handleCreate() {
        // spread reactive formData into a plain object so the backend receives
        // { name, price, type } instead of { formData: { ... } }
        const payload = { ...formData }
        const res = await menuStore.createMenuItem(payload)
        if (res && res.errors) {
            // TODO: show validation/auth errors in the UI
            console.error('Create menu errors', res.errors)
            errors.value = res.errors
        } else {
            // reset form
            formData.name = ''
            formData.price = ''
            formData.type = ''
            errors.value = {}
        }
    }

    // inline edit state & handlers
    const editingId = ref(null)
    const editModel = reactive({ name: '', price: 0, type: '' })

    function startEdit(menu) {
        editingId.value = menu.id
        editModel.name = menu.name
        editModel.price = menu.price
        editModel.type = menu.type
    }

    function cancelEdit() {
        editingId.value = null
    }

    async function saveEdit(id) {
        const payload = { name: editModel.name, price: editModel.price, type: editModel.type }
        const res = await menuStore.updateMenuItem(id, payload)
        if (res && res.errors) {
            console.error('Update errors', res.errors)
        } else {
            editingId.value = null
        }
    }

    async function confirmDelete(id) {
        if (!confirm('Biztosan törlöd ezt az elemet?')) return
        const res = await menuStore.deleteMenuItem(id)
        if (res && res.errors) {
            console.error('Delete error', res.errors)
        }
    }
</script>