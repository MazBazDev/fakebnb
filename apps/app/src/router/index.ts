import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/listings',
      name: 'listings',
      component: () => import('@/views/ListingsIndexView.vue'),
    },
    {
      path: '/listings/:id',
      name: 'listing-detail',
      component: () => import('@/views/ListingDetailView.vue'),
      props: true,
    },
    {
      path: '/bookings',
      name: 'bookings',
      component: () => import('@/views/BookingsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/messages',
      name: 'messages',
      component: () => import('@/views/MessagesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/messages/:id',
      name: 'message-thread',
      component: () => import('@/views/MessageThreadView.vue'),
      meta: { requiresAuth: true },
      props: true,
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/ProfileView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/dashboard',
      redirect: '/host',
    },
    {
      path: '/host',
      name: 'host-dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
    },
    {
      path: '/host/cohosts',
      name: 'host-cohosts',
      component: () => import('@/views/CohostsView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
    },
    {
      path: '/host/listings/new',
      name: 'host-listing-create',
      component: () => import('@/views/ListingCreateView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
    },
    {
      path: '/host/listings',
      name: 'host-listings',
      component: () => import('@/views/MyListingsView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
    },
    {
      path: '/host/bookings',
      name: 'host-bookings',
      component: () => import('@/views/HostBookingsView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
    },
    {
      path: '/host/messages',
      name: 'host-messages',
      component: () => import('@/views/MessagesView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
    },
    {
      path: '/host/messages/:id',
      name: 'host-message-thread',
      component: () => import('@/views/MessageThreadView.vue'),
      meta: { requiresAuth: true, layout: 'host' },
      props: true,
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'listings' }
  }

  return true
})

export default router
