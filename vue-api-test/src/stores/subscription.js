import { defineStore } from "pinia";
import { useAuthStore } from "./auth";

export const useSubscriptionStore = defineStore("subscription", {
    state: () => ({
        user: null,
        subscriptions: []
    }),
    actions: {
        async fetchSubscriptions() {
            const token = localStorage.getItem('token');
            try {
                const res = await fetch('/api/subscriptions/user-week', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) throw new Error('Failed to fetch subscriptions');
                const data = await res.json();
                // controller returns { subscriptions: [...] }
                this.subscriptions = data.subscriptions ?? data;
            } catch (e) {
                console.error('fetchSubscriptions error', e);
            }
        },
        async fetchUserWeek() {
            const token = localStorage.getItem('token');
            try {
                const res = await fetch('/api/subscriptions/user-week', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (res.status === 404) return null; // no subscription for next week
                if (!res.ok) throw new Error('Failed to fetch user-week subscription');
                const data = await res.json();
                // returns { subscription, choices }
                return data;
            } catch (e) {
                console.error('fetchUserWeek error', e);
                throw e;
            }
        },
        async createSubscription(weekId) {
            const authStore = useAuthStore();
            // auth store may not expose token directly; fall back to localStorage
            const token = authStore.token ?? localStorage.getItem('token');

            try {
                const response = await fetch("/api/subscriptions", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ week_id: weekId })
                });

                if (!response.ok) {
                    let body = null;
                    try {
                        body = await response.json();
                    } catch (e) {
                        body = await response.text();
                    }

                    if (response.status === 401) {
                        console.error('Unauthorized when creating subscription:', body);
                        throw new Error('Unauthorized');
                    }

                    console.error('Failed to create subscription, status:', response.status, 'body:', body);
                    throw new Error('Failed to create subscription');
                }

                const data = await response.json();
                this.subscriptions.push(data);
            } catch (error) {
                console.error('Error creating subscription:', error);
            }
        },
        

        async updateSubscription(id, weekId, choices = []) {
            const token = localStorage.getItem('token');
            try {
                const res = await fetch(`/api/subscriptions/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: JSON.stringify({ week_id: weekId, choices })
                });
                if (!res.ok) {
                    let body = null;
                    try { body = await res.json(); } catch(e) { body = await res.text(); }
                    throw new Error('Update subscription failed: ' + (body && body.message ? body.message : JSON.stringify(body)));
                }
                const data = await res.json();
                // optionally refresh subscriptions list
                await this.fetchSubscriptions();
                return data;
            } catch (e) {
                console.error('updateSubscription error', e);
                throw e;
            }
        },
        async deleteSubscription(id) {
            const token = localStorage.getItem('token');
            try {
                const res = await fetch(`/api/subscriptions/${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                if (!res.ok) {
                    let body = null;
                    try { body = await res.json(); } catch(e) { body = await res.text(); }
                    throw new Error('Delete subscription failed: ' + (body && body.message ? body.message : JSON.stringify(body)));
                }
                // refresh list
                await this.fetchSubscriptions();
                return true;
            } catch (e) {
                console.error('deleteSubscription error', e);
                throw e;
            }
        },
        async searchUserByEmail(email) {
            const token = localStorage.getItem('token');
            try {
                const res = await fetch(`/api/auth/search-user?query=${encodeURIComponent(email)}`, {
                    headers: {
                        'Authorization': 'Bearer ' + token
                    }
                });
                if (res.status === 404) return null; // user not found
                if (!res.ok) throw new Error('Failed to search user');
                const data = await res.json();
                return data.user ?? data;
            } catch (e) {
                console.error('searchUserByEmail error', e);
                throw e;
            }
        }
    }
});
