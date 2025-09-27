<template>
    <div class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 24px;">🎮 Einzelspieler - Schwierigkeit wählen</h2>

            <div class="form-group">
                <label class="form-label">Wählen Sie den Schwierigkeitsgrad:</label>
                <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                    <label class="difficulty-easy selected-difficulty"
                        style="cursor: pointer; padding: 16px; border: 2px solid #28a745; border-radius: 8px; text-align: center;"
                        onclick="selectDifficulty(this)">
                        <input type="radio" name="singlePlayerDifficulty" value="easy" checked style="display: none;">
                        <div style="font-weight: 600;">🟢 Leicht</div>
                    </label>
                    <label class="difficulty-medium"
                        style="cursor: pointer; padding: 16px; border: 2px solid #ffc107; border-radius: 8px; text-align: center;"
                        onclick="selectDifficulty(this)">
                        <input type="radio" name="singlePlayerDifficulty" value="medium" style="display: none;">
                        <div style="font-weight: 600;">🟡 Mittel</div>
                    </label>
                    <label class="difficulty-hard"
                        style="cursor: pointer; padding: 16px; border: 2px solid #dc3545; border-radius: 8px; text-align: center;"
                        onclick="selectDifficulty(this)">
                        <input type="radio" name="singlePlayerDifficulty" value="hard" style="display: none;">
                        <div style="font-weight: 600;">🔴 Schwer</div>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-secondary" @click.prevent="$emit('update:modelValue', false)"
                    style="flex: 1;">
                    Abbrechen
                </button>
                <button type="button" class="btn btn-primary" onclick="startSinglePlayerWithDifficulty()"
                    style="flex: 1;">
                    ✅ Spiel starten
                </button>
                <button @click.prevent="DebugFunction">Debug-Button</button>
            </div>
        </div>
    </div>
</template>

<script>
// JK: Für Prop-Manipulation mittels V-model https://vuejs.org/guide/components/v-model.html
// Notwendig, da das Modal als Child von DashboardPage.vue aufgerufen wird und somit über dessen Anzeige entscheidet. Somit muss
// das Modal den Wert in der Dashboardkomponenten ändern
export default {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    methods: {
        DebugFunction() {
            console.log("Hello");
            console.log(this.showSingleplayerModal)
        }
    }
}

</script>