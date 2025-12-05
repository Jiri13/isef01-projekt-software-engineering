<template>
    <DashboardNavbar />
    <Teleport to="body">
        <div v-if="isShowingCreateQuizModal" class="modal">
            <create-quiz-modal v-model="isShowingCreateQuizModal" />
        </div>
        <div v-if="isShowingEditQuizModal && this.selectedQuizQuestions != []" class="modal">
            <edit-quiz-modal v-model="isShowingEditQuizModal" :quizID="selectedQuizID" :quizzes="quizzes"
                :questions="selectedQuizQuestions" />
        </div>
    </Teleport>

    <div class="container" style="margin-top: 24px;">
        <div style="text-align: right; margin-bottom: 12px;">
            <button class="btn btn-primary" @click.prevent="showQuestionPage()">📝 Fragenverwaltung</button>
            <button class="btn btn-primary" @click.prevent="showCreateQuizModal()">➕ Neues
                Quiz
                erstellen</button>
        </div>
        <h2 style="margin-bottom: 24px; margin-left: 50px">Gespeicherte Quiz:</h2>
        <div style=" overflow-y: auto; margin-bottom: 24px; margin-left: 50px; margin-right: 50px;">
            <div v-for="quiz in quizzes" :key="quiz.quizID" class="card"
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
                            <button v-if="quiz.userID == sessionStore.userID" @click.stop="deleteQuiz(quiz.quizID)"
                                class="btn btn-danger"
                                style="position:absolute;bottom:12px;right:12px;padding:8px 12px;font-size:14px;">
                                🗑️ Quiz löschen
                            </button>
                            <button v-else @click.stop="removeQuiz(quiz.quizID)" class="btn btn-danger"
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
import questions from '../files/questions.json'   // <DATENBANK>
//import quizzes from '../files/quizzes.json'

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
            quizzes: [],          // ← kommt jetzt aus der DB
            questions,
            selectedQuizID: null,
            selectedQuizTitle: '',
            selectedQuizQuestions: [],
            isShowingCreateQuizModal: false,
            isShowingEditQuizModal: false,
            loading: false,
            error: null
        }
    },

    async mounted() {
    this.loading = true
    this.error = null

        try {
            const { data } = await axios.get('/api/getQuizzes.php', {
                params: {
                    userID: this.sessionStore.userID  // kann optional genutzt werden
                }
            })

            if (Array.isArray(data)) {
                this.quizzes = data
            } else {
                console.error('Unerwartetes Quiz-Format:', data)
                this.error = 'Unerwartete Antwort vom Server.'
            }
        } catch (e) {
            console.error('Fehler beim Laden der Quizze:', e)
            this.error = e?.response?.data?.error || 'Konnte Quizze nicht laden.'
        } finally {
            this.loading = false
        }
    },

    methods: {
        showCreateQuizModal() {
            this.isShowingCreateQuizModal = true;
        },
        onQuizCreated(newQuiz) {
            // neues Quiz oben in die Liste einsetzen
            if (newQuiz && newQuiz.quizID) {
                this.quizzes.unshift(newQuiz)
            }
        },
        editQuiz(quizID) {
            this.selectedQuizQuestions = [];
            this.selectedQuizID = quizID
            questions.forEach(question => {
                if (question.quizID == quizID) {
                    this.selectedQuizQuestions.push(question);
                }

            });
            this.isShowingEditQuizModal = true;
        },
       async deleteQuiz(quizID) {
            if (!confirm('Möchtest du dieses Quiz wirklich löschen?')) return;

            try {
                const { data } = await axios.post('/api/deleteQuiz.php', { quizID });

                // Wenn der Server 200 liefert, gehen wir mal davon aus, dass es geklappt hat:
                this.quizzes = this.quizzes.filter(q => q.quizID !== quizID);
                alert('Quiz gelöscht.');

                // Wenn du trotzdem auf data.ok bestehen willst:
                // if (data && data.ok) {
                //   this.quizzes = this.quizzes.filter(q => q.quizID !== quizID);
                //   alert('Quiz gelöscht.');
                // } else {
                //   alert('Fehler beim Löschen: ' + (data.error || 'Unbekannt'));
                // }

            } catch (err) {
                console.error('Fehler beim Löschen:', err);
                alert('Serverfehler: ' + err.message);
            }
        },


        removeQuiz(quizID) {
            if (!confirm('Möchtest du dieses Quiz aus deiner Liste entfernen?')) return;

            this.quizzes = this.quizzes.filter(q => q.quizID !== quizID);
            alert('Nur aus deiner Liste entfernt.');
        },
        showQuestionPage() {
            router.push('/questions')
        }
    }
}
</script>

<style scoped>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
</style>