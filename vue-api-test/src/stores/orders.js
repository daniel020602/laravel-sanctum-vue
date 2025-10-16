import { defineStore } from "pinia";

export const useOrdersStore = defineStore("orders", {
  state: () => ({
        orders: [],
        current: null,
  }),
    actions: {
        async fetchOrders() {
            const response = await fetch("/api/orders", {
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                }
            });
            this.orders = await response.json();
            console.log("Fetched orders:", this.orders);
            return this.orders;
        },
        async createOrder(formData) {
            const res = await fetch("/api/orders", {
                method: "POST",
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData)
            });

            const payload = await res.json().catch(() => null);

            if (!res.ok) {
                // handle validation/server error consistently
                // payload may contain { errors: ... } or message
                return { errors: payload?.errors || payload?.message || 'Failed to create order' };
            }

                        // unwrap if API sends { data: order }
                        const newOrder = payload && payload.data ? payload.data : payload;

                        // update store and set current order
                        if (newOrder) {
                            this.orders.push(newOrder);
                            this.current = newOrder;
                        }

                        return newOrder;
        }
                ,
                async fetchOrder(id) {
                    if (!id) return null;
                    const res = await fetch(`/api/orders/${id}`, {
                        headers: {
                            "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                        }
                    });
                    if (!res.ok) {
                        const payload = await res.json().catch(() => null);
                        throw new Error(payload?.message || 'Failed to fetch order');
                    }
                    const payload = await res.json().catch(() => null);
                    const order = payload && payload.data ? payload.data : payload;
                    this.current = order;
                    console.log("Fetched order:", order);
                    return order;
                }
    }
});