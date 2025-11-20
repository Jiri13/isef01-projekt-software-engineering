<template>
    <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
        <h2>➕ Neues Quiz erstellen</h2>
        <form @submit.prevent="createQuiz">
            <div class="form-group">
                <label class="form-label">Name</label>
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

            <label class="form-label">Fragen zum Quiz hinzufügen</label>
            <button class="btn btn-primary" @click="isAllQuestionsCollapsed = !isAllQuestionsCollapsed"> {{
                isAllQuestionsCollapsed ? 'Anzeigen' : 'Schließen' }}</button>
            <Transition name="collapse" @enter="enterAllQuestions" @leave="leaveAllQuestions">
                <div v-show="!isAllQuestionsCollapsed" class="collapsible-content"
                    style="height: 250px; overflow-y: auto; margin-bottom: 24px;">
                    <div v-for="question in allQuestions" :key="question.questionID" class="card"
                        style="margin-bottom: 12px; border: 1px solid #ddd;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;">
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
                                <small v-if="question.explanation" style="color: #28a745;"><em>Mit
                                        Erklärung</em></small>
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
                <button type="submit" class="btn btn-primary">Quiz erstellen</button>
            </div>
        </form>
    </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import allQuestions from '../files/questions.json'
import axios from 'axios'

export default {
    emits: ['update:modelValue', 'created'],
    props: {
        modelValue: { type: Boolean, default: false }
    },
    data() {
        const session = useSessionStore()
        return {
            session,
            allQuestions,
            questions: [],
            isAllQuestionsCollapsed: true,
            form: {
                name: '',
                category: '',
                description: '',
                timeLimit: 0,
                questions: []
            }
        };
    },
    methods: {
        close() {
            this.$emit('update:modelValue', false)
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
        leaveAllQuestions(el) {
            el.style.height = getComputedStyle(el).height;
            getComputedStyle(el);
            setTimeout(() => {
                el.style.height = '0';
            });
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
                addQuestion(questionID){
            console.log("Added Question to Quiz")
            console.log(questionID)
        }
    }
}
</script>


<style scoped>
.modal-inner {
    max-width: 640px;
    width: 100%;
    background: white;
    border-radius: 8px;
    padding: 20px;
}

.collapse-enter-active,
.collapse-leave-active {
    transition: height 0.3s ease-in-out;
    overflow: hidden;
}
</style>