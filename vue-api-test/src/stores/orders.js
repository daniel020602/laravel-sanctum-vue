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
                },
        async updateOrder(id, formData) {
            const res = await fetch(`/api/orders/${id}`, {
                method: "PUT",
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData)
            });

            const payload = await res.json().catch(() => null);

            if (!res.ok) {
                return { errors: payload?.errors || payload?.message || 'Failed to update order' };
            }

            const updatedOrder = payload && payload.data ? payload.data : payload;

            if (updatedOrder) {
                const index = this.orders.findIndex(order => order.id === id);
                if (index !== -1) {
                    this.orders.splice(index, 1, updatedOrder);
                }
                this.current = updatedOrder;
            }

            return updatedOrder;
        },
        async deleteOrder(id) {
            const res = await fetch(`/api/orders/${id}`, {
                method: "DELETE",
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                }
            });

            if (!res.ok) {
                const payload = await res.json().catch(() => null);
                return { message: payload?.message || 'Failed to delete order' };
            }

            this.orders = this.orders.filter(order => order.id !== id);
            if (this.current && this.current.id === id) {
                this.current = null;
            }

            return { message: 'Order deleted successfully' };
        },
        async fetchCurrentUserOrders() {
            const response = await fetch("/api/orders/current-order", {
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                }
            });
            const data = await response.json();
            return data;
        }   
        ,
        async payOrder(id, stripeToken = 'tok_visa') {
            if (!id) throw new Error('order id required');
            const res = await fetch(`/api/orders/${id}/pay`, {
                method: 'POST',
                headers: {
                    'Authorization': localStorage.getItem('token') ? 'Bearer ' + localStorage.getItem('token') : '',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ stripeToken })
            });

            if (!res.ok) {
                const body = await res.json().catch(() => null);
                throw new Error(body?.message || 'Payment failed');
            }

            const data = await res.json().catch(() => null);
            if (data && data.order) {
                this.current = data.order;
            }
            return data;
        },
        async fetchInProgressOrders() {
            const response = await fetch("/api/orders/in-progress", {
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                }
            });
            const data = await response.json();
            this.orders = data;
            console.log("Fetched in-progress orders:", this.orders);
            return this.orders;
        },
        async changeStatus(id, status) {
            const res = await fetch(`/api/orders/${id}/status`, {
                method: "POST",
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ status })
            });
            const payload = await res.json().catch(() => null);

            if (!res.ok) {
                const message = payload?.message || payload || `Failed to change order status (HTTP ${res.status})`;
                console.error('changeStatus failed', { status: res.status, payload });
                throw new Error(message);
            }

            const updatedOrder = payload && payload.data ? payload.data : payload;

            if (updatedOrder) {
                const index = this.orders.findIndex(order => order.id === id);
                if (index !== -1) {
                    this.orders.splice(index, 1, updatedOrder);
                }
                this.current = updatedOrder;
            }
            return updatedOrder;
        },
        async markAsPaid(id) {
            if (!id) throw new Error('order id required');

            const res = await fetch(`/api/orders/${id}`, {
                method: "PUT",
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ is_paid: true })
            });

            const payload = await res.json().catch(() => null);

            if (!res.ok) {
                // return a consistent error shape (caller can check for .errors or .message)
                return { errors: payload?.errors || payload?.message || `Failed to mark order ${id} as paid` };
            }

            const updatedOrder = payload && payload.data ? payload.data : payload;

            if (updatedOrder) {
                const index = this.orders.findIndex(o => o.id === id);
                if (index !== -1) this.orders.splice(index, 1, updatedOrder);
                if (this.current && this.current.id === id) this.current = updatedOrder;
            }

            return updatedOrder;
        },
        async fetchOrderStatistics() {
            const res = await fetch("/api/orders/statistics", {
                headers: {
                    "Authorization": localStorage.getItem("token") ? "Bearer " + localStorage.getItem("token") : "",
                }
            });
            const data = await res.json();
            this.orderStatistics = data;
            console.log("Fetched order statistics:", this.orderStatistics);
            return this.orderStatistics;
        }
    }
});