// engine/chat/config.js
export const state = {
    remetenteId: null,
    destinatarioId: null,
    destinatarioUsername: null,
    chatTipo: 'privado',
    pollingInterval: null,
    lastMessageCount: 0,
    selectedFile: null,
    replyingToId: null,
    originalTitle: document.title,
    alertInterval: null
};

export function initRemetente(id) {
    state.remetenteId = id;
}