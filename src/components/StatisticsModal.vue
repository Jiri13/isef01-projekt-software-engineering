<template>
    <div class="modal">
        <div class="modal-content">
            <h1 style="text-align: center;">Statistik</h1>
            <table>
                <tbody>
                    <tr>
                        <td>Rang</td>
                        <td>{{getRank()}}</td>
                    </tr>
                    <tr>
                        <td>Beantwortete Fragen</td>
                        <td>{{getUserStatsFromID(this.sessionStore.userID).answeredQuestions}}</td>
                    </tr>
                    <tr>
                        <td>Richtige Antworten</td>
                        <td>{{getUserStatsFromID(this.sessionStore.userID).correctAnswers}}</td>
                    </tr>
                    <tr>
                        <td>Falsche Antworten</td>
                        <td>{{getUserStatsFromID(this.sessionStore.userID).wrongAnswers}}</td>
                    </tr>
                    <tr>
                        <td>Absolvierte Quiz</td>
                        <td>{{getNumberOfCompletedQuizzes()}}</td>
                    </tr>
                    <tr>
                        <td>Beste Frage</td>
                        <td>{{getBestQuestionText()}}</td>
                    </tr>
                    <tr>
                        <td>Schlechteste Frage</td>
                        <td>{{getWorstQuestionText()}}</td>
                    </tr>

                </tbody>
            </table>
            <div style="display:flex;gap:12px;margin-top:16px;">
                <button type="button" class="btn btn-secondary" @click="close">Schließen</button>
            </div>
        </div>
    </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import users from '../files/users.json'
import router from '@/router/index'
import axios from 'axios'

export default {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    data() {
        const sessionStore = useSessionStore()
        return{
            sessionStore,
            users,
            questions
        }
    },
    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },
        getUserStatsFromID(ID) {
            const foundUser = (this.users || []).find(user => user.userID === ID)
            return foundUser?.stats || { correctAnswers: 0, wrongAnswers: 0 };
        },
        getUserCorrectRatio() {
            return this.getUserStatsFromID(this.sessionStore.userID).correctAnswers / (this.getUserStatsFromID(this.sessionStore.userID).answeredQuestions || 1) * 100;
        },
        getBestQuestionText(){
            return questions[this.getUserStatsFromID(this.sessionStore.userID).bestQuestion].question_text;
        },
        getWorstQuestionText(){
            return questions[this.getUserStatsFromID(this.sessionStore.userID).worstQuestion].question_text;
        },
        getNumberOfCompletedQuizzes(){
            return this.getUserStatsFromID(this.sessionStore.userID).completedQuizzes;
        },
        getRank() {
            const currentUserCorrectRatio = this.getUserCorrectRatio();
            var scoreArray = [];
            users.forEach(user => {
                scoreArray.push((user.stats.correctAnswers / user.stats.answeredQuestions) * 100)
            });
            const sortedScoreArray = scoreArray.sort((a, b) => b - a);
            const rankInArray = sortedScoreArray.indexOf(currentUserCorrectRatio);
            return rankInArray + 1;
        }
    }
}
</script>

<style scoped>
tbody tr {
    border-bottom: 1px solid #dddddd;
}

td {
    padding: 12px 15px;
    text-align: left;
}

td:nth-of-type(odd) {
    font-weight: 600;
}

tbody tr:nth-of-type(even) {
    background-color: #c4dcf5;
}

tbody tr:last-of-type {
    border-bottom: none;
}
</style>