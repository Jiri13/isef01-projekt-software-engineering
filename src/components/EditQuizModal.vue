<template>
    <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
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
            <!-- <div class="form-group">
                <label class="form-label">Fragen</label>
                <select class="form-input" v-model.number="form.questions">
                    <option v-for="question in questions" :key="question.question_id">
                        {{ question.question_text }}
                    </option>
                </select>
            </div> -->
        </form>

        <div style="height: 250px; overflow-y: auto; margin-bottom: 24px;">
            <div v-for="question in questions" :key="question.questionID" class="card" style="margin-bottom: 12px; border: 1px solid #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;">
                    <div style="flex: 1; cursor: default;" @click="openEdit(q.id)">
                        <strong style="font-size: 16px; color: #007bff;">{{ question.question_text }}</strong>
                        <span :class="['badge', getDifficultyClass(question.difficulty)]"
                            style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;">
                            {{ getDifficultyLabel(question.difficulty) }}
                        </span>
                        <br>
                        <small style="color: #666;">
                            Typ: {{ question.question_type === 'multiple_choice' ? 'Multiple Choice' : question.question_type === 'true_false' ?
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


        <div style="display:flex;gap:12px;margin-top:16px;">
            <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Änderungen speichern</button>
        </div>
    </div>



</template>


<script>
import { useSessionStore } from '@/stores/session'
import axios from 'axios'
// import questions from '../files/questions.json'

export default {
    props: {
        modelValue: { type: Boolean, default: false },
        quizID: {
            type: Number
        },
        quizzes: {type: Array},
        questions: {type: Array}
    },
    emits: ['update:modelValue', 'created'],
    data() {
        const session = useSessionStore()
        return {
            session,
            
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
            }
        }
    },
    created(){
        if (this.quizzes && this.quizID){
            const index = this.quizID -1;
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
        deleteQuestion(questionID) {
            console.log("Removed Question from Quiz")
        },
        updateQuiz(){
            console.log("Update Quiz")
        }
    }
}
</script>