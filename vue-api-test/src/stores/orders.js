import { defineStore } from "pinia";

export const useOrdersStore = defineStore("orders", {
  state: () => ({
    orders: [],
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
            const newOrder = await res.json();
            this.orders.push(newOrder);
            return newOrder;
        }
    }
});