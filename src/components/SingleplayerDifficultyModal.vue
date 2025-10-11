<template>
    <div class="modal">
        <div class="modal-content">
            <h1 style="text-align: center;">Einzelspieler</h1>
            <div id="sp-no-game-running" v-if="!singleplayerStore.hasStarted">
                <div id="sp-modal-quiz">
                    <h2 style="margin-bottom: 24px;">Quiz auswählen:</h2>
                    <div style="height: 250px; overflow-y: auto; margin-bottom: 24px;">
                        <div v-for="quiz in getQuizzesFromUserQuizCatalogue()" class="card" :class="{ 'selected': quiz.quizID === selectedQuizID && this.mode == 'quiz'}"
                            style="border: 2px solid #007bff; margin-bottom: 16px; position: relative; ">
                            <div style="cursor: pointer;" @click.prevent="selectQuiz(quiz)">
                                <div
                                    style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start;">
                                    <div>
                                        <h3>{{ quiz.title }} <small style="font-weight: normal; color: #666;">Ersteller:
                                                {{ getUserNameFromQuizUserID(quiz.userID) }}</small></h3>
                                        <p>{{ quiz.quiz_description }}</p>
                                        <p>Studiengang: {{ quiz.category }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="sp-modal-catalogue">
                    <h2 style="margin-bottom: 24px;">Fragen aus dem gesamten Katalog:</h2>
                    <div class="form-group">
                        <label class="form-label">Wählen Sie den Schwierigkeitsgrad:</label>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                            <label class="difficulty-easy selected-difficulty"
                                style="cursor: pointer; padding: 16px; border: 2px solid #28a745; border-radius: 8px; text-align: center;"
                                @click.prevent="selectDifficulty($event)">
                                <input type="radio" name="singlePlayerDifficulty" value="easy" checked
                                    style="display: none;">
                                <div style="font-weight: 600;">🟢 Leicht</div>
                            </label>
                            <label class="difficulty-medium"
                                style="cursor: pointer; padding: 16px; border: 2px solid #ffc107; border-radius: 8px; text-align: center;"
                                @click.prevent="selectDifficulty($event)">
                                <input type="radio" name="singlePlayerDifficulty" value="medium" style="display: none;">
                                <div style="font-weight: 600;">🟡 Mittel</div>
                            </label>
                            <label class="difficulty-hard"
                                style="cursor: pointer; padding: 16px; border: 2px solid #dc3545; border-radius: 8px; text-align: center;"
                                @click.prevent="selectDifficulty($event)">
                                <input type="radio" name="singlePlayerDifficulty" value="hard" style="display: none;">
                                <div style="font-weight: 600;">🔴 Schwer</div>
                            </label>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn btn-secondary" @click.prevent="$emit('update:modelValue', false)"
                        style="flex: 1;">
                        Abbrechen
                    </button>
                    <button type="button" class="btn btn-primary" @click.prevent="startSingleplayer()"
                        style="flex: 1;">
                        ✅ Spiel starten
                    </button>
                    <button @click.prevent="DebugFunction">Debug-Button</button>
                </div>
            </div>
            <div id="sp-game-running" style="margin:auto" v-if="singleplayerStore.hasStarted">
                <p>Es läuft bereits ein Quiz</p>
                <button type="button" class="btn btn-secondary" @click.prevent="$emit('update:modelValue', false)"
                    style="flex: 1;">
                    Abbrechen
                </button>
                <button type="button" class="btn btn-danger" @click.prevent="resetSingleplayerStore()">Neues Quiz starten</button>
                <button type="button" class="btn btn-primary"
                    @click.prevent="goToSingleplayerPage()">Fortsetzen</button>
            </div>
        </div>
    </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import { useSingleplayerStore } from '@/stores/singleplayer';
import router from '@/router/index';
import quizzes from '../files/quizzes.json';
import users from '../files/users.json';
import questions from '../files/questions.json';
// JK: Für Prop-Manipulation mittels V-model https://vuejs.org/guide/components/v-model.html
// Notwendig, da das Modal als Child von DashboardPage.vue aufgerufen wird und somit über dessen Anzeige entscheidet. Somit muss
// das Modal den Wert in der Dashboardkomponenten ändern
export default {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    data() {
        const sessionStore = useSessionStore()
        const singleplayerStore = useSingleplayerStore()

        return {
            sessionStore,
            singleplayerStore,
            quizzes,
            questions,
            users,
            selectedQuizID: null,
            mode: ''
        }
    },
    methods: {
        DebugFunction() {
            console.log(this.getCurrentUser().stats.quiz_catalogue)
        },
        selectDifficulty(event) {
            // JK:event ist in dem Fall das Click event. Vue.js nimmt über den Ausdruck event automatisch das angeklickte Element
            const labelElement = event.currentTarget;
            // Schwierigkeitsauswahl Handler für farbliche Hervorhebung
            // Entferne selected-difficulty von allen Labels
            const allLabels = labelElement.parentElement.querySelectorAll('label');
            allLabels.forEach(label => label.classList.remove('selected-difficulty'));

            // Füge selected-difficulty zur geklickten Label hinzu
            labelElement.classList.add('selected-difficulty');

            // Setze das entsprechende Radio-Button auf checked
            const input = labelElement.querySelector('input[type="radio"]');
            if (input) {
                input.checked = true;
            }
            this.mode = 'catalogue'
        },
        selectQuiz(quiz) {
            this.mode = 'quiz'
            this.selectedQuizID = quiz.quizID;
            console.log("Quiz.quizID:", quiz.quizID);
            console.log("SelectedQuizID:",this.selectedQuizID)
        },
        //JK: Diese Funktion dient dazu, die notwendigen Daten an den singleplayerStore zu übergeben. Diese müssen dann von
        //Singleplayerpage.vue geladen werden
        startSingleplayer() {
            this.singleplayerStore.quiz = this.quizzes.find(quiz => quiz.quizID == this.selectedQuizID)

            this.singleplayerStore.quiz.questions.forEach(id => {
                const foundQuestion = this.questions.find(question => question.questionID == id);
                this.singleplayerStore.questions.push(foundQuestion);
            });
            this.singleplayerStore.currentQuestionIndex = 0;
            this.singleplayerStore.hasStarted = true;
            //Debug
            console.log(this.singleplayerStore.quiz)
            console.log(this.singleplayerStore.questions)
            router.push('/singleplayer');
        },
        resetSingleplayerStore() {
            this.singleplayerStore.hasStarted = false;
            this.singleplayerStore.quizID = null;
            this.singleplayerStore.quiz = null
            this.singleplayerStore.questions = [];
            this.singleplayerStore.currentQuestionIndex = null;
            this.singleplayerStore.score = 0;
            this.singleplayerStore.finished = false;
        },
        goToSingleplayerPage() {
            router.push('/singleplayer');
        },
        getUserNameFromQuizUserID(quizUserID) {
            const foundUser = this.users.find(user => user.userID === quizUserID);
            return foundUser.first_name;
        },
        getCurrentUser() {
            const currentUserID = this.sessionStore.userID;
            return this.users.find(user => user.userID === currentUserID);
        },
        getQuizzesFromUserQuizCatalogue() {
            const currentUser = this.getCurrentUser();
            const currentUserQuizCatalogue = currentUser.stats.quiz_catalogue;
            let userQuizArray = []
            currentUserQuizCatalogue.forEach(id => {
                userQuizArray.push(this.quizzes.find(quiz => quiz.quizID === id))
            });
            return userQuizArray;
        }
    }
}

</script>