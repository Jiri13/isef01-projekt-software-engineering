import { createRouter, createWebHistory } from 'vue-router';
import LandingPage from '@/components/LandingPage.vue';
import DashboardPage from '@/components/DashboardPage.vue';
//import QuizPage from '@/components/QuizPage.vue';
import QuestionManagementPage from '@/components/QuestionManagementPage.vue';
import SingleplayerPage from '@/components/SingleplayerPage.vue';
import MultiplayerPage from '@/components/MultiplayerPage.vue';

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
    //{
    //    path: '/quiz',
    //    name: 'Quiz',
    //    component: QuizPage
    //},
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
    }
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
});

export default router;

