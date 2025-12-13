<template>
  <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;max-height:90vh;overflow-y:auto;">
    <h2 style="margin-bottom:16px;">➕ Neues Quiz erstellen</h2>

    <form @submit.prevent="createQuiz">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input class="form-input" v-model="form.name" required placeholder="z.B. IT-Grundlagen" />
      </div>

      <div class="form-group">
        <label class="form-label">Fach/Modul</label>
        <input class="form-input" v-model="form.category" required placeholder="z.B. Informatik" />
      </div>

      <div class="form-group">
        <label class="form-label">Beschreibung</label>
        <input class="form-input" v-model="form.description" required placeholder="Kurze Beschreibung..." />
      </div>

      <div class="form-group">
        <label class="form-label">Zeitlimit pro Frage (Sekunden)</label>
        <input type="number" min="5" max="300" class="form-input" v-model.number="form.timeLimit" />
      </div>

      <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">

      <label class="form-label">Fragen hinzufügen ({{ form.questions.length }} ausgewählt)</label>

      <button type="button" class="btn btn-secondary" @click="isAllQuestionsCollapsed = !isAllQuestionsCollapsed" style="margin-bottom:12px;">
        {{ isAllQuestionsCollapsed ? 'Fragen anzeigen' : 'Verbergen' }}
      </button>

      <div v-show="!isAllQuestionsCollapsed" class="collapsible-content">
        <div v-if="allQuestions.length === 0" style="padding:10px; text-align:center;">Lade Fragen...</div>

        <div v-for="q in allQuestions" :key="q.id" class="card" style="margin-bottom: 8px; padding: 10px; border: 1px solid #ddd;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1;">
              <strong style="color: #007bff;">{{ q.text }}</strong>
              <br>
              <small class="badge" style="background:#eee;">{{ q.difficulty }}</small>
            </div>

            <button v-if="!isQuestionSelected(q.id)" type="button" class="btn btn-primary"
                    @click.stop="addQuestion(q.id)" style="font-size: 12px; padding: 4px 8px;">
              ➕ Hinzufügen
            </button>
            <button v-else type="button" class="btn btn-danger"
                    @click.stop="removeQuestion(q.id)" style="font-size: 12px; padding: 4px 8px;">
              🗑️ Entfernen
            </button>
          </div>
        </div>
      </div>

      <div style="display:flex; gap:12px; margin-top:24px;">
        <button type="button" class="btn btn-secondary" @click="close" style="flex:1;">Abbrechen</button>
        <button type="submit" class="btn btn-primary" style="flex:1;">Quiz erstellen</button>
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
      allQuestions: [],
      isAllQuestionsCollapsed: true,
      form: {
        name: '',
        category: '',
        description: '',
        timeLimit: 30,
        questions: []
      }
    };
  },
  watch: {
    modelValue(val) {
      if (val) {
        this.loadQuestions();
        // Formular resetten beim Öffnen
        this.resetForm();
      }
    }
  },
  mounted() {
    if(this.modelValue) this.loadQuestions();
  },
  methods: {
    resetForm() {
      this.form = {
        name: '',
        category: '',
        description: '',
        timeLimit: 30,
        questions: []
      };
      this.isAllQuestionsCollapsed = true;
    },

    async loadQuestions() {
      try {
        const res = await axios.get('/api/getQuestions.php');
        this.allQuestions = Array.isArray(res.data) ? res.data : [];
      } catch (e) {
        console.error("Fehler beim Laden der Fragen:", e);
        this.allQuestions = [];
      }
    },

    isQuestionSelected(id) {
      return this.form.questions.includes(id);
    },

    addQuestion(id) {
      if (!this.form.questions.includes(id)) {
        this.form.questions.push(id);
      }
    },

    removeQuestion(id) {
      this.form.questions = this.form.questions.filter(qId => qId !== id);
    },

    async createQuiz() {
      if (!this.form.name) return alert("Bitte einen Namen eingeben.");

      try {
        const payload = {
          title: this.form.name,
          category: this.form.category,
          description: this.form.description,
          timeLimit: this.form.timeLimit,
          questions: this.form.questions
        };


        const res = await axios.post('/api/addQuiz.php', payload);

        if (res.data && res.data.ok) {
          alert("Quiz erfolgreich erstellt!");
          this.$emit('created');
          this.close();
        } else {
          alert("Fehler beim Erstellen des Quiz.");
        }
      } catch (e) {
        console.error(e);
        alert("Serverfehler beim Erstellen.");
      }
    },

    close() {
      this.$emit('update:modelValue', false)
    }
  }
}
</script>

<style scoped>
.modal-inner {
  /* Styles wie zuvor */
}
.badge {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 10px;
  margin-right: 6px;
  display: inline-block;
}
.collapsible-content {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #eee;
  padding: 8px;
  border-radius: 4px;
}
</style>