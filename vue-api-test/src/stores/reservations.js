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
            try {
                const res = await fetch('/api/reservations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
                    body: JSON.stringify(formData)
                });
                const data = await res.json();
                if (!res.ok) {
                    console.error('createReservation failed', data);
                    throw new Error(data.message || 'Failed to create reservation');
                }
                const created = data.reservation ? data.reservation : (data.data ? data.data : data);
                // push to items if API returns an object or array
                this.items = Array.isArray(this.items) ? [ ...this.items, created ] : [ created ];
                return created;
            } catch (e) {
                console.error('createReservation error', e);
                throw e;
            }
        },

        async confirmReservation(reservationId, reservationCode) {
            try {
                const token = localStorage.getItem('token') || '';
                const res = await fetch(`/api/reservations/${encodeURIComponent(reservationId)}/confirm`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': token ? `Bearer ${token}` : '' },
                    body: JSON.stringify({ reservation_code: reservationCode })
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Failed to confirm reservation');
                }
                // optionally update local item
                const updated = data.reservation ?? data;
                if (updated && updated.id) {
                    this.items = this.items.map(i => i.id === updated.id ? updated : i);
                }
                return data;
            } catch (e) {
                console.error('confirmReservation error', e);
                throw e;
            }
        }
    }
});