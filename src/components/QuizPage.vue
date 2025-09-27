<!--JK 27.09.25: Nicht funktional. War für mich zum Üben. Code ggf. als Einstiegshilfe für richtige Funktionalität-->

<template>
    <h1>Quiz-Page</h1>
    <div class="container" v-if="!quizStarted">
        <button @click.prevent="startQuiz">Start Quiz</button>
    </div>
    <div class="container" v-if="quizStarted">
        <div class="container text-center bg-primary-subtle">
            <p>{{ questions[currentQuestionID].question }}</p>
        </div>
        <div class="row m-3">
            <button @click.prevent="evaluateQuestion(questions[currentQuestionID].answer0)">{{
                questions[currentQuestionID].answer0 }}</button>
        </div>
        <div class="row m-3">
            <button @click.prevent="evaluateQuestion(questions[currentQuestionID].answer1)">{{
                questions[currentQuestionID].answer1 }}</button>
        </div>
        <div class="row m-3">
            <button @click.prevent="evaluateQuestion(questions[currentQuestionID].answer2)">{{
                questions[currentQuestionID].answer2 }}</button>
        </div>
        <div class="row m-3">
            <button @click.prevent="evaluateQuestion(questions[currentQuestionID].answer3)">{{
                questions[currentQuestionID].answer3 }}</button>
        </div>
        <div class="row m-3" v-if="quizHalted">
            <button class="btn btn-primary" @click.prevent="nextQuestion">{{ weiterButtonText }}</button>
        </div>
    </div>
</template>

<script>
import questionData from '../files/questions.json'
import router from "@/router/index"

export default {
    data() {
        return {
            questions: questionData,
            quizStarted: false,
            currentQuestionID: 0,
            quizHalted: false,
            weiterButtonText: 'Weiter'
        }
    },
    methods: {
        // async getQuestions() {
        //     let questionFile = await fetch('data/questions.json')
        //     let questionData = await questionFile.json();
        //     this.questions = questionData
        // },
        startQuiz() {
            this.quizStarted = true;
        },
        evaluateQuestion(answer) {
            this.quizHalted = true
            if (answer == this.questions[this.currentQuestionID].correctAnswer) {
                console.log("Correct!")
            }
            else if (answer != this.questions[this.currentQuestionID].correctAnswer) {
                console.log("Wrong!")
            }

            if (this.currentQuestionID >= this.questions.length-1){
                this.weiterButtonText = 'Zum Dashboard'
            }
        },
        nextQuestion(){
            if(this.currentQuestionID < this.questions.length-1){
                this.currentQuestionID++
                this.quizHalted = false
            }
            else if(this.currentQuestionID >= this.questions.length-1){
                router.push('/dashboard')
            }
        }
    }
}
</script>