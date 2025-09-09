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

        const text = await response.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          // non-JSON response — log raw text to help debug server side issues
          console.error('fetchWeeks: server returned non-JSON response:', text);
          throw new Error('Server returned non-JSON response');
        }

        // support different shapes: { data: [...] } or { weeks: [...] }
        this.items = data.data ?? data.weeks ?? data;
        console.log('fetchWeeks parsed response:', data);
      } catch (error) {
        console.error("Error fetching weeks:", error);
      } finally {
        this.isLoading = false;
      }
    },
  },
});
