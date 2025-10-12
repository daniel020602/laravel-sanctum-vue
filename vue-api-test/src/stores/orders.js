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
    }
});