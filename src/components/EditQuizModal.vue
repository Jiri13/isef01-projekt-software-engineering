<template>
    <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
        <h1>✏️ Quiz bearbeiten</h1>
        <h2>{{ quizzes[quizID - 1].title }}</h2>
        <p>Erstelldatum: {{ quizzes[quizID - 1].created_at }}</p>
        <form @submit.prevent="createQuiz">
            <div class="form-group">
                <label class="form-label">Quiz-Name</label>
                <input class="form-input" v-model="form.name" required/>
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

            <div class="form-group">
                <label class="form-label">Fragen</label>
                <select class="form-input" v-model.number="form.questions">
                    <option v-for="question in questions" :key="question.question_id">
                        {{ question.question_text }}
                    </option>
                </select>
            </div>

            <div style="display:flex;gap:12px;margin-top:16px;">
                <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Änderungen speichern</button>
            </div>
        </form>
    </div>
</template>


<script>
import { useSessionStore } from '@/stores/session'
import axios from 'axios'

export default {
    props: {
        modelValue: { type: Boolean, default: false },
        quizID: {
            type: Number
        },
        quizzes: {type: Array}
    },
    emits: ['update:modelValue', 'created'],
    data() {
        const session = useSessionStore()
        return {
            session,
            questions: [],
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
        }
    }
}
</script>