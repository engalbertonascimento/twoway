// engine/chat/ui.js
import { state } from './config.js';

export function startReply(id, user, text) {
    state.replyingToId = id; // SALVA O ID NO ESTADO GLOBAL

    const replyUser = document.getElementById('reply-user');
    const replyText = document.getElementById('reply-text');
    const replyBar = document.getElementById('reply-bar');

    if (replyUser) replyUser.textContent = user;
    if (replyText) replyText.textContent = text;
    if (replyBar) replyBar.style.display = 'flex';
    
    document.getElementById('message-input').focus();
}

export function cancelReply() {
    state.replyingToId = null; // LIMPA O ID AO CANCELAR OU ENVIAR
    const replyBar = document.getElementById('reply-bar');
    if (replyBar) replyBar.style.display = 'none';
}

export function handleSearch(termo) {
    const termoLower = termo.toLowerCase().trim();
    const recentDiv = document.getElementById('recent-contacts');
    const searchResultsDiv = document.getElementById('search-results');
    const recentUsers = document.querySelectorAll('#recent-contacts .contact-item');

    if (termoLower === '') {
        if (recentDiv) recentDiv.style.display = 'block';
        if (searchResultsDiv) searchResultsDiv.style.display = 'none';
        recentUsers.forEach(el => el.style.display = 'flex');
    } else {
        if (recentDiv) recentDiv.style.display = 'none';
        if (searchResultsDiv) searchResultsDiv.style.display = 'block';
        document.querySelectorAll('.all-users').forEach(user => {
            const nome = user.dataset.username.toLowerCase();
            user.style.display = nome.includes(termoLower) ? 'flex' : 'none';
        });
    }
}