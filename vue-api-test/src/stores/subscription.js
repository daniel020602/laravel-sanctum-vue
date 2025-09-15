import { defineStore } from "pinia";
import { useAuthStore } from "./auth";

export const useSubscriptionStore = defineStore("subscription", {
    state: () => ({
        user: null,
        subscriptions: []
    }),
    actions: {
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
        }
    }
});
