<template>
    <div v-if="quizzes[quizID - 1].userID === session.userID" class="modal-inner"
        style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
        <h1>✏️ Quiz bearbeiten</h1>
        <h2>{{ quizzes[quizID - 1].title }}</h2>
        <p>Erstelldatum: {{ quizzes[quizID - 1].created_at }}</p>
        <form @submit.prevent="updateQuiz">
            <div class="form-group">
                <label class="form-label">Quiz-Name</label>
                <input class="form-input" v-model="form.name" required />
            </div>

            <div class="form-group">
                <label class="form-label">Fach/Modul</label>
                <input class="form-input" v-model="form.category" required />
            </div>

            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <input class="form-input" v-model="form.description" required />
            </div>

            <div class="form-group">
                <label class="form-label">Zeitlimit</label>
                <input type="number" min="2" max="99" class="form-input" v-model.number="form.timeLimit" />
            </div>
        </form>
        <label class="form-label">Fragen im Quiz</label>
        <button class="btn btn-primary" @click="isQuizQuestionsCollapsed = !isQuizQuestionsCollapsed"> {{
            isQuizQuestionsCollapsed ? 'Anzeigen' : 'Schließen' }}</button>
        <Transition name="collapse" @enter="enterQuizQuestions" @leave="leaveQuizQuestions">
            <div v-show="!isQuizQuestionsCollapsed" class="collapsible-content"
                style="height: 250px; overflow-y: auto; margin-bottom: 24px;">
                <div v-for="question in questions" :key="question.questionID" class="card"
                    style="margin-bottom: 12px; border: 1px solid #ddd;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;">
                        <div style="flex: 1; cursor: default;">
                            <strong style="font-size: 16px; color: #007bff;">{{ question.question_text }}</strong>
                            <span :class="['badge', getDifficultyClass(question.difficulty)]"
                                style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;">
                                {{ getDifficultyLabel(question.difficulty) }}
                            </span>
                            <br>
                            <small style="color: #666;">
                                Typ: {{ question.question_type === 'multiple_choice' ? 'Multiple Choice' :
                                    question.question_type === 'true_false' ?
                                        'Wahr/Falsch' : 'Texteingabe' }} |
                                Zeitlimit: {{ question.time_limit }}s
                            </small>
                            <br v-if="question.explanation">
                            <small v-if="question.explanation" style="color: #28a745;"><em>Mit Erklärung</em></small>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button class="btn btn-danger" @click.stop="deleteQuestion(question.questionID)"
                                style="padding: 6px 12px; font-size: 12px;">
                                🗑️ Löschen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
        <label class="form-label">Fragen zum Quiz hinzufügen</label>
        <button class="btn btn-primary" @click="isAllQuestionsCollapsed = !isAllQuestionsCollapsed"> {{
            isAllQuestionsCollapsed ? 'Anzeigen' : 'Schließen' }}</button>
        <Transition name="collapse" @enter="enterAllQuestions" @leave="leaveAllQuestions">
            <div v-show="!isAllQuestionsCollapsed" class="collapsible-content"
                style="height: 250px; overflow-y: auto; margin-bottom: 24px;">
                <div v-for="question in allQuestions" :key="question.questionID" class="card"
                    style="margin-bottom: 12px; border: 1px solid #ddd;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;">
                        <div style="flex: 1; cursor: default;">
                            <strong style="font-size: 16px; color: #007bff;">{{ question.question_text }}</strong>
                            <span :class="['badge', getDifficultyClass(question.difficulty)]"
                                style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;">
                                {{ getDifficultyLabel(question.difficulty) }}
                            </span>
                            <br>
                            <small style="color: #666;">
                                Typ: {{ question.question_type === 'multiple_choice' ? 'Multiple Choice' :
                                    question.question_type === 'true_false' ?
                                        'Wahr/Falsch' : 'Texteingabe' }} |
                                Zeitlimit: {{ question.time_limit }}s
                            </small>
                            <br v-if="question.explanation">
                            <small v-if="question.explanation" style="color: #28a745;"><em>Mit Erklärung</em></small>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button class="btn btn-primary" @click.stop="addQuestion(question.questionID)"
                                style="padding: 6px 12px; font-size: 12px;">
                                ➕ Hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
        <div style="display:flex;gap:12px;margin-top:16px;">
            <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
            <button type="submit" class="btn btn-primary" @click="save">Speichern</button>
        </div>
    </div>
    <!-- Quiz nicht selbst erstellt -->
    <div v-else class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
        <h2>Fragen in: {{ quizzes[quizID - 1].title }}</h2>
        <p>Erstelldatum: {{ quizzes[quizID - 1].created_at }}</p>
        <div style="height: 500px; overflow-y: auto; margin-bottom: 24px;">
            <div v-for="question in allQuestions" :key="question.questionID" class="card"
                style="margin-bottom: 12px; border: 1px solid #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;">
                    <div style="flex: 1; cursor: default;">
                        <strong style="font-size: 16px; color: #007bff;">{{ question.question_text }}</strong>
                        <span :class="['badge', getDifficultyClass(question.difficulty)]"
                            style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;">
                            {{ getDifficultyLabel(question.difficulty) }}
                        </span>
                        <br>
                        <small style="color: #666;">
                            Typ: {{ question.question_type === 'multiple_choice' ? 'Multiple Choice' :
                                question.question_type === 'true_false' ?
                                    'Wahr/Falsch' : 'Texteingabe' }} |
                            Zeitlimit: {{ question.time_limit }}s
                        </small>
                        <br v-if="question.explanation">
                        <small v-if="question.explanation" style="color: #28a745;"><em>Mit Erklärung</em></small>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" @click="close">Schließen</button>
    </div>


</template>


<script>
import { useSessionStore } from '@/stores/session'
import axios from 'axios'
import allQuestions from '../files/questions.json'

export default {
    props: {
        modelValue: { type: Boolean, default: false },
        quizID: {
            type: Number
        },
        quizzes: { type: Array },
        questions: { type: Array }
    },
    emits: ['update:modelValue', 'created'],
    data() {
        const session = useSessionStore()
        return {
            session,
            isQuizQuestionsCollapsed: true,
            isAllQuestionsCollapsed: true,
            form: {
                name: '',
                category: '',
                description: '',
                timeLimit: 0,
                questions: []
            },
            quiz: {
                userID: '',
                category: '',
                created_at: '',
                quizID: '',
                quiz_description: '',
                time_limit: '',
                title: '',
            },
            allQuestions
        }
    },
    created() {
        if (this.quizzes && this.quizID) {
            const index = this.quizID - 1;
            this.form.name = this.quizzes[index].title;
            this.form.category = this.quizzes[index].category;
            this.form.description = this.quizzes[index].quiz_description;
            this.form.time_limit = this.quizzes[index].time_limit;
        }
    },
    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },
        save(){
            alert("Änderungen im Quiz wurden gespeichert")
            this.$emit('update:modelValue', false)
        },
        getDifficultyLabel(difficulty) {
            switch ((difficulty || '').toLowerCase()) {
                case 'easy': return 'Leicht';
                case 'medium': return 'Mittel';
                case 'hard': return 'Schwer';
                default: return difficulty || 'Unbekannt';
            }
        },
        getDifficultyClass(d) {
            return `difficulty-${(d || 'easy').toLowerCase()}`;
        },
        addQuestion(questionID) {
            console.log("Added Question to Quiz")
            console.log(questionID)
        },
        deleteQuestion(questionID) {
            console.log("Removed Question from Quiz")
        },
        updateQuiz() {
            console.log("Update Quiz")
        },
        enterQuizQuestions(el) {
            el.style.height = '250px';
            const height = getComputedStyle(el).height;
            el.style.height = '0';
            getComputedStyle(el);
            setTimeout(() => {
                el.style.height = height;
            });
        },
        enterAllQuestions(el) {
            el.style.height = '250px';
            const height = getComputedStyle(el).height;
            el.style.height = '0';
            getComputedStyle(el);
            setTimeout(() => {
                el.style.height = height;
            });
        },

        leaveQuizQuestions(el) {
            el.style.height = getComputedStyle(el).height;
            getComputedStyle(el);
            setTimeout(() => {
                el.style.height = '0';
            });
        },
        leaveAllQuestions(el) {
            el.style.height = getComputedStyle(el).height;
            getComputedStyle(el);
            setTimeout(() => {
                el.style.height = '0';
            });
        }
    }
}
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
    transition: height 0.3s ease-in-out;
    overflow: hidden;
}
</style>