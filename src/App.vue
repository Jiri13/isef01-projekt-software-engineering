<script setup></script>

<template>
  <router-view></router-view>
</template>

<script>
import { useSessionStore } from '@/stores/session';

import LoginPage from './components/LoginPage.vue';
import LandingPage from './components/LandingPage.vue';

export default {
  components: {
    LoginPage, LandingPage
  },
  data() {
    const sessionStore = useSessionStore()

    return {
      sessionStore,
      activePage: 0,
    }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: system-ui, sans-serif;
  background: #f8f9fa;
  color: #333;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.card.selected {
  border: 4px solid #007bff !important;
  box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  display: inline-block;
  transition: all 0.2s;
  margin: 4px;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
}

.form-input {
  width: 100%;
  padding: 12px;
  border: 2px solid #ddd;
  border-radius: 6px;
  font-size: 16px;
}

.form-input:focus {
  outline: none;
  border-color: #007bff;
}

.alert {
  padding: 12px;
  border-radius: 6px;
  margin: 10px 0;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.navbar {
  background: white;
  border-bottom: 1px solid #ddd;
  padding: 16px 0;
  margin-bottom: 20px;
}

.navbar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.navbar-brand {
  font-size: 24px;
  font-weight: 700;
  color: #007bff;
}

.nav-links {
  display: flex;
  gap: 20px;
  align-items: center;
  flex-wrap: wrap;
}

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

.modal-content {
  background: white;
  border-radius: 8px;
  padding: 24px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.answer-option {
  padding: 12px;
  margin: 8px 0;
  border: 2px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.answer-option:hover {
  background: #f0f8ff;
  border-color: #007bff;
}

.answer-option.selected {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.answer-option.correct {
  background: #28a745;
  color: white;
  border-color: #28a745;
  pointer-events: none;
}

.answer-option.incorrect {
  background: #dc3545;
  color: white;
  border-color: #dc3545;
  pointer-events: none;
}

.singleplayer-explanation {
  margin-top: 16px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 4px solid #007bff;
  font-weight: bold;
}

.disabled-options {
  pointer-events: none;
}

.difficulty-easy {
  background: #d4edda;
  color: #155724;
}

.difficulty-medium {
  background: #fff3cd;
  color: #856404;
}

.difficulty-hard {
  background: #f8d7da;
  color: #721c24;
}

.selected-difficulty {
  border: 4px solid #007bff !important;
  box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}
</style>
