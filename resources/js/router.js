import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const Login = () => import('./pages/Login.vue');
const AdminLayout = () => import('./layouts/AdminLayout.vue');
const Dashboard = () => import('./pages/Dashboard.vue');

const routes = [
    {
        path: '/',
        name: 'Login',
        component: Login,
        meta: { guestOnly: true }
    },
    {
        path: '/',
        component: AdminLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: Dashboard,
            },
            {
                path: 'campaigns',
                name: 'Campaigns',
                component: () => import('./pages/Campaigns.vue'),
            },
            {
                path: 'brands',
                name: 'Brands',
                component: () => import('./pages/Brands.vue'),
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('./pages/Users.vue'),
            },
            {
                path: 'companies',
                name: 'Companies',
                component: () => import('./pages/Companies.vue'),
            },
            {
                path: 'tasks',
                name: 'Tasks',
                component: () => import('./pages/Tasks.vue'),
            },
            {
                path: 'campaigns/:id',
                name: 'CampaignDetail',
                component: () => import('./pages/CampaignDetail.vue'),
            },
            {
                path: 'promotions',
                name: 'Promotions',
                component: () => import('./pages/Promotions.vue'),
            },
            {
                path: 'promotions/:id',
                name: 'PromotionDetail',
                component: () => import('./pages/PromotionDetail.vue'),
            },
            {
                path: 'products',
                name: 'Products',
                component: () => import('./pages/Products.vue'),
            },
            {
                path: 'products/:id',
                name: 'ProductDetail',
                component: () => import('./pages/ProductDetail.vue'),
            },
            {
                path: 'activity',
                name: 'ActivityLogs',
                component: () => import('./pages/ActivityLogs.vue'),
            },
            {
                path: 'export',
                name: 'ExportPage',
                component: () => import('./pages/ExportPage.vue'),
            },
            {
                path: 'settings',
                name: 'SystemSettings',
                component: () => import('./pages/SystemSettings.vue'),
            }
        ]
    },
    {
        path: '/review/:token',
        name: 'PublicReview',
        component: () => import('./pages/PublicReview.vue'),
        meta: { public: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from) => {
    if (to.meta.public) {
        return true;
    }

    const authStore = useAuthStore();
    
    // Check initial user state if not loaded for protected routes only
    if (!to.meta.guestOnly && !authStore.isAuthenticated && !authStore.user) {
        await authStore.fetchUser();
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'Login' };
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return { name: 'Dashboard' };
    }

    return true;
});

export default router;
