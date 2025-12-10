<template>
  <DashboardNavbar />
  <Teleport to="body">
    <div v-if="isShowingCreateQuizModal" class="modal">
      <create-quiz-modal v-model="isShowingCreateQuizModal" @created="fetchQuizzes" />
    </div>
    <div v-if="isShowingEditQuizModal" class="modal">
      <edit-quiz-modal
          v-model="isShowingEditQuizModal"
          :quizID="selectedQuizID"
          :quizzes="quizzes"
          :questions="selectedQuizQuestions"
          @updated="onQuizUpdated"
      />
    </div>
  </Teleport>

  <div class="container" style="margin-top: 24px;">
    <div style="text-align: right; margin-bottom: 12px;">
      <button class="btn btn-primary" @click.prevent="showQuestionPage()">📝 Fragenverwaltung</button>
      <button class="btn btn-primary" @click.prevent="showCreateQuizModal()">➕ Neues Quiz erstellen</button>
    </div>

    <h2 style="margin-bottom: 24px; margin-left: 50px">Gespeicherte Quiz:</h2>

    <div v-if="loading" style="margin-left: 50px;">⏳ Lade Quiz-Daten...</div>
    <div v-else-if="error" style="margin-left: 50px; color: red;">{{ error }}</div>

    <div v-else style="overflow-y: auto; margin-bottom: 24px; margin-left: 50px; margin-right: 50px;">
      <div v-for="quiz in quizzes" :key="quiz.quizID" class="card"
           style="border: 2px solid #007bff; margin-bottom: 16px; position: relative;">
        <div style="cursor: pointer;" @click.prevent="editQuiz(quiz.quizID)">
          <div style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start;">
            <div>
              <h3>
                {{ quiz.title }}
                <small style="font-weight: normal; color: #666;">
                  Ersteller: {{ quiz.creatorName || 'Unbekannt' }}
                </small>
              </h3>
              <p>{{ quiz.quiz_description }}</p>
              <p>Kategorie: {{ quiz.category }} | Zeitlimit: {{ quiz.time_limit }}s</p>

              <button v-if="Number(quiz.userID) === Number(sessionStore.userID)"
                      @click.stop="deleteQuiz(quiz.quizID)"
                      class="btn btn-danger"
                      style="position:absolute;bottom:12px;right:12px;padding:8px 12px;font-size:14px;">
                🗑️ Quiz löschen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import DashboardNavbar from './DashboardNavbar.vue';
import CreateQuizModal from './CreateQuizModal.vue';
import EditQuizModal from './EditQuizModal.vue';
import { useSessionStore } from '@/stores/session'
import router from '@/router/index'
import axios from 'axios'

export default {
  components: {
    DashboardNavbar,
    CreateQuizModal,
    EditQuizModal
  },

  data() {
    const sessionStore = useSessionStore()
    return {
      sessionStore,
      quizzes: [],
      questions: [],
      selectedQuizID: null,
      selectedQuizQuestions: [],
      isShowingCreateQuizModal: false,
      isShowingEditQuizModal: false,
      loading: false,
      error: null
    }
  },
  async mounted() {
    await this.fetchQuizzes();
  },
  methods: {
    async fetchQuizzes() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/api/getQuizzes.php');
        this.quizzes = Array.isArray(response.data) ? response.data : [];
      } catch (e) {
        console.error("Fehler beim Laden der Quizzes:", e);
        this.error = "Konnte Quiz-Liste nicht laden.";
      } finally {
        this.loading = false;
      }
    },

    // Diese Funktion lädt die Fragen für das Modal
    async loadQuestionsForQuiz(quizID) {
      console.log("Lade Fragen für Quiz ID:", quizID);
      try {
        const response = await axios.get('/api/getQuizQuestions.php', {
          params: { quizID: quizID }
        });

        console.log("Antwort von getQuizQuestions:", response.data);

        if (response.data && Array.isArray(response.data.questions)) {
          // Wir erzwingen ein neues Array, damit Vue das Update bemerkt
          this.selectedQuizQuestions = [...response.data.questions.map(q => ({
            questionID: q.questionId || q.questionID,
            question_text: q.questionText || q.question_text,
            question_type: q.questionType || q.question_type,
            difficulty: q.difficulty,
            time_limit: q.timeLimit || q.time_limit,
            explanation: q.explanation
          }))];
          console.log("Gesetzte selectedQuizQuestions:", this.selectedQuizQuestions);
        } else {
          this.selectedQuizQuestions = [];
        }
      } catch (e) {
        console.error("Fehler beim Laden der Quiz-Fragen:", e);
      }
    },

    async editQuiz(quizID) {
      this.selectedQuizID = quizID;
      this.selectedQuizQuestions = []; // Reset vor dem Laden
      await this.loadQuestionsForQuiz(quizID);
      this.isShowingEditQuizModal = true;
    },

    // Wird aufgerufen, wenn im Modal etwas passiert (Hinzufügen/Entfernen)
    async onQuizUpdated() {
      console.log("Quiz Updated Event empfangen!");
      // 1. Metadaten neu laden
      await this.fetchQuizzes();

      // 2. Fragen neu laden (WICHTIG für das Live-Update der Liste)
      if (this.selectedQuizID) {
        await this.loadQuestionsForQuiz(this.selectedQuizID);
      }
    },

    showCreateQuizModal() {
      this.isShowingCreateQuizModal = true;
    },

    async deleteQuiz(quizID) {
      if (!confirm('Möchtest du dieses Quiz wirklich löschen?')) return;
      alert("Löschen-Funktion muss im Backend noch implementiert werden.");
    },

    showQuestionPage() {
      router.push('/questions')
    }
  }
}
</script>

<style scoped>
.modal {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
</style>