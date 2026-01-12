import { state, initRemetente } from './config.js';
import * as api from './chat_api.js';
import * as ui from './ui.js';
import { clearNotifications } from './notification.js';

// Funções globais para os onclicks do HTML dinâmico
window.startReplyAction = ui.startReply;
window.deleteMsgAction = (id) => {
    if(!confirm('Apagar mensagem?')) return;
    const form = new FormData();
    form.append('message_id', id);
    fetch('../../engine/api_chat.php?action=delete_message', { method: 'POST', body: form })
        .then(() => api.loadMessages());
};

document.addEventListener('DOMContentLoaded', () => {
    initRemetente(window.REMETENTE_PHP_ID);

    const messageInput = document.getElementById('message-input');
    const emojiPicker = document.querySelector('emoji-picker');
    const emojiContainer = document.getElementById('emoji-container');
    const emojiButton = document.getElementById('emoji-button');

    // --- LÓGICA DO EMOJI PICKER ---
    if (emojiPicker) {
        // Insere o emoji no input ao clicar
        emojiPicker.addEventListener('emoji-click', event => {
            messageInput.value += event.detail.unicode;
            messageInput.focus();
        });
    }

    // Abre/Fecha o menu de emojis
    if (emojiButton && emojiContainer) {
        emojiButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita fechar imediatamente
            emojiContainer.style.display = emojiContainer.style.display === 'none' ? 'block' : 'none';
        });

        // Fecha o seletor se clicar fora dele
        document.addEventListener('click', (e) => {
            if (!emojiContainer.contains(e.target) && e.target !== emojiButton) {
                emojiContainer.style.display = 'none';
            }
        });
    }

    // --- EVENTOS EXISTENTES ---

    // Busca
    document.getElementById('search-input').addEventListener('input', (e) => ui.handleSearch(e.target.value));

    // Envio
    document.getElementById('send-button').addEventListener('click', api.handleSend);
    messageInput.addEventListener('keypress', (e) => { 
        if(e.key === 'Enter') api.handleSend(); 
    });

    // Seleção de Contato
    document.getElementById('contact-list').addEventListener('click', (e) => {
        const item = e.target.closest('.contact-item');
        if (!item) return;

        state.destinatarioId = item.dataset.id;
        state.destinatarioUsername = item.dataset.username;
        state.chatTipo = item.dataset.tipo || 'privado';

        document.getElementById('chat-header').textContent = state.destinatarioUsername;
        document.getElementById('input-area').style.display = 'flex';
        
        state.lastMessageCount = 0;
        ui.cancelReply();
        api.loadMessages();
        
        if (state.pollingInterval) clearInterval(state.pollingInterval);
        state.pollingInterval = setInterval(api.loadMessages, 3000);
    });

    // Anexos
    document.getElementById('media-button').addEventListener('click', () => document.getElementById('media-input').click());
    document.getElementById('media-input').addEventListener('change', (e) => {
        state.selectedFile = e.target.files[0];
        if(state.selectedFile) {
            messageInput.placeholder = "Arquivo: " + state.selectedFile.name;
            messageInput.style.backgroundColor = "#e1f5fe"; // Destaque visual
        }
    });

    // Notificações
    document.addEventListener('visibilitychange', () => { if (!document.hidden) clearNotifications(); });
});