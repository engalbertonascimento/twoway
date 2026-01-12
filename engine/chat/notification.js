// engine/chat/notification.js
import { state } from './config.js';

export function notifyUser(count) {
    const sound = document.getElementById('notif-sound');
    if (sound) {
        sound.currentTime = 0;
        sound.play().catch(e => console.log("Áudio aguardando interação."));
    }

    if (document.hidden && !state.alertInterval) {
        state.alertInterval = setInterval(() => {
            document.title = document.title === state.originalTitle 
                ? `(${count}) Nova Mensagem...` 
                : state.originalTitle;
        }, 1000);
    }
}

export function clearNotifications() {
    clearInterval(state.alertInterval);
    state.alertInterval = null;
    document.title = state.originalTitle;
}