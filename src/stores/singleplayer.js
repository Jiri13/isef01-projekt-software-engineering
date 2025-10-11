import { defineStore } from "pinia";

export const useSingleplayerStore = defineStore('singleplayer', {
    state: () => ({
            hasStarted: false,
            quizID: null,
            quiz: null,
            questions: [],
            currentQuestionIndex: null,
            score: 0,
            finished: false
    })
    ,
    persist: {
        paths: ['hasStarted', 'quizID', 'quiz', 'questions', 'currentQuestionIndex', 'score', 'finished']
    }
})