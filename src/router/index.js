import { createRouter, createWebHistory } from 'vue-router';
import LandingPage from '@/components/LandingPage.vue';
import DashboardPage from '@/components/DashboardPage.vue';
import QuestionManagementPage from '@/components/QuestionManagementPage.vue';
import SingleplayerPage from '@/components/SingleplayerPage.vue';
import MultiplayerPage from '@/components/MultiplayerPage.vue';
import QuizManagementPage from '@/components/QuizManagementPage.vue';
import UserManagementPage from '@/components/UserManagementPage.vue';

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
        path: '/questions',
        name: 'QuestionManagement',
        component: QuestionManagementPage
    },
    {
        path: '/singleplayer',
        name: 'Singleplayer',
        component: SingleplayerPage
    }
    ,{
        path: '/multiplayer/:id',
        name: 'Multiplayer',
        component: MultiplayerPage,
        props: true
    },
    {
        path: '/quizmanagement',
        name: 'QuizManagement',
        component: QuizManagementPage
    },
    {
        path: '/usermanagement',
        name: 'UserManagement',
        component:UserManagementPage
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

export default router;

