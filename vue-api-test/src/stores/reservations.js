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
        },
        async fetchReservationById(id, reservationCode = '') {
            try {
                // Backend expects reservation_code as input() (query or body) — include as query param for GET
                const query = reservationCode ? `?reservation_code=${encodeURIComponent(reservationCode)}` : '';
                const res = await fetch(`/api/reservations/${encodeURIComponent(id)}${query}`);
                let data = {};
                try { data = await res.json(); } catch (e) { data = {}; }
                if (!res.ok) {
                    throw new Error(data.message || `Failed to fetch reservation: ${res.status}`);
                }
                return data;
            } catch (e) {
                console.error('fetchReservationById error', e);
                throw e;
            }
        },
        async deleteReservation(id, reservationCode = '') {
            try {
                // delete with reservation_code as query param so controller's $request->input('reservation_code') finds it
                const query = reservationCode ? `?reservation_code=${encodeURIComponent(reservationCode)}` : '';
                const res = await fetch(`/api/reservations/${encodeURIComponent(id)}${query}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                });
                if (!res.ok) {
                    let data = {};
                    try { data = await res.json(); } catch (_) { }
                    throw new Error(data.message || 'Failed to delete reservation');
                }
                // Remove the deleted reservation from the local state
                this.items = this.items.filter(i => i.id !== id);
            } catch (e) {
                console.error('deleteReservation error', e);
                throw e;
            }
        },
        async updateReservation(id, updateData, reservationCode = '') {
            try {
                // include reservation_code in the body so controller's $request->input('reservation_code') finds it
                const body = { ...updateData, reservation_code: reservationCode || '' };
                const res = await fetch(`/api/reservations/${encodeURIComponent(id)}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (!res.ok) {
                    console.error('updateReservation failed', data);
                    throw new Error(data.message || 'Failed to update reservation');
                }
                // Update the local item
                const updated = data.reservation ?? data;
                this.items = this.items.map(i => i.id === id ? updated : i);
                return data;
            } catch (e) {
                console.error('updateReservation error', e);
                throw e;
            }
        }
    }
});