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
            const token = authStore.token;

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
