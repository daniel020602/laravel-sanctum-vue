import { defineStore } from 'pinia'

export const useAdminUsersStore = defineStore('adminUsers', {
  state: () => ({
    users: [],
    isLoading: false,
  }),
  actions: {
    async fetchUsers() {
      this.isLoading = true;
      try {
        const res = await fetch('/api/auth/list-users', { headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') } });
        if (!res.ok) throw new Error('Failed to fetch users');
        const data = await res.json();
        this.users = data.users ?? [];
        return this.users;
      } finally {
        this.isLoading = false;
      }
    },
    async updateUser(id, payload) {
      const res = await fetch(`/api/auth/change-data/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        body: JSON.stringify(payload)
      });
      if (!res.ok) {
        const body = await res.json().catch(() => null);
        throw new Error(body?.message || 'Failed to update');
      }
      const data = await res.json();
      // update local list
      const idx = this.users.findIndex(u => u.id === id);
      if (idx !== -1) this.users[idx] = data.user;
      return data.user;
    },
    async toggleAdmin(id, makeAdmin) {
      const url = `/api/auth/${makeAdmin ? 'promote' : 'demote'}/${id}`;
      const res = await fetch(url, { method: 'POST', headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') } });
      if (!res.ok) throw new Error('Failed to toggle admin');
      await this.fetchUsers();
      return true;
    },
    async deleteUser(id) {
      const res = await fetch(`/api/auth/delete-user/${id}`, { method: 'DELETE', headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') } });
      if (!res.ok) throw new Error('Failed to delete');
      this.users = this.users.filter(u => u.id !== id);
      return true;
    }
  }
})
