import { defineStore } from "pinia";

export const useReservationStore = defineStore("reservation", {
    state: () => ({
        items: [],
    }),
    actions: {
        async fetchReservations() {
            const response = await fetch("/api/reservations");
            this.items = await response.json();
            console.log("Fetched reservations:", this.items);
            return this.items;
        },
        async createReservation(formData) {
            const res = await fetch("/api/reservations", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData),
            });
            const data = await res.json().catch(() => ({}));
            return data;
        }
    }
});