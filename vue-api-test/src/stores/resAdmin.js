import { defineStore } from "pinia";

export const useResAdminStore = defineStore("resAdmin", {
    state: () => ({
        unconfirmedCount: 0,
        reservations: [],
    }),
    actions: {
        async fetchReservations() {
            try {
                const token = localStorage.getItem('token') || '';
                const response = await fetch("/api/res-admin", {
                    headers: { 'Authorization': token ? `Bearer ${token}` : '' },
                });
                if (!response.ok) {
                    const errText = await response.text().catch(() => '');
                    throw new Error(`Failed to fetch reservations: ${response.status} ${errText}`);
                }
                const data = await response.json();
                // backend returns array of reservations
                this.reservations = Array.isArray(data) ? data : (data.data || []);
                return this.reservations;
            } catch (e) {
                console.error('fetchReservations error', e);
                this.reservations = [];
                throw e;
            }
        },
        async completeReservation(reservationId) {
            const response = await fetch(`/api/res-admin/${encodeURIComponent(reservationId)}/complete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
            });
            const data = await response.json(); 
            if (!response.ok) {
                throw new Error(data.message || 'Failed to complete reservation');
            }
            return data;
        },
        async fetchReservation(reservationId) {
            const response = await fetch(`/api/res-admin/${encodeURIComponent(reservationId)}`, {
                headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
            });
            // try to parse json if present
            let data = {};
            try { data = await response.json(); } catch (e) { data = {}; }
            if (!response.ok) {
                throw new Error(data.message || `Failed to fetch reservation: ${response.status}`);
            }
            return data;
        },
        async deleteReservation(reservationId) {
            const response = await fetch(`/api/res-admin/${encodeURIComponent(reservationId)}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
            });
            // DELETE may return 204 No Content -> avoid failing to parse
            let data = {};
            try { data = await response.json(); } catch (e) { data = {}; }
            if (!response.ok) {
                throw new Error(data.message || `Failed to delete reservation: ${response.status}`);
            }
            // Refresh list if present
            try { await this.fetchReservations(); } catch (e) { /* ignore */ }
            return data;
        },
        async deleteUnconfirmedReservations() {
            try {
                const token = localStorage.getItem('token') || '';
                const response = await fetch("/api/res-admin/delete-unconfirmed-reservations", {
                    method: "DELETE",
                    headers: { 'Content-Type': 'application/json', 'Authorization': token ? `Bearer ${token}` : '' },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    // preserve error message if provided
                    throw new Error(data.message || 'Failed to delete unconfirmed reservations');
                }
                // Backend doesn't return deleted_count — set to 0 after successful deletion
                this.unconfirmedCount = 0;
                // Refresh reservations list to reflect deletions
                try { await this.fetchReservations(); } catch (_) { /* ignore */ }
                return data;
            } catch (e) {
                console.error('deleteUnconfirmedReservations error', e);
                throw e;
            }
        },
        async fetchUnconfirmedCount() {
            try {
                const response = await fetch("/api/res-admin/unconfirmed-count", {
                    headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
                });
                if (!response.ok) {
                    const err = await response.text().catch(() => '');
                    throw new Error(`Failed to fetch unconfirmed count: ${response.status} ${err}`);
                }
                const data = await response.json();
                // Backend returns { unconfirmed_count: <number> }
                this.unconfirmedCount = data.unconfirmed_count ?? data.count ?? 0;
            } catch (e) {
                console.error('fetchUnconfirmedCount error', e);
                this.unconfirmedCount = 0;
            }
            console.log('Unconfirmed reservations count:', this.unconfirmedCount);
            return this.unconfirmedCount; 
        },
        async updateReservation(reservationId, updateData) {
            const response = await fetch(`/api/res-admin/${encodeURIComponent(reservationId)}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
                body: JSON.stringify(updateData)
            });
            const data = await response.json(); 
            if (!response.ok) {
                throw new Error(data.message || 'Failed to update reservation');
            }
            return data;
    
        },
        async createReservation(reservationData) {
            const response = await fetch("/api/res-admin", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
                body: JSON.stringify(reservationData)
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Failed to create reservation');
            }
            return data;
        }
    }
}); 