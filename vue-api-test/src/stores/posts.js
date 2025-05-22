import { defineStore } from 'pinia';
import { useAuthStore } from './auth.js'

export const usePostsStore = defineStore('postsStore', {
    state: () =>
    {
        return {
            errors: {}
        }
    },
    actions: 
    {
        async getAllPosts(){
            const res= await fetch('/api/posts')
            const data = await res.json()
            return data
        },
        async getPost(id){
            const res= await fetch('/api/posts/' + id)
            const data = await res.json()
            return data.post
        },
        async createPost(formData){
            const res= await fetch('/api/posts', {
                method: 'POST',
                headers: {
                    Authorization: 'Bearer ' + localStorage.getItem('token'),
                },
                body: JSON.stringify(formData),

            })
            const data = await res.json()
            if(data.errors) {
                this.errors = data.errors
            } else{
                this.router.push({name:'home'})
            }
            
        },
        async deletePost(post){
            const authStore = useAuthStore()
            if(authStore.user.id=== post.user_id){ 
                console.log(post.id)
            const res= await fetch('/api/posts/' + post.id, {
                method: 'DELETE',
                headers: {
                    Authorization: 'Bearer ' + localStorage.getItem('token'),
                },
            })
            const data = await res.json()
            if(data.errors) {
                this.errors = data.errors
            } else{
                this.router.push({name:'home'})
            }
        }

    },
        async updatePost(post, formData){
            const authStore = useAuthStore()
            if(authStore.user.id=== post.user_id){ 

            const res= await fetch('/api/posts/' + post.id, {
                method: 'PUT',
                headers: {
                    Authorization: 'Bearer ' + localStorage.getItem('token'),
                },
                body: JSON.stringify(formData),
            })
            const data = await res.json()
            if(data.errors) {
                this.errors = data.errors
            } else{
                this.router.push({name:'home'})
            }
        }
    }
}
}
)