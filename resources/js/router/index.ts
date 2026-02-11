import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/pages/Welcome.vue'),
    },
    // Phase 11: Auth routes
    // Phase 12: App routes
    // Phase 12: Admin routes
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});
