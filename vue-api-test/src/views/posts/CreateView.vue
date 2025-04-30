<script setup>
import { reactive } from 'vue';
import { usePostsStore } from '@/stores/posts'
import { storeToRefs } from 'pinia';
const { createPost } = usePostsStore()
const { errors } = storeToRefs(usePostsStore())
const formData = reactive({
    title: '',
    body: ''
})
</script>

<template>
    <main>
        <h1 class="title">create new post</h1>
        <form @submit.prevent="createPost(formData)" class="w-1/2 mx-auto space-y-6">
            <div>
                <input type="text" placeholder="title" v-model="formData.title"/>
            </div>
            <p class="error" v-if="errors.title">{{ errors.title[0] }}</p>
            <div>
                <textarea rows="6" placeholder="post" v-model="formData.body"></textarea>
            </div>
            <p class="error" v-if="errors.body">{{ errors.body[0] }}</p>
            <button class="primary-btn">create</button>
        </form>
    </main>
</template>