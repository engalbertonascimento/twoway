// engine/chat/chat_api.js
import { state } from './config.js';
import { notifyUser } from './notification.js';
import { cancelReply } from './ui.js';

export function loadMessages() {
    if (!state.destinatarioId) return;

    fetch(`../../engine/api_chat.php?action=get_messages&destinatario_id=${state.destinatarioId}&tipo=${state.chatTipo}`)
        .then(res => res.json())
        .then(data => {
            const messagesDiv = document.getElementById('messages');
            if (state.lastMessageCount > 0 && data.length > state.lastMessageCount) {
                const ultimaMsg = data[data.length - 1];
                if (parseInt(ultimaMsg.remetente_id) !== parseInt(state.remetenteId)) {
                    notifyUser(data.length - state.lastMessageCount);
                }
            }
            if (data.length !== state.lastMessageCount) {
                renderMessages(data, messagesDiv);
            }
            state.lastMessageCount = data.length;
        });
}

export function handleSend() {
    const messageInput = document.getElementById('message-input');
    const text = messageInput.value.trim();
    if (!text && !state.selectedFile) return;

    const formData = new FormData();
    formData.append('destinatario_id', state.destinatarioId);
    formData.append('tipo', state.chatTipo);
    formData.append('mensagem', text);
    
    // MELHORIA AQUI: Só adiciona ao FormData se houver um ID de resposta
    if (state.replyingToId) {
        formData.append('reply_to_id', state.replyingToId);
    }

    let url = '../../engine/api_chat.php?action=send_message';
    if (state.selectedFile) {
        formData.append('media', state.selectedFile);
        url = '../../engine/api_chat.php?action=send_media';
    }

    fetch(url, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                messageInput.placeholder = "Digite sua mensagem...";
                messageInput.style.backgroundColor = "";
                state.selectedFile = null;
                document.getElementById('media-input').value = '';
                
                // IMPORTANTE: cancelReply() limpa o state.replyingToId
                cancelReply(); 
                loadMessages();
            }
        });
}

function renderMessages(data, container) {
    const isAtBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 50;
    container.innerHTML = '';

    data.forEach(msg => {
        const isSent = parseInt(msg.remetente_id) === parseInt(state.remetenteId);
        const div = document.createElement('div');
        div.className = `message-wrapper ${isSent ? 'sent' : 'received'}`;
        
        // Lógica de Mídia
        let fileHtml = '';
        if (msg.arquivo_path) {
            const ext = msg.arquivo_path.split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                fileHtml = `<img src="${msg.arquivo_path}" class="chat-img" style="max-width:200px; cursor:pointer;" onclick="window.open('${msg.arquivo_path}')">`;
            } else {
                fileHtml = `<div class="file-box"><a href="${msg.arquivo_path}" target="_blank">📄 Arquivo .${ext}</a></div>`;
            }
        }

        const quoteHtml = msg.reply_to_id ? `<div class="quoted-msg" style="background:rgba(0,0,0,0.05); padding:5px; border-left:3px solid #ccc; margin-bottom:5px;"><small><strong>${msg.replied_username}:</strong> ${msg.replied_mensagem}</small></div>` : '';

        div.innerHTML = `
            <div class="message-box">
                ${quoteHtml}
                ${fileHtml}
                <p>${msg.mensagem || ''}</p>
                <div class="msg-footer" style="font-size: 0.7em; display:flex; justify-content: space-between; align-items: center;">
                    <span style="cursor:pointer" onclick="window.startReplyAction('${msg.id}', '${isSent ? 'Você' : msg.remetente_nome}', '${msg.mensagem || 'Mídia'}')">↩️</span>
                    <span>
                        ${new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        ${isSent ? `<span style="cursor:pointer; margin-left:5px;" onclick="window.deleteMsgAction(${msg.id})">🗑️</span>` : ''}
                    </span>
                </div>
            </div>`;
        container.appendChild(div);
    });

    if (isAtBottom || (data.length > 0 && parseInt(data[data.length-1].remetente_id) === parseInt(state.remetenteId))) {
        container.scrollTop = container.scrollHeight;
    }
}