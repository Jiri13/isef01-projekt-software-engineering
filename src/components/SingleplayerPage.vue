<template>
    <SingleplayerNavbar></SingleplayerNavbar>

    <p>Quiz:ID lautet {{ singleplayerStore.quiz.quizID }}</p>
    <p>{{currentQuestion.timeLimit}}</p>
    <p>{{currentQuestion.explanation}}</p>
    <p>{{explanationText}}</p>
    <p>{{currentQuestion.difficulty}}</p>
    <button @click.prevent="debug()">Debug</button>
    <button @click.prevent="debugCancelQuiz()">Debug Cancel Quiz</button>

    <div class="container" v-if=!singleplayerStore.finished>
        <div class="card">
            <div style="margin-bottom: 24px;">
                <div style="background: #f8f9fa; height: 8px; border-radius: 4px; margin-bottom: 16px;">
                    <div style="background: #007bff; height: 100%; border-radius: 4px; transition: width 0.3s ease;"
                        :style="{ width: progressBarWidth }">
                    </div>
                </div>
                <div style="text-align: center; color: #666; font-size: 14px;">
                    Frage: {{ singleplayerStore.currentQuestionIndex + 1 }} von {{ singleplayerStore.questions.length
                    }} | Punkte:
                    {{ singleplayerStore.score }}
                </div>
            </div>

            <div style="font-size: 20px; font-weight: 600; margin-bottom: 24px; text-align: center;">
                {{ currentQuestion.question_text }}
            </div>

            <div id="singlePlayerOptions" :class="{ 'disabled-options': isAnswerSubmitted }"
                v-for="(option, index) in currentQuestion.options" :key="index">
                <div class="answer-option" :class="getOptionClasses(option, index)" @click.prevent="select(option)">{{
                    option }}</div>
            </div>
            <!-- JK:Die Anzeige der Erklärung ist aktuell verbuggt. Keine Ahnung warum...Sobald submitAnswer() ausgeführt wird, wird der String-Value zu true.  -->
            <div id="singleplayerExplanation" class="singleplayer-explanation"
                v-if="isAnswerSubmitted && currentQuestionHasExplanation">{{ currentQuestion.explanation }}</div>

            <button id="submitSinglePlayerBtn" class="btn btn-primary" @click.prevent="submitAnswer()"
                :disabled="isSubmitDisabled" style="width: 100%; margin-top: 24px; font-size: 16px; padding: 16px;">
                {{ buttonText }}
            </button>
        </div>
    </div>


    <div class="container" v-if="singleplayerStore.finished">
        <div class="card" style="text-align: center; padding: 48px;">
            <h1 :style="ratingColor">{{ rating }}</h1>

            <div class="badge ${getDifficultyClass(selectedDifficulty)}"
                style="padding: 8px 16px; border-radius: 20px; font-size: 16px; margin-bottom: 24px;">
                Schwierigkeit: ${getDifficultyLabel(selectedDifficulty)}
            </div>

            <div style="background: #f8f9fa; padding: 32px; border-radius: 12px; margin: 32px 0;">
                <div style="font-size: 36px; font-weight: 700; color: #007bff; margin-bottom: 16px;">
                    {{ singleplayerStore.score }} / {{ singleplayerStore.questions.length }}
                </div>
                <div style="font-size: 24px; color: #666;">
                    {{ (singleplayerStore.score / singleplayerStore.questions.length) * 100 }} % richtig beantwortet
                </div>
            </div>

            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <!-- <button class="btn btn-primary" @click.prevent="showSinglePlayerDifficultyModal()"
                    style="padding: 16px 32px;">
                    🔄 Nochmal spielen
                </button> -->
                <button class="btn btn-secondary" @click.prevent="goToDashboard()" style="padding: 16px 32px;">
                    📊 Zum Dashboard
                </button>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="isShowingSinglePlayerModal" class="modal">
            <singleplayer-difficulty-modal v-model="isShowingSinglePlayerModal"></singleplayer-difficulty-modal>
        </div>
    </Teleport>


</template>

<script>
import SingleplayerNavbar from './SingleplayerNavbar.vue';
import router from '@/router';
import { useSingleplayerStore } from '@/stores/singleplayer';
import SingleplayerDifficultyModal from './SingleplayerDifficultyModal.vue'
import quizzes from '../files/quizzes.json'
import questions from '../files/questions.json'

export default {
    data() {
        const singleplayerStore = useSingleplayerStore()
        const ButtonState = Object.freeze({
            SUBMIT: {
                text: '✅ Antwort bestätigen',
            },
            NEXT: {
                text: '➡️ Nächste Frage',
            },
            FINISH: {
                text: '🏁 Quiz beenden'
            }
        })

        return {
            singleplayerStore,
            quizzes,
            questions,
            selectedOption: null,
            isAnswerSubmitted: false,
            showResult: false,
            ButtonState,
            isShowingSinglePlayerModal: false
        }
    },
    components: {
        SingleplayerNavbar, SingleplayerDifficultyModal
    },
    computed: {
        progressBarWidth() {
            const progress = (this.singleplayerStore.currentQuestionIndex / this.singleplayerStore.questions.length) * 100;
            return `${progress}%`;
        },
        currentQuestion() {
            return this.singleplayerStore.questions[this.singleplayerStore.currentQuestionIndex];
        },
        currentQuestionHasExplanation() {
            return this.currentQuestion.explanation && (this.currentQuestion.explanation = ! '');
        },
        isSubmitDisabled() {
            return this.selectedOption == null;
        },
        isLastQuestion() {
            return this.singleplayerStore.currentQuestionIndex === this.singleplayerStore.questions.length - 1;
        },
        buttonText() {
            if (!this.isAnswerSubmitted) {
                return this.ButtonState.SUBMIT.text
            }
            if (this.isAnswerSubmitted) {
                return this.isLastQuestion ? '🏁 Quiz beenden' : '➡️ Nächste Frage';
            }
        },
        rating() {
            const percentage = (this.singleplayerStore.score / this.singleplayerStore.questions.length) * 100;
            if (percentage >= 90) {
                return '🏆 Hervorragend!';
            } else if (percentage >= 70) {
                return '🥈 Sehr gut!';
            } else if (percentage >= 50) {
                return '🥉 Gut gemacht!';
            } else {
                return '💪 Weiter üben!';
            }
        },
        ratingColor() {
            const percentage = (this.singleplayerStore.score / this.singleplayerStore.questions.length) * 100;
            if (percentage >= 90) {
                return {
                    color: '#28a745',
                    marginBottom: '32px'
                };
            } else if (percentage >= 70) {
                return {
                    color: '#17a2b8',
                    marginBottom: '32px'
                };
            } else if (percentage >= 50) {
                return {
                    color: '#ffc107',
                    marginBottom: '32px'
                };

            } else {
                return {
                    color: '#dc3545',
                    marginBottom: '32px'
                };
            }
        },
        explanationText(){
            return String(this.currentQuestion.explanation);
        }

    },
    methods: {
        debug() {

        },
        debugCancelQuiz() {
            this.resetSingleplayerStore();
            router.push('/');
        },
        goToDashboard() {
            this.resetSingleplayerStore();
            router.push('/');
        },
        getQuestionFromID(id) {
            const question = questions.find(question => id == question.questionID);
            return question;
        },
        getQuestionIDsFromQuizID() {
            const quizID = this.singleplayerStore.quizID
            const quiz = this.quizzes.find(quiz => quizID == quiz.quizID)
            return quiz.questions;
        },
        getOptionClasses(option, index) {
            const classes = {
                'selected': this.selectedOption === option,
            };

            if (this.isAnswerSubmitted) {
                const isCorrectOption = index === this.currentQuestion.correctAnswer;
                const isUserSelected = this.selectedOption === option;

                if (isCorrectOption) {
                    classes['correct'] = true;
                    classes['selected'] = false;
                }

                if (!isCorrectOption && isUserSelected) {
                    classes['incorrect'] = true;
                    classes['selected'] = false;
                }
            }
            return classes;
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
        select(option) {
            this.selectedOption = option;
        },
        showSinglePlayerDifficultyModal() {
            this.isShowingSinglePlayerModal = true;
        },
        submitAnswer() {
            if (!this.isAnswerSubmitted) {
                this.isAnswerSubmitted = true;
                if (this.selectedOption == this.currentQuestion.options[this.currentQuestion.correctAnswer]) {
                    console.log("Correct");
                    console.log(this.currentQuestion);
                    this.singleplayerStore.score++;
                    console.log("First if called");
                }
                return
            }
            if (this.isAnswerSubmitted && (this.singleplayerStore.currentQuestionIndex < this.singleplayerStore.questions.length - 1)) {
                this.singleplayerStore.currentQuestionIndex++;
                this.selectedOption = null;
                this.isAnswerSubmitted = false;
                console.log("Second if called")
            } else {
                // this.showResult = true;
                this.singleplayerStore.finished = true;
                console.log("Quiz finished!");
            }
        }
    },
    mounted(){
        console.log("Current Question:", this.currentQuestion);
        console.log("Explanation:", this.currentQuestion.explanation);
        console.log("Type:", typeof this.currentQuestion.explanation);
        console.log("QuestionType:", this.currentQuestion.type);
        
    }
}
</script>

<style scoped>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
</style>