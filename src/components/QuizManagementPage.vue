<template>
    <DashboardNavbar />

    <Teleport to="body">
        <div v-if="isShowingCreateQuizModal" class="modal">
            <create-quiz-modal v-model="isShowingCreateQuizModal"/>
        </div>
        <div v-if="isShowingEditQuizModal" class="modal">
            <edit-quiz-modal v-model="isShowingEditQuizModal" :quizID="selectedQuizID" :quizzes="quizzes"/>
        </div>
    </Teleport>


    <div div class="container" style="margin-top: 24px;">
        <button class="btn btn-primary" style="margin-left: 50px" @click.prevent="showCreateQuizModal()">➕ Neues Quiz
            erstellen</button>
        <h2 style="margin-bottom: 24px; margin-left: 50px">Gespeicherte Quiz:</h2>
        <div style=" overflow-y: auto; margin-bottom: 24px; margin-left: 50px; margin-right: 50px;">
            <div v-for="quiz in quizzes" :key="quiz.quizID" class="card"
                :class="{ selected: quiz.quizID === selectedQuizID && mode === 'quiz' }"
                style="border: 2px solid #007bff; margin-bottom: 16px; position: relative;">
                <div style="cursor: pointer;" @click.prevent="editQuiz(quiz.quizID)">
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start;">
                        <div>
                            <h3>
                                {{ quiz.title }}
                                <small style="font-weight: normal; color: #666;">Ersteller: {{ quiz.creatorName
                                    }}</small>
                            </h3>
                            <p>{{ quiz.quiz_description }}</p>
                            <p>Studiengang: {{ quiz.category }}</p>
                            <button v-if="quiz.userID == sessionStore.userID" @click.prevent="deleteQuiz(quiz.quizID)"
                                class="btn btn-danger"
                                style="position:absolute;bottom:12px;right:12px;padding:8px 12px;font-size:14px;">
                                🗑️ Quiz löschen
                            </button>
                            <button v-else @click.prevent="removeQuiz(quiz.quizID)" class="btn btn-danger"
                                style="position:absolute;bottom:12px;right:12px;padding:8px 12px;font-size:14px;">
                                ❌ Quiz entfernen
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
import { useSingleplayerStore } from '@/stores/singleplayer'
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
        const singleplayerStore = useSingleplayerStore()
        return {
            sessionStore,
            singleplayerStore,
            quizzes: [],
            questions: [],
            selectedQuizID: null,
            selectedQuizTitle: '',
            isShowingCreateQuizModal: false,
            isShowingEditQuizModal: false,
            loading: false,
            error: null
        }
    },
    async mounted() {
    try {
      this.loading = true
      const { data } = await axios.get('/api/getQuizzes.php', {
        params: { userID: this.sessionStore.userID }
      })
      this.quizzes = Array.isArray(data) ? data : []
    } catch (e) {
      this.error = e?.response?.data?.error || 'Konnte Quizzes nicht laden.'
      console.error(e)
    } finally {
      this.loading = false
    }
  },
  methods:{
    showCreateQuizModal(){
        this.isShowingCreateQuizModal = true;
    },
    editQuiz(quizID){
        this.selectedQuizID = quizID
        console.log(quizID)
        console.log(typeof quizID)
        console.log(typeof quizzes)
        this.isShowingEditQuizModal = true;
    },
    deleteQuiz(quizID) {
        if (confirm('Möchtest du dieses Quiz wirklich löschen?')) {
            console.log("Quiz gelöscht");
        }
    },
    removeQuiz(quizID) {
        if (confirm('Möchtest du dieses Quiz aus deiner Liste entfernen?')) {
            console.log("Quiz gelöscht");
        }
    }
  }
}
</script>

<style scoped>
.modal{
  position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;
}
</style>