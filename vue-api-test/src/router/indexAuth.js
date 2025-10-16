// auth-only routes (requires authenticated user, non-admin)
import CreateView from '../views/posts/CreateView.vue'
import UpdateView from '@/views/posts/UpdateView.vue'
import UserDataView from '@/views/auth/UserDataView.vue'
import SubscriptionView from '@/views/Subscription.vue'
import PayForSubscription from '@/views/PayForSubscription.vue'
import EditSubscriptionsView from '@/views/EditSubscriptionsView.vue'
import UserOrderView from '@/views/UserOrderView.vue'
import UserOrderStatus from '@/views/UserOrderStatus.vue'

export const authRoutes = [
  {
    path: '/create',
    name: 'create',
    component: CreateView,
    meta: { auth: true }
  },
  {
    path: '/posts/update/:id/',
    name: 'update',
    component: UpdateView,
    meta: { auth: true }
  },
  {
    path: '/userdata',
    name: 'userdata',
    component: UserDataView,
    meta: { auth: true }
  },
  {
    path: '/userdata/:id',
    name: 'userdata-admin',
    component: UserDataView,
    meta: { auth: true }
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
    path: '/user/orders',
    name: 'user-orders',
    component: UserOrderView,
    meta: { auth: true }
  },
  {
    path: '/user/orders/:id',
    name: 'user-order-status',
    component: UserOrderStatus,
    meta: { auth: true }
  },
  {
    path: '/user/change-order/:id',
    name: 'user-change-order',
    component: UserOrderView,
    meta: { auth: true }
  }
]
