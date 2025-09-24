import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import RegisterView from '../views/auth/Registerview.vue'
import LoginView from '../views/auth/LoginView.vue'
import { useAuthStore } from '../stores/auth.js'
import CreateView from '../views/posts/CreateView.vue'
import PostView from '../views/posts/PostView.vue'
import UpdateView from '@/views/posts/UpdateView.vue'
import UserDataView from '@/views/auth/UserDataView.vue'
import Menu from '@/views/Menu.vue'
import AdminMainView from '@/views/admin/AdminMainView.vue'
import MenuAdminView from '@/views/admin/MenuAdminView.vue'
import WeeksAdminView from '@/views/admin/WeeksAdminView.vue'
import AddNewWeek from '@/views/admin/AddNewWeek.vue'
import EditWeekView from '@/views/admin/EditWeekView.vue'
import SubscriptionView from '@/views/Subscription.vue'
import PayForSubscription from '@/views/PayForSubscription.vue'
import EditSubscriptionsView from '@/views/EditSubscriptionsView.vue'
import AdminUsersView from '@/views/admin/AdminUsersView.vue'
import MenuHandoutView from '@/views/admin/MenuHandoutView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,

    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta:{guest:true}
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta:{guest:true}
    },
    {
      path: '/create',
      name: 'create',
      component: CreateView,
      meta:{auth:true}
    },
    {
    path: '/posts/:id',
    name: 'post',
    component: PostView,
    },
    {
      path: '/posts/update/:id/',
      name: 'update',
      component: UpdateView,
      meta:{auth:true}
    },
    {
      path: '/userdata',
      name: 'userdata',
      component: UserDataView,
      meta:{auth:true}
    },
    {
      path: '/userdata/:id',
      name: 'userdata-admin',
      component: UserDataView,
      meta:{auth:true}
    },
    {
      path: '/menu',
      name: 'menu',
      component: Menu
    },
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
      path: '/subscription',
      name: 'subscription',
      component: SubscriptionView,
      meta: { auth: true }
    },
    {
      path: '/pay-for-subscription/:id',
      name: 'pay-for-subscription',
      component: PayForSubscription,
      meta: { auth: true }
    },
    {
      path: '/subscription-edit/:id',
      name: 'subscription-edit',
      component: EditSubscriptionsView,
      meta: { auth: true }
    },
    {
      path: '/admin/menu-handout',
      name: 'admin-menu-handout',
      component: MenuHandoutView,
      meta: { requiresAdmin: true, auth: true }
    }
  ],
})
router.beforeEach(async (to, from) => 
{
  const authStore = useAuthStore()
  await authStore.getUser()
  if(authStore.user && to.meta.guest)
  {
    return {name:'home'}
  }
  if(!authStore.user && to.meta.auth)
  {
    return {name:'login'}
  }
  if (to.meta.requiresAdmin && (!authStore.user || !authStore.user.is_admin)) {
    return { name: 'home' } // or { path: '/' }
  }


})
export default router
