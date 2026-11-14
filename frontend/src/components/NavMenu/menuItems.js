// Voci delle tendine della navbar (utente collegato).
// `test(pathname, search)` dice quando la voce corrisponde alla pagina aperta.

export const GAME_ITEMS = [
  { to: "/quiz-one-shot", icon: "bi-grid-1x2-fill", title: "Quiz One Shot", text: "Un quiz, un solo tentativo", test: (p) => p.startsWith("/quiz-one-shot") || p.startsWith("/quiz/") },
  { to: "/midalario", icon: "bi-broadcast", title: "Il Midalario", text: "Il quiz live con tutti gli altri", test: (p) => p.startsWith("/midalario") },
  { to: "/training", icon: "bi-lightning-charge-fill", title: "Training", text: "Allenati per categoria", test: (p) => p.startsWith("/training") },
  { to: "/minigiochi", icon: "bi-joystick", title: "Minigiochi", text: "Sfide veloci, punteggio in palio", test: (p) => p.startsWith("/minigiochi") },
]

export const AVATAR_ITEMS = [
  { to: "/avatar", icon: "bi-person-badge", title: "Il mio avatar", text: "Personalizzalo e indossa gli oggetti", test: (p, s) => p === "/avatar" && !s.includes("vista=negozio") },
  { to: "/avatar?vista=negozio", icon: "bi-shop", title: "Negozio", text: "Spendi le tue monete", test: (p, s) => p === "/avatar" && s.includes("vista=negozio") },
  { to: "/invita", icon: "bi-people-fill", title: "Invita un amico", text: "Ricevi monete per ogni amico", test: (p) => p.startsWith("/invita") },
]

export const PROFILE_ITEMS = [
  { to: "/profilo", icon: "bi-person-lines-fill", title: "Il mio profilo", text: "I tuoi dati e le impostazioni", test: (p) => p.startsWith("/profilo") },
  { to: "/storico", icon: "bi-clock-history", title: "Storico", text: "Le tue partite", test: (p) => p.startsWith("/storico") },
  { to: "/chi-siamo", icon: "bi-info-circle-fill", title: "Chi siamo", text: "Chi c'e' dietro Midalot", test: (p) => p.startsWith("/chi-siamo") },
  { to: "/regolamento", icon: "bi-journal-check", title: "Regolamento", text: "Regole e premi", test: (p) => p.startsWith("/regolamento") },
]
