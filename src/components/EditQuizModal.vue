<template>
  <div v-if="quizData" class="modal-inner"
       style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;max-height:90vh;overflow-y:auto;">

    <h2 style="margin-bottom: 12px;">✏️ Quiz bearbeiten: {{ quizData.title }}</h2>

    <form @submit.prevent="saveQuizMetadata">
      <div class="form-group"><label class="form-label">Name</label><input class="form-input" v-model="form.name" required /></div>
      <div class="form-group"><label class="form-label">Kategorie</label><input class="form-input" v-model="form.category" required /></div>
      <div class="form-group"><label class="form-label">Beschreibung</label><input class="form-input" v-model="form.description" required /></div>
      <div class="form-group"><label class="form-label">Zeitlimit</label><input type="number" class="form-input" v-model.number="form.timeLimit" /></div>
      <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:20px;">💾 Speichern</button>
    </form>

    <hr>

    <h3>Fragen im Quiz ({{ questions ? questions.length : 0 }})</h3>
    <button class="btn btn-secondary" @click="isQuizQuestionsCollapsed = !isQuizQuestionsCollapsed" style="margin-bottom:10px; font-size:12px;">
      {{ isQuizQuestionsCollapsed ? 'Anzeigen' : 'Verbergen' }}
    </button>

    <div v-show="!isQuizQuestionsCollapsed" class="list-container">
      <div v-if="!questions || questions.length === 0" style="padding:10px; text-align:center; color:#666;">Noch keine Fragen zugeordnet.</div>
      <div v-else v-for="q in questions" :key="q.questionID || q.id" class="card-item" style="border-left: 4px solid #007bff;">
        <div style="flex: 1;">
          <strong>{{ q.question_text || q.text }}</strong>
          <span class="badge">{{ q.difficulty }}</span>
        </div>
        <button type="button" class="btn btn-danger" @click.stop="removeQuestion(q.questionID || q.id)" style="font-size: 12px;">Entfernen</button>
      </div>
    </div>

    <h3 style="margin-top:20px;">Fragen hinzufügen</h3>
    <button class="btn btn-secondary" @click="isAllQuestionsCollapsed = !isAllQuestionsCollapsed" style="margin-bottom:10px; font-size:12px;">
      {{ isAllQuestionsCollapsed ? 'Fragenpool anzeigen' : 'Verbergen' }}
    </button>

    <div v-show="!isAllQuestionsCollapsed" class="list-container">
      <div v-if="isLoadingQuestions" style="padding:10px; text-align:center; color:#007bff;">⏳ Lade Fragen...</div>
      <div v-else-if="!allQuestions || allQuestions.length === 0" style="padding:10px; text-align:center; color:red;">❌ Keine Fragen im Pool gefunden.</div>

      <div v-else v-for="q in allQuestions" :key="q.id" class="card-item">
        <div style="flex: 1;">
          <strong>{{ q.text }}</strong>
          <span class="badge">{{ q.difficulty }}</span>
        </div>

        <button v-if="!isQuestionInQuiz(q.id)" type="button" class="btn btn-primary" @click.stop="addQuestion(q.id)" style="font-size: 12px;">
          ➕ Hinzufügen
        </button>
      </div>
    </div>

    <div style="display:flex; justify-content:flex-end; margin-top:20px;">
      <button class="btn btn-secondary" @click="close">Schließen</button>
    </div>
  </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import axios from 'axios'

export default {
  props: {
    modelValue: { type: Boolean },
    quizID: { type: Number },
    quizzes: { type: Array, default: () => [] },
    questions: { type: Array, default: () => [] } // Diese Prop kommt vom Parent
  },
  emits: ['update:modelValue', 'updated'],
  data() {
    return {
      session: useSessionStore(),
      isQuizQuestionsCollapsed: false,
      isAllQuestionsCollapsed: false,
      allQuestions: [],
      isLoadingQuestions: false,
      form: { name: '', category: '', description: '', timeLimit: 0 }
    }
  },
  computed: {
    quizData() {
      if (!this.quizzes) return null;
      return this.quizzes.find(q => Number(q.quizID) === Number(this.quizID)) || null;
    }
  },
  watch: {
    quizID: {
      immediate: true,
      handler() { this.initForm(); }
    }
  },
  mounted() {
    this.loadAllQuestions();
    this.initForm();
  },
  methods: {
    initForm() {
      if (this.quizData) {
        this.form.name = this.quizData.title;
        this.form.category = this.quizData.category;
        this.form.description = this.quizData.quiz_description;
        this.form.timeLimit = this.quizData.time_limit;
      }
    },
    async loadAllQuestions() {
      this.isLoadingQuestions = true;
      try {
        const res = await axios.get('/api/getQuestions.php');
        this.allQuestions = Array.isArray(res.data) ? res.data : [];
      } catch (e) {
        console.error("Fehler beim Laden aller Fragen:", e);
        this.allQuestions = [];
      } finally {
        this.isLoadingQuestions = false;
      }
    },
    isQuestionInQuiz(poolId) {
      if (!this.questions || !Array.isArray(this.questions)) return false;
      // Prüft ob die ID aus dem Pool (poolId) schon in der Liste der zugeordneten Fragen (this.questions) ist
      return this.questions.some(q => {
        const qID = q.questionID || q.id;
        return Number(qID) === Number(poolId);
      });
    },
    async addQuestion(qId) {
      try {
        await axios.post('/api/assignQuestionToQuiz.php', {
          quizID: this.quizID,
          questionID: qId
        });
        // WICHTIG: Parent sagen "Lade neu!", damit die Liste oben und der Button sich updaten
        this.$emit('updated');
      } catch (e) { console.error(e); }
    },
    async removeQuestion(qId) {
      try {
        await axios.post('/api/removeQuestionFromQuiz.php', {
          quizID: this.quizID,
          questionID: qId
        });
        this.$emit('updated');
      } catch (e) { console.error(e); }
    },
    async saveQuizMetadata() {
      try {
        await axios.post('/api/updateQuiz.php', {
          quizID: this.quizID,
          title: this.form.name,
          category: this.form.category,
          description: this.form.description,
          timeLimit: this.form.timeLimit
        });
        alert("Gespeichert!");
        this.$emit('updated');
      } catch (e) { console.error(e); }
    },
    close() { this.$emit('update:modelValue', false); }
  }
}
</script>

<style scoped>
.list-container {
  max-height: 250px; overflow-y: auto; border: 1px solid #eee; padding: 8px; border-radius: 4px;
}
.card-item {
  display: flex; justify-content: space-between; align-items: center;
  background: #fff; border: 1px solid #ddd; padding: 8px; margin-bottom: 6px; border-radius: 4px;
}
.badge {
  background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;
}
</style>