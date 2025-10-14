import { defineStore } from "pinia";

export const useMenuStore = defineStore("menus", {
  state: () => ({
    items: [],
  }),
  actions: {
    async fetchMenuItems() {
      const response = await fetch("/api/menus");
      this.items = await response.json();
    },
    async createMenuItem(formData) {
        const res = await fetch("/api/menus", {
            method: "POST",
            headers: {
            Authorization: "Bearer " + localStorage.getItem("token"),
            'Content-Type': 'application/json',
            Accept: 'application/json',
            },
            body: JSON.stringify(formData),
        });

        const data = await res.json();

        if (data.errors) {
            return { errors: data.errors };
        }

        // ha a backend { message, data }-t ad vissza
        const newItem = data.data ? data.data : data;

        this.items.push(newItem);

  return newItem; // visszaadjuk az új rekordot ID-val együtt
  },
    async createOrder(items) {
      const token = localStorage.getItem('token');
      // normalize payload: allow passing either an array or an object { items: [...] }
      let payload;
      if (Array.isArray(items)) {
        payload = { items };
      } else if (items && Array.isArray(items.items)) {
        payload = items;
      } else {
        // fallback: try to coerce
        payload = { items: [] };
      }

      try {
        const res = await fetch('/api/orders', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
          body: JSON.stringify(payload),
        });

        if (!res.ok) {
          // try to parse JSON error body for clearer message
          let bodyText = null;
          try {
            const json = await res.json();
            bodyText = JSON.stringify(json);
          } catch (e) {
            bodyText = await res.text().catch(() => String(e));
          }
          throw new Error(`Order creation failed: ${res.status} ${bodyText}`);
        }

        return await res.json();
      } catch (err) {
        console.error('createOrder error:', err);
        throw err;
      }
    },
    async updateMenuItem(id, payload) {
      const res = await fetch(`/api/menus/${id}`, {
        method: 'PUT',
        headers: {
        Authorization: 'Bearer ' + localStorage.getItem('token'),
        'Content-Type': 'application/json',
        Accept: 'application/json',
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (data.errors) return { errors: data.errors };
      const updated = data.data ? data.data : data;
      this.items = this.items.map(i => (i.id === updated.id ? updated : i));
      return updated;
    },

    async deleteMenuItem(id) {
      const res = await fetch(`/api/menus/${id}`, {
        method: 'DELETE',
        headers: {
        Authorization: 'Bearer ' + localStorage.getItem('token'),
        Accept: 'application/json',
        },
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        return { errors: data.errors || { server: 'Törlés sikertelen' } };
      }
      // remove locally
      this.items = this.items.filter(i => i.id !== id);
      return { success: true };
    },
  },
});