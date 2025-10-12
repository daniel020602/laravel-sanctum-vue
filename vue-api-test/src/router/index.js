import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import RegisterView from '../views/auth/Registerview.vue'
import LoginView from '../views/auth/LoginView.vue'
import PostView from '../views/posts/PostView.vue'
import Menu from '@/views/Menu.vue'
import NewReservationView from '@/views/NewReservationView.vue'
import ConfirmReservationView from '@/views/ConfirmReservationView.vue'
import SearchUserReservation from '@/views/SearchUserReservation.vue'
import UserModifyReservation from '@/views/UserModifyReservation.vue'
import { useAuthStore } from '../stores/auth.js'

// routes split into files for clarity
import { authRoutes } from './indexAuth'
import { adminRoutes } from './indexAdmin'

const publicRoutes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guest: true }
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true }
  },
  {
    path: '/posts/:id',
    name: 'post',
    component: PostView,
  },
  {
    path: '/menu',
    name: 'menu',
    component: Menu
  },
  {
    path: '/reservations/new',
    name: 'new-reservation',
    component: NewReservationView,
  },
  {
    path: '/reservations/confirm',
    name: 'confirm-reservation',
    component: ConfirmReservationView,
  },
  {
    path: '/reservations/search',
    name: 'search-reservation',
    component: SearchUserReservation,
  },
  {
    path: '/reservations/modify-user/:id',
    name: 'modify-user-reservation',
    component: UserModifyReservation,
    // pass reservationData from navigation state (history.state)
    props: (route) => ({ reservationData: (route && route.state && route.state.reservation) ? route.state.reservation : null })
  }
]

const routes = [...publicRoutes, ...authRoutes, ...adminRoutes]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach(async (to, from) => {
  const authStore = useAuthStore()
  await authStore.getUser()
  if (authStore.user && to.meta.guest) {
    return { name: 'home' }
  }
  if (!authStore.user && to.meta.auth) {
    return { name: 'login' }
  }
  if (to.meta.requiresAdmin && (!authStore.user || !authStore.user.is_admin)) {
    return { name: 'home' }
  }
})

export default router

