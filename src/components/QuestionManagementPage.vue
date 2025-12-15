<template>
  <DashboardNavbar />

  <div class="container" style="margin-top: 24px;">
    <!-- Button zum Erstellen einer Frage -->
    <div style="text-align: right; margin-bottom: 12px;">
      <button class="btn btn-primary" @click.prevent="showQuizManagementPage()">🗂️ Quizverwaltung</button>
      <button class="btn btn-primary" @click="openNewQuestion">➕ Neue Frage hinzufügen</button>
    </div>

    <div v-if="questions.length === 0" class="card" style="text-align: center; padding: 24px;">
      <h3>📝 Noch keine Fragen gefunden</h3>
      <p style="color: #666;">Erstelle Fragen oder lade welche hoch.</p>
    </div>

    <div v-else>
      <div
        v-for="q in questions"
        :key="q.id"
        class="card"
        style="margin-bottom: 12px; border: 1px solid #ddd;"
      >
        <div
          style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px;"
        >
          <div
            style="flex: 1; cursor: pointer;"
            @click="canEditQuestion(q) && openEdit(q.id)"
          >
            <strong style="font-size: 16px; color: #007bff;">{{ q.text }}</strong>
            <span
              :class="['badge', getDifficultyClass(q.difficulty)]"
              style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;"
            >
              {{ getDifficultyLabel(q.difficulty) }}
            </span>

            <!--Ersteller anzeigen (Vorname + Nachname, falls Nachname leer dann nur Vorname) -->
            <br />
            <small style="color:#666;">
              Ersteller: {{ getCreatorLabel(q) }}
            </small>

            <br />
            <small style="color: #666;">
              Typ:
              {{
                q.type === 'multiple_choice'
                  ? 'Multiple Choice'
                  : q.type === 'true_false'
                  ? 'Wahr/Falsch'
                  : 'Texteingabe'
              }}
              |
              Zeitlimit: {{ q.timeLimit }}s
            </small>
            <br v-if="q.explanation" />
            <small v-if="q.explanation" style="color: #28a745;"><em>Mit Erklärung</em></small>
          </div>

          <div style="display: flex; gap: 8px; align-items: center;">
            <button
              class="btn btn-secondary"
              v-if="canEditQuestion(q)"
              @click.stop="openEdit(q.id)"
              style="padding: 6px 12px; font-size: 12px;"
            >
              ✏️ Bearbeiten
            </button>
            <button
              class="btn btn-danger"
              v-if="canDeleteQuestion(q)"
              @click.stop="deleteQuestion(q.id)"
              style="padding: 6px 12px; font-size: 12px;"
            >
              🗑️ Löschen
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div
      v-if="showModal && selectedQuestion"
      class="modal"
      @click.self="closeModal"
      style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:1000;"
    >
      <div
        class="modal-content"
        style="background:white;border-radius:8px;padding:20px;max-width:720px;width:95%;max-height:85vh;overflow:auto;box-shadow:0 6px 24px rgba(0,0,0,0.25);"
      >
        <button
          type="button"
          @click="closeModal"
          style="position:absolute;right:12px;top:12px;background:transparent;border:none;font-size:20px;cursor:pointer;"
        >
          ✕
        </button>
        <h2 style="margin-bottom: 12px;">✏️ Frage bearbeiten</h2>
        <form @submit.prevent="saveQuestion">
          <div class="form-group">
            <label class="form-label">Fragetext</label>
            <textarea
              class="form-input"
              v-model="selectedQuestion.text"
              rows="3"
              required
            ></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Fragetyp</label>
            <select
              class="form-input"
              v-model="selectedQuestion.type"
              @change="onTypeChange"
            >
              <option value="multiple_choice">Multiple Choice</option>
              <option value="true_false">Wahr/Falsch</option>
              <option value="text_input">Texteingabe</option>
            </select>
          </div>

          <div class="form-group" v-if="selectedQuestion.type !== 'text_input'">
            <label class="form-label">Antwortoptionen</label>
            <div
              v-for="(opt, idx) in selectedQuestion.options"
              :key="idx"
              style="display:flex; gap:8px; margin-bottom:8px; align-items:center;"
            >
              <input
                class="form-input"
                v-model="selectedQuestion.options[idx]"
                :placeholder="`Option ${idx + 1}`"
              />
              <label
                style="display:flex; align-items:center; gap:6px; font-size:14px;"
              >
                <input
                  type="radio"
                  name="correctAnswer"
                  :value="idx"
                  v-model.number="selectedQuestion.correctAnswer"
                />
                Richtig
              </label>
              <button
                type="button"
                class="btn btn-danger"
                v-if="selectedQuestion.type === 'multiple_choice'"
                @click="removeOption(idx)"
              >
                ✕
              </button>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              @click="addOption"
              v-if="selectedQuestion.type === 'multiple_choice'"
            >
              + Option hinzufügen
            </button>
          </div>

          <div class="form-group" v-if="selectedQuestion.type === 'text_input'">
            <label class="form-label">Richtige Antwort (Text)</label>
            <input class="form-input" v-model="selectedQuestion.correctAnswerText" />
          </div>

          <div class="form-group">
            <label class="form-label">Zeitlimit (Sekunden)</label>
            <input
              type="number"
              class="form-input"
              v-model.number="selectedQuestion.timeLimit"
              min="5"
              max="300"
            />
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
            <textarea
              class="form-input"
              v-model="selectedQuestion.explanation"
              rows="3"
            ></textarea>
          </div>

          <div style="display:flex; gap:12px; margin-top:16px;">
            <button
              type="button"
              class="btn btn-secondary"
              @click="closeModal"
              style="flex:1;"
            >
              Abbrechen
            </button>
            <button type="submit" class="btn btn-primary" style="flex:1;">
              Speichern
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import DashboardNavbar from './DashboardNavbar.vue';
import axios from 'axios';
import router from '@/router/index';
import { useSessionStore } from '@/stores/session';

function normalize(q) {
  // creatorFirstName/creatorLastName aus getQuestions.php übernehmen
  return {
    id: q.questionID ?? q.id ?? Date.now(),
    text: q.question_text ?? q.text ?? '',
    type: q.type ?? 'multiple_choice',
    options: Array.isArray(q.options) ? [...q.options] : [],
    correctAnswer: typeof q.correctAnswer === 'number' ? q.correctAnswer : 0,
    correctAnswerText: typeof q.correctAnswer === 'string' ? q.correctAnswer : '',
    explanation: q.explanation ?? '',
    timeLimit: q.timeLimit ?? 30,
    difficulty: q.difficulty ?? 'easy',
    rating: q.rating ?? 0,
    quizID: q.quizID ?? null,
    userID: q.userID ?? null,

    creatorFirstName: q.creatorFirstName ?? '',
    creatorLastName: q.creatorLastName ?? '',
  };
}

export default {
  components: { DashboardNavbar },
  data() {
    const sessionStore = useSessionStore();

    return {
      sessionStore,
      questions: '',
      showModal: false,
      selectedQuestion: null,
    };
  },

  async mounted() {
    const saved = localStorage.getItem('quiz_questions');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed)) this.questions = parsed.map(q => normalize(q));
      } catch (e) {
        console.warn('Failed to parse saved quiz_questions', e);
      }
    }

    try {
      const response = await axios.get('/api/getQuestions.php');
      if (response.data && Array.isArray(response.data)) {
        this.questions = response.data.map(q => normalize(q));
      }
    } catch (err) {
      console.warn(
        'Backend nicht erreichbar, benutze lokale Fragen.json as fallback.',
        err && err.message ? err.message : err
      );
    }
  },

  watch: {
    questions: {
      handler(newQ) {
        try {
          localStorage.setItem('quiz_questions', JSON.stringify(newQ || []));
        } catch (e) {
          console.warn('Failed to persist quiz_questions', e);
        }
      },
      deep: true,
    },
  },

  methods: {
    getCreatorLabel(q) {
      const first = (q?.creatorFirstName || '').trim();
      const last  = (q?.creatorLastName || '').trim();
      const full  = `${first} ${last}`.trim();
      return full || first || 'Unbekannt';
    },

    openNewQuestion() {
      this.selectedQuestion = {
        id: 'temp-' + Date.now(),
        text: '',
        type: 'multiple_choice',
        options: ['', ''],
        correctAnswer: 0,
        difficulty: 'medium',
        explanation: '',
        timeLimit: 30,
        quizID: 1,
        userID: this.sessionStore.userID,

        creatorFirstName: this.sessionStore.firstName || '',
        creatorLastName: this.sessionStore.lastName || '',
      };
      this.showModal = true;
    },

    openEdit(id) {
      const q = this.questions.find(x => x.id === id);
      if (!q) return;

      this.selectedQuestion = JSON.parse(JSON.stringify(q));
      if (!Array.isArray(this.selectedQuestion.options)) this.selectedQuestion.options = [];

      this.showModal = true;
      this.$nextTick(() => {
        const ta = document.querySelector('.modal-content textarea');
        if (ta) ta.focus();
      });
    },

    editQuestion(id) {
      this.openEdit(id);
    },

    closeModal() {
      this.selectedQuestion = null;
      this.showModal = false;
    },

    async saveQuestion() {
      if (!this.selectedQuestion) return;

      if (!this.selectedQuestion.text || this.selectedQuestion.text.trim() === '') {
        alert('Bitte Fragetext eingeben.');
        return;
      }

      let optionsPayload = [];

      if (this.selectedQuestion.type === 'multiple_choice') {
        const opts = (this.selectedQuestion.options || [])
          .map(o => (typeof o === 'string' ? o : (o && o.text) || ''))
          .map(o => o.trim())
          .filter(o => o !== '');

        if (opts.length < 2) {
          alert('Mindestens zwei Antwortoptionen erforderlich.');
          return;
        }

        this.selectedQuestion.options = opts;

        if (
          typeof this.selectedQuestion.correctAnswer !== 'number' ||
          this.selectedQuestion.correctAnswer < 0 ||
          this.selectedQuestion.correctAnswer >= opts.length
        ) {
          this.selectedQuestion.correctAnswer = 0;
        }

        optionsPayload = this.selectedQuestion.options.map((opt, idx) => ({
          text: opt,
          isCorrect: idx === this.selectedQuestion.correctAnswer,
        }));
      } else if (this.selectedQuestion.type === 'true_false') {
        let opts = (this.selectedQuestion.options || [])
          .map(o => (typeof o === 'string' ? o : (o && o.text) || ''))
          .map(o => o.trim())
          .filter(o => o !== '');

        if (opts.length !== 2) opts = ['Wahr', 'Falsch'];
        this.selectedQuestion.options = opts;

        if (
          typeof this.selectedQuestion.correctAnswer !== 'number' ||
          this.selectedQuestion.correctAnswer < 0 ||
          this.selectedQuestion.correctAnswer >= opts.length
        ) {
          this.selectedQuestion.correctAnswer = 0;
        }

        optionsPayload = this.selectedQuestion.options.map((opt, idx) => ({
          text: opt,
          isCorrect: idx === this.selectedQuestion.correctAnswer,
        }));
      } else if (this.selectedQuestion.type === 'text_input') {
        const txt = (this.selectedQuestion.correctAnswerText || '').trim();
        if (!txt) {
          alert('Bitte eine richtige Antwort (Text) eingeben.');
          return;
        }
        optionsPayload = [{ text: txt, isCorrect: true }];
      }

      try {
        const payload = {
          questionID: this.selectedQuestion.id,
          quizID: this.selectedQuestion.quizID || 1,
          text: this.selectedQuestion.text,
          type: this.selectedQuestion.type,
          options: optionsPayload,
          difficulty: this.selectedQuestion.difficulty,
          explanation: this.selectedQuestion.explanation,
          timeLimit: this.selectedQuestion.timeLimit,
        };

        let response;
        if (!this.selectedQuestion.id || String(this.selectedQuestion.id).startsWith('temp')) {
          response = await axios.post('/api/addQuestion.php', payload);
        } else {
          response = await axios.post('/api/updateQuestion.php', payload);
        }

        const data = response.data;

        if (data && data.ok) {
          if (!this.selectedQuestion.id || String(this.selectedQuestion.id).startsWith('temp')) {
            this.selectedQuestion.id = data.questionID;

            // lokal direkt korrekt setzen (sonst wäre erst nach reload sichtbar)
            this.selectedQuestion.userID = this.sessionStore.userID;
            this.selectedQuestion.creatorFirstName = this.sessionStore.firstName || '';
            this.selectedQuestion.creatorLastName  = this.sessionStore.lastName || '';

            this.questions.unshift(JSON.parse(JSON.stringify(this.selectedQuestion)));
          } else {
            const idx = this.questions.findIndex(q => q.id === this.selectedQuestion.id);
            if (idx !== -1) {
              this.questions.splice(idx, 1, JSON.parse(JSON.stringify(this.selectedQuestion)));
            }
          }
          alert('Frage erfolgreich gespeichert.');
        } else {
          alert('Fehler beim Speichern der Frage (API-Fehler).');
        }
      } catch (err) {
        console.error(err);
        alert('Fehler beim Speichern der Frage (Netzwerk/Server).');
      }

      this.closeModal();
    },

    async deleteQuestion(id) {
      if (!confirm('Sind Sie sicher, dass Sie diese Frage löschen möchten?')) return;

      try {
        const payload = { questionID: id };
        const { data } = await axios.post('/api/deleteQuestion.php', payload);

        if (data && data.ok) {
          this.questions = this.questions.filter(q => q.id !== id);
          alert('Frage erfolgreich gelöscht.');
        } else {
          alert('Fehler beim Löschen (Backend-Fehler).');
        }
      } catch (err) {
        console.error(err);
        alert('Fehler beim Löschen (Netzwerk/Server).');
      }
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
    },
    showQuizManagementPage() {
      router.push('/quizmanagement');
    },

    // Rollen-Checks
    canEditQuestion(q) {
      const me = this.sessionStore.userID;
      const role = this.sessionStore.userRole;
      if (!me) return false;
      if (role === 'Admin') return true;
      return Number(q.userID) === Number(me);
    },
    canDeleteQuestion(q) {
      return this.canEditQuestion(q);
    },
  },
};
</script>
