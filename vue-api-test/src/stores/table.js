import {defineStore} from "pinia";

export const useTableStore = defineStore("table", {
    state: () => ({
        items: [],
    }),
    actions: {
        async fetchTables() {
            const response = await fetch("/api/tables", {
            });
            this.items = await response.json();
            console.log('Tables fetched:', this.items.value);
            return this.items;
        },
        async createTable(formData) {
            const res = await fetch("/api/tables", {
                method: "POST",
                headers: {
                    Authorization: "Bearer " + localStorage.getItem("token"),
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(formData),
            });
            const data = await res.json().catch(() => ({}));
            if (data.errors) return {errors: data.errors};
            const created = data.data ? data.data : data;
            this.items.push(created);
            return created;
        },
        async updateTable(id, payload) {
            const res = await fetch(`/api/tables/${id}`, {
                method: "PUT",
                headers: {
                    Authorization: "Bearer " + localStorage.getItem("token"),
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (data.errors) return {errors: data.errors};
            const updated = data.data ? data.data : data;
            this.items = this.items.map(i => (i.id === updated.id ? updated : i));
            return updated;
        },
    }
});