import { defineStore } from "pinia"
export const useAuthStore = defineStore('authStore', {
    state:()=>{
        return {
            user: null,
            errors:{}
        }
    },
    actions:{
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
                
            }

        }
    }
});