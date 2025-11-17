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

            <div class="form-group">
                <label class="form-label">Fragen hinzufügen (optional)</label>
                <select class="form-input" v-model.number="form.questions">
                    <option v-for="question in questions" :key="question.question_id">
                        {{ question.question_text }}
                    </option>
                </select>
            </div>

            <div style="display:flex;gap:12px;margin-top:16px;">
                <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Quiz erstellen</button>
            </div>
        </form>
    </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
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
            questions: [],
            form: {
                name: '',
                category: '',
                description: '',
                timeLimit: 0,
                questions: []
            }
        };
    },
    watch: {
        modelValue(newVal) {
            if (newVal) {
                this.loadQuestions()
            }
        }
    },
    mounted() {
        if (this.modelValue){
            this.loadQuestions()
        }
    },
    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },
        async loadQuestions() {
            // Load persisted questions from localStorage first (user edits)
            const saved = localStorage.getItem('quiz_questions')
            if (saved) {
                try {
                    const parsed = JSON.parse(saved)
                    if (Array.isArray(parsed)) this.questions = parsed.map(q => normalize(q))
                } catch (e) {
                    console.warn('Failed to parse saved quiz_questions', e)
                }
            }

            // Try to load from backend API; if it succeeds we override local default (but not persisted edits)
            try {
                const response = await axios.get('/api/getQuestions.php')
                if (response.data && Array.isArray(response.data)) {
                    this.questions = response.data.map(q => normalize(q))
                }
            } catch (err) {
                console.warn('Backend nicht erreichbar, benutze lokale Fragen.json as fallback.', err && err.message ? err.message : err)
            }
        }
    }
}
</script>


<style scoped>
.modal-inner { 
    max-width:640px;
    width:100%;
    background:white;
    border-radius:8px;
    padding:20px;
}
</style>