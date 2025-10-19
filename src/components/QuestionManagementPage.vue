<template>
    <DashboardNavbar />

    <div class="container" style="margin-top: 24px;">
        <div v-if="questions.length === 0" class="card" style="text-align: center; padding: 24px;">
            <h3>📝 Noch keine Fragen gefunden</h3>
            <p style="color: #666;">Erstelle Fragen unter <code>src/files/questions.json</code> oder lade welche hoch.</p>
        </div>

        <div v-else>
            <div v-for="q in questions" :key="q.id" class="card"
                     style="margin-bottom: 12px; border: 1px solid #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;">
                    <div style="flex: 1; cursor: pointer;" @click="openEdit(q.id)">
                        <strong style="font-size: 16px; color: #007bff;">{{ q.text }}</strong>
                        <span :class="['badge', getDifficultyClass(q.difficulty)]"
                                    style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;">
                            {{ getDifficultyLabel(q.difficulty) }}
                        </span>
                        <br>
                        <small style="color: #666;">
                            Typ: {{ q.type === 'multiple_choice' ? 'Multiple Choice' : q.type === 'true_false' ? 'Wahr/Falsch' : 'Texteingabe' }} |
                            Zeitlimit: {{ q.timeLimit }}s
                        </small>
                        <br v-if="q.explanation">
                        <small v-if="q.explanation" style="color: #28a745;"><em>Mit Erklärung</em></small>
                    </div>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button class="btn btn-secondary" @click.stop="openEdit(q.id)" style="padding: 6px 12px; font-size: 12px;">
                            ✏️ Bearbeiten
                        </button>
                        <button class="btn btn-danger" @click.stop="deleteQuestion(q.id)" style="padding: 6px 12px; font-size: 12px;">
                            🗑️ Löschen
                        </button>
                    </div>
                </div>
            </div>
        </div>

            <!-- Edit Modal -->
            <div v-if="showModal && selectedQuestion" class="modal" @click.self="closeModal"
                     style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:1000;">
                <div class="modal-content" style="background:white;border-radius:8px;padding:20px;max-width:720px;width:95%;max-height:85vh;overflow:auto;box-shadow:0 6px 24px rgba(0,0,0,0.25);">
                    <button type="button" @click="closeModal" style="position:absolute;right:12px;top:12px;background:transparent;border:none;font-size:20px;cursor:pointer;">✕</button>
                <h2 style="margin-bottom: 12px;">✏️ Frage bearbeiten</h2>
                <form @submit.prevent="saveQuestion">
                    <div class="form-group">
                        <label class="form-label">Fragetext</label>
                        <textarea class="form-input" v-model="selectedQuestion.text" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fragetyp</label>
                        <select class="form-input" v-model="selectedQuestion.type" @change="onTypeChange">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">Wahr/Falsch</option>
                            <option value="text_input">Texteingabe</option>
                        </select>
                    </div>

                    <div class="form-group" v-if="selectedQuestion.type !== 'text_input'">
                        <label class="form-label">Antwortoptionen</label>
                        <div v-for="(opt, idx) in selectedQuestion.options" :key="idx" style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                            <input class="form-input" v-model="selectedQuestion.options[idx]" :placeholder="`Option ${idx + 1}`" />
                            <label style="display:flex; align-items:center; gap:6px; font-size:14px;">
                                <input type="radio" name="correctAnswer" :value="idx" v-model.number="selectedQuestion.correctAnswer" /> Richtig
                            </label>
                            <button type="button" class="btn btn-danger" v-if="selectedQuestion.type === 'multiple_choice'" @click="removeOption(idx)">✕</button>
                        </div>
                        <button type="button" class="btn btn-secondary" @click="addOption" v-if="selectedQuestion.type === 'multiple_choice'">+ Option hinzufügen</button>
                    </div>

                    <div class="form-group" v-if="selectedQuestion.type === 'text_input'">
                        <label class="form-label">Richtige Antwort (Text)</label>
                        <input class="form-input" v-model="selectedQuestion.correctAnswerText" />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Zeitlimit (Sekunden)</label>
                        <input type="number" class="form-input" v-model.number="selectedQuestion.timeLimit" min="5" max="300" />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Schwierigkeitsgrad</label>
                        <select class="form-input" v-model="selectedQuestion.difficulty">
                            <option value="easy">Leicht</option>
                            <option value="medium">Mittel</option>
                            <option value="hard">Schwer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Erklärung</label>
                        <textarea class="form-input" v-model="selectedQuestion.explanation" rows="3"></textarea>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:16px;">
                        <button type="button" class="btn btn-secondary" @click="closeModal" style="flex:1;">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" style="flex:1;">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import DashboardNavbar from './DashboardNavbar.vue';
import questionsFile from '@/files/questions.json';

function normalize(q) {
    // Map incoming JSON shape to the local shape used by the component
    return {
        id: q.questionID ?? q.id ?? Date.now(),
        text: q.question_text ?? q.text ?? '',
        type: q.type ?? 'multiple_choice',
        options: Array.isArray(q.options) ? [...q.options] : [],
        correctAnswer: typeof q.correctAnswer === 'number' ? q.correctAnswer : 0,
        // for text_input questions we store the correct text separately
        correctAnswerText: typeof q.correctAnswer === 'string' ? q.correctAnswer : '',
        explanation: q.explanation ?? '',
        timeLimit: q.timeLimit ?? 30,
        difficulty: q.difficulty ?? 'easy',
        rating: q.rating ?? 0,
        quizID: q.quizID ?? null,
        userID: q.userID ?? null
    };
}

export default {
    components: { DashboardNavbar },
    data() {
        return {
            // create editable, normalized copy of imported questions
            questions: Array.isArray(questionsFile) ? questionsFile.map(normalize) : [],
            showModal: false,
            selectedQuestion: null
        };
    },
    methods: {
        openEdit(id) {
            const q = this.questions.find(x => x.id === id);
            if (!q) return;
                        console.log('openEdit called for id=', id, 'found:', !!q);
                        // deep copy so changes aren't applied until Save
                        this.selectedQuestion = JSON.parse(JSON.stringify(q));
            // ensure options array exists for editing
            if (!Array.isArray(this.selectedQuestion.options)) this.selectedQuestion.options = [];
                        // make modal visible after we've prepared the selectedQuestion
                        this.showModal = true;
                        this.$nextTick(() => {
                            // focus the textarea if present
                            const ta = document.querySelector('.modal-content textarea');
                            if (ta) ta.focus();
                        });
        },
        editQuestion(id) {
            // kept for compatibility: open modal
            this.openEdit(id);
        },
        closeModal() {
            this.selectedQuestion = null;
            this.showModal = false;
        },
        saveQuestion() {
            if (!this.selectedQuestion) return;
            console.log('saveQuestion called for id=', this.selectedQuestion.id);
            // basic validation
            if (!this.selectedQuestion.text || this.selectedQuestion.text.trim() === '') {
                alert('Bitte Fragetext eingeben.');
                return;
            }

            // If multiple_choice, ensure at least 2 options
            if (this.selectedQuestion.type === 'multiple_choice') {
                const opts = (this.selectedQuestion.options || []).filter(o => o && o.trim() !== '');
                if (opts.length < 2) {
                    alert('Mindestens zwei Antwortoptionen erforderlich.');
                    return;
                }
                // sanitize options
                this.selectedQuestion.options = opts;
                if (typeof this.selectedQuestion.correctAnswer !== 'number' || this.selectedQuestion.correctAnswer >= opts.length) {
                    this.selectedQuestion.correctAnswer = 0;
                }
            }

            // Merge back into questions array
            const idx = this.questions.findIndex(q => q.id === this.selectedQuestion.id);
            if (idx !== -1) {
                // overwrite
                this.questions.splice(idx, 1, JSON.parse(JSON.stringify(this.selectedQuestion)));
            } else {
                this.questions.push(JSON.parse(JSON.stringify(this.selectedQuestion)));
            }

            this.closeModal();
            this.$emit('question-updated', this.selectedQuestion);
            // Note: changes are local only. To persist, add a backend or write to filesystem during development.
        },
        deleteQuestion(id) {
            if (!confirm('Sind Sie sicher, dass Sie diese Frage löschen möchten?')) return;
            this.questions = this.questions.filter(q => q.id !== id);
            this.$emit('question-deleted', id);
        },
        addOption() {
            if (!this.selectedQuestion) return;
            if (!Array.isArray(this.selectedQuestion.options)) this.selectedQuestion.options = [];
            if (this.selectedQuestion.options.length >= 6) return;
            this.selectedQuestion.options.push('');
        },
        removeOption(idx) {
            if (!this.selectedQuestion) return;
            this.selectedQuestion.options.splice(idx, 1);
            if (this.selectedQuestion.correctAnswer >= this.selectedQuestion.options.length) {
                this.selectedQuestion.correctAnswer = 0;
            }
        },
        onTypeChange() {
            if (!this.selectedQuestion) return;
            if (this.selectedQuestion.type === 'true_false') {
                this.selectedQuestion.options = ['Wahr', 'Falsch'];
                this.selectedQuestion.correctAnswer = 0;
            } else if (this.selectedQuestion.type === 'multiple_choice') {
                if (!Array.isArray(this.selectedQuestion.options) || this.selectedQuestion.options.length < 2) {
                    this.selectedQuestion.options = ['', ''];
                    this.selectedQuestion.correctAnswer = 0;
                }
            } else if (this.selectedQuestion.type === 'text_input') {
                // move numeric correctAnswer to text
                this.selectedQuestion.correctAnswerText = this.selectedQuestion.correctAnswerText || '';
            }
        },
        getDifficultyLabel(d) {
            switch ((d || '').toLowerCase()) {
                case 'easy': return 'Leicht';
                case 'medium': return 'Mittel';
                case 'hard': return 'Schwer';
                default: return d || 'Unbekannt';
            }
        },
        getDifficultyClass(d) {
            return `difficulty-${(d || 'easy').toLowerCase()}`;
        }
    }
};
</script>