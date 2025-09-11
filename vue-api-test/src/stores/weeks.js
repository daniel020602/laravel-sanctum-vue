import { defineStore } from "pinia";
import { useAuthStore } from "./auth";

export const useWeeksStore = defineStore("weeks", {
  state: () => ({
    user: null,
    items: [],
    menus: [],
    isLoading: false,
  }),
  getters: {
    soups: (state) => state.menus.filter(m => m.type === 'soup'),
    mains: (state) => state.menus.filter(m => m.type === 'main'),
  },
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
    async fetchMenus() {
      const headers = {};
      const token = localStorage.getItem('token');
      if (token) headers['Authorization'] = 'Bearer ' + token;

      const res = await fetch('/api/menus', { credentials: 'include', headers });
      if (!res.ok) throw new Error('Failed to fetch menus');
      const data = await res.json();
      this.menus = Array.isArray(data) ? data : (data.data ?? []);
    },
    async fetchWeek(id) {
      const headers = {};
      const token = localStorage.getItem('token');
      if (token) headers['Authorization'] = 'Bearer ' + token;

      const res = await fetch(`/api/weeks/${id}`, { credentials: 'include', headers });
      if (!res.ok) throw new Error('Failed to fetch week');
      const data = await res.json();
      return data; // { week, menus }
    },
    async updateWeek(id, payload) {
      const headers = { 'Content-Type': 'application/json' };
      const token = localStorage.getItem('token');
      if (token) headers['Authorization'] = 'Bearer ' + token;

      const res = await fetch(`/api/weeks/${id}`, {
        method: 'PUT',
        credentials: 'include',
        headers,
        body: JSON.stringify(payload),
      });
      const body = await res.json().catch(() => null);
      return { status: res.status, body };
    },
    async createWeek(payload) {
      const headers = { 'Content-Type': 'application/json' };
      const token = localStorage.getItem('token');
      if (token) headers['Authorization'] = 'Bearer ' + token;

      const res = await fetch('/api/weeks', {
        method: 'POST',
        credentials: 'include',
        headers,
        body: JSON.stringify(payload),
      });
      const body = await res.json().catch(() => null);
      return { status: res.status, body };
    },
  },
});
