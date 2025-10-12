// admin-only routes (requires authenticated admin user)
import AdminMainView from '@/views/admin/AdminMainView.vue'
import MenuAdminView from '@/views/admin/MenuAdminView.vue'
import WeeksAdminView from '@/views/admin/WeeksAdminView.vue'
import AddNewWeek from '@/views/admin/AddNewWeek.vue'
import EditWeekView from '@/views/admin/EditWeekView.vue'
import AdminUsersView from '@/views/admin/AdminUsersView.vue'
import MenuHandoutView from '@/views/admin/MenuHandoutView.vue'
import TableAdminView from '@/views/admin/TableAdminView.vue'
import ModifyReservationView from '@/views/admin/ModifyReservationView.vue'

export const adminRoutes = [
  {
    path: '/admin',
    name: 'admin',
    component: AdminMainView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/menu',
    name: 'admin-menu',
    component: MenuAdminView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/weeks',
    name: 'admin-weeks',
    component: WeeksAdminView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/weeks/create',
    name: 'admin-weeks-create',
    component: AddNewWeek,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/weeks/edit/:id',
    name: 'admin-weeks-edit',
    component: EditWeekView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: AdminUsersView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/menu-handout',
    name: 'admin-menu-handout',
    component: MenuHandoutView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/admin/tables',
    name: 'admin-tables',
    component: TableAdminView,
    meta: { requiresAdmin: true, auth: true }
  },
  {
    path: '/reservations/modify/:id',
    name: 'modify-reservation',
    component: ModifyReservationView,
    meta: { auth: true, requiresAdmin: true }
  }
]
