import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from '@/components/LoginPage.vue';
import LandingPage from '@/components/LandingPage.vue';
import DashboardPage from '@/components/DashboardPage.vue';
import QuizPage from '@/components/QuizPage.vue';
import QuestionManagementPage from '@/components/QuestionManagementPage.vue';

const routes = [
    {
        path: '/',
        name: 'Landing',
        component: LandingPage
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: DashboardPage
    },
    {
        path: '/quiz',
        name: 'Quiz',
        component: QuizPage
    },
    {
        path: '/questions',
        name: 'QuestionManagement',
        component: QuestionManagementPage
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

export default router;

