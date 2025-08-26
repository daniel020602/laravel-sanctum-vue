import { defineStore } from "pinia";
import { useAuthStore } from "./auth";

export const useWeeksStore = defineStore("weeks", {
  state: () => ({
    user: null,
    items: [],
    isLoading: false,
  }),
  actions: {
    async fetchWeeks() {
      this.isLoading = true;
      const authStore = useAuthStore();
      const token = authStore.token;
      console.log(token);

      try {
        const response = await fetch("/api/weeks", {
          headers: {
            Authorization: 'Bearer ' + localStorage.getItem('token'),
          },
        });

        const data = await response.json();
        this.items = data.data; // <-- This line updates the store!
        console.log(data);
      } catch (error) {
        console.error("Error fetching weeks:", error);
      } finally {
        this.isLoading = false;
      }
    },
  },
});
