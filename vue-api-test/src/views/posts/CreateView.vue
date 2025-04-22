<script setup>
import { reactive } from 'vue';
const formData = reactive({
    title: '',
    body: ''
})
</script>

<template>
    <main>
        <h1 class="title">create new post</h1>
        <form @submit.prevent="console.log(formData)" class="w-1/2 mx-auto space-y-6">
            <div>
                <input type="text" placeholder="title" v-model="formData.title"/>
            </div>
            <div>
                <textarea rows="6" placeholder="post" v-model="formData.body"></textarea>
            </div>
            <button class="primary-btn">create</button>
        </form>
    </main>
</template>