# 🎯 IU Kooperatives Quiz System

Ein webbasiertes Quiz-System für Lernzwecke.  
Das Projekt läuft vollständig im Browser (HTML, CSS, JavaScript) und benötigt **keine externe Installation**.  

## 🚀 Features
- **Login mit Demo-Accounts**
  - Mehrere Demo-Benutzer (Julian, Marie, Felix, Jerôme)
  - Beliebiges Passwort möglich
- **Dashboard**
  - Übersicht über Räume, Statistiken und Spieloptionen
  - Einzeln oder im Multiplayer-Modus spielen
- **Quiz-Modi**
  - 🎮 **Einzelspieler-Modus** mit wählbarer Schwierigkeit (Leicht, Mittel, Schwer)
  - 🏠 **Raum-System** zum Erstellen und Beitreten von Quiz-Räumen
  - 🤝 Kooperativ oder ⚔️ Kompetitiv
- **Fragenverwaltung**
  - Fragen hinzufügen, bearbeiten und löschen
  - Verschiedene Fragetypen: Multiple Choice, Wahr/Falsch, Texteingabe
  - Schwierigkeitsgrade und Zeitlimits
  - Erklärungen für Lernzwecke
- **Statistiken**
  - Richtige und falsche Antworten werden lokal gespeichert (per `localStorage`)

## 📂 Projektstruktur
- `index.html`  
  Enthält die gesamte Anwendung inkl. Styles und Skripten.
- **Inline CSS** für Layout, Buttons, Modals, etc.
- **Inline JavaScript**:
  - Simulierte Vue.js 3 API
  - State-Management über `localStorage`
  - Quizlogik (Fragen, Räume, Statistiken, UI-Updates)

## ▶️ Nutzung
1. Öffne `index.html` im Browser.
2. Melde dich mit einem der Demo-Benutzer an:
   - z. B. `julian.schork@iu-study.org` (Passwort beliebig)
3. Wähle im Dashboard:
   - **Einzelspieler** → Quiz starten mit Schwierigkeitsgrad
   - **Raum erstellen** → Multiplayer starten
   - **Fragenverwaltung** → Fragen hinzufügen/bearbeiten

## 🛠️ Erweiterungsmöglichkeiten
- Fragen und Benutzer können einfach im Code (globalState) erweitert werden.
- Integration eines echten Backends (z. B. Node.js/Express + DB) für persistente Räume und Highscores.
- Export/Import von Fragen im JSON-Format.

---

👨‍🎓 Entwickelt als Lern- und Demo-Projekt für **wirtschaftsinformatische Quizze**.
