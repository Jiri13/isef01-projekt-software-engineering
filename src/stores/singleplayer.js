import { defineStore } from 'pinia'

export const useSingleplayerStore = defineStore('singleplayer', {
    state: () => ({
        quiz: null,
        questions: [],
        currentQuestionIndex: 0,
        hasStarted: false,
        finished: false,
        score: 0,
    }),

    getters: {
        currentQuestion(state) {
            return state.questions[state.currentQuestionIndex] ?? null
        },
        hasQuestions(state) {
            return Array.isArray(state.questions) && state.questions.length > 0
        }
    },

    actions: {
        startWithQuestions(quiz, questions) {
            if (!Array.isArray(questions) || questions.length === 0) {
                throw new Error('No questions to start')
            }
            this.quiz = quiz
            this.questions = questions
            this.currentQuestionIndex = 0
            this.hasStarted = true
            this.finished = false
            this.score = 0
        },

        nextQuestion() {
            if (this.currentQuestionIndex + 1 < this.questions.length) {
                this.currentQuestionIndex++
            } else {
                this.finished = true
            }
        },

        reset() {
            this.quiz = null
            this.questions = []
            this.currentQuestionIndex = 0
            this.hasStarted = false
            this.finished = false
            this.score = 0
        }
    }
})
