import { defineStore } from "pinia"
export const useAuthStore = defineStore('authStore', {
    state:()=>{
        return {
            user: null,
            token: null
        }
    },
    actions:{
        async authenticate(apiRoute,formData){
            const res =await fetch(`/api/${apiRoute}`, {
                method: 'POST',
                body: JSON.stringify(formData)
            })
            const data = await res.json()
            console.log(data)
        }
    }
});