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
                meta: { permission: 'campaign.view' },
            },
            {
                path: 'campaigns',
                name: 'Campaigns',
                component: () => import('./pages/Campaigns.vue'),
                meta: { permission: 'campaign.view' },
            },
            {
                path: 'brands',
                name: 'Brands',
                component: () => import('./pages/Brands.vue'),
                meta: { permission: 'brand.view' },
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('./pages/Users.vue'),
                meta: { permission: 'user.view' },
            },
            {
                path: 'roles',
                name: 'Roles',
                component: () => import('./pages/Roles.vue'),
                meta: { role: 'Super Admin' },
            },
            {
                path: 'companies',
                name: 'Companies',
                component: () => import('./pages/Companies.vue'),
                meta: { permission: 'company.view' },
            },
            {
                path: 'tasks',
                name: 'Tasks',
                component: () => import('./pages/Tasks.vue'),
                meta: { permission: 'task.view' },
            },
            {
                path: 'performance-reports',
                name: 'PerformanceReports',
                component: () => import('./pages/PerformanceReports.vue'),
                meta: { permission: 'performance-report.view' },
            },
            {
                path: 'campaigns/:id',
                name: 'CampaignDetail',
                component: () => import('./pages/CampaignDetail.vue'),
                meta: { permission: 'campaign.view' },
            },
            {
                path: 'promotions',
                name: 'Promotions',
                component: () => import('./pages/Promotions.vue'),
                meta: { permission: 'promotion.view' },
            },
            {
                path: 'promotions/:id',
                name: 'PromotionDetail',
                component: () => import('./pages/PromotionDetail.vue'),
                meta: { permission: 'promotion.view' },
            },
            {
                path: 'products',
                name: 'Products',
                component: () => import('./pages/Products.vue'),
                meta: { permission: 'product.view' },
            },
            {
                path: 'products/:id',
                name: 'ProductDetail',
                component: () => import('./pages/ProductDetail.vue'),
                meta: { permission: 'product.view' },
            },
            {
                path: 'activity',
                name: 'ActivityLogs',
                component: () => import('./pages/ActivityLogs.vue'),
                meta: { permission: 'activity.view' },
            },
            {
                path: 'export',
                name: 'ExportPage',
                component: () => import('./pages/ExportPage.vue'),
                meta: { permission: 'report.export' },
            },
            {
                path: 'settings',
                name: 'SystemSettings',
                component: () => import('./pages/SystemSettings.vue'),
                meta: { permission: 'settings.view' },
            },
            {
                path: 'forbidden',
                name: 'AccessDenied',
                component: () => import('./pages/AccessDenied.vue'),
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

    if (to.meta.permission && !authStore.can(to.meta.permission)) {
        return { name: 'AccessDenied' };
    }

    if (to.meta.role && !authStore.hasRole(to.meta.role)) {
        return { name: 'AccessDenied' };
    }

    return true;
});

export default router;
