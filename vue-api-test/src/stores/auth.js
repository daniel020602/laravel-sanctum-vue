import { defineStore } from "pinia"
import router from "../router/index.js"
export const useAuthStore = defineStore('authStore', {
    state:()=>{
        return {
            user: null,
            errors:{}
        }
    },
    actions:{
        async getUser()
        {
            if(localStorage.getItem('token'))
            {
                const res = await fetch('/api/user', {
                    headers: 
                    {
                        authorization: `Bearer ${localStorage.getItem('token')}`,
                    },
                })
                const data = await res.json()
                if(res.ok)
                {
                    this.user = data;
                } 
            }
        },
        async authenticate(apiRoute,formData){
            const res =await fetch(`/api/${apiRoute}`, {
                method: 'POST',
                body: JSON.stringify(formData)
            })
            const data = await res.json()
            if (data.errors)
            {
                this.errors=data.errors    
            } else {
                localStorage.setItem('token', data.token)
                this.user = data.user
                this.errors = {}
                this.router.push({name:'home'})
            }

        },
        async logout()
        {
            const res = await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    authorization: `Bearer ${localStorage.getItem('token')}`,
                },
            })
            if(res.ok)
            {
                localStorage.removeItem('token')
                this.user = null
                this.errors = {}
                this.router.push({name:'home'})
            }
        }
    }
});