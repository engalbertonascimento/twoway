<?php
// chat.php
session_start();
include '../../engine/config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuário Desconhecido'; 

$usuarios = [];
if (!$conn->connect_error) {
    // QUERY PARA CONVERSAS RECENTES:
    $sql = "SELECT DISTINCT u.id, u.username, 
            (SELECT MAX(timestamp) FROM mensagens m2 
             WHERE (m2.remetente_id = u.id AND m2.destinatario_id = ?) 
                OR (m2.remetente_id = ? AND m2.destinatario_id = u.id)) as ultima_msg
            FROM usuarios u
            INNER JOIN mensagens m ON (m.remetente_id = u.id OR m.destinatario_id = u.id)
            WHERE u.id != ? AND (m.remetente_id = ? OR m.destinatario_id = ?)
            ORDER BY ultima_msg DESC";

    $stmt = $conn->prepare($sql);
    // Vinculamos o ID do usuário logado nos 5 parâmetros da busca
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    $stmt->close();
}
$conn->close(); 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Interno - <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" href="../../styles/chat/style.css"> 
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1"></script>
</head>
<body>
    <div id="sidebar">

        <div id="perfil-user">
            <img src="https://png.pngtree.com/png-vector/20240715/ourmid/pngtree-male-profile-icon-in-black-on-a-white-background-vector-png-image_7058986.png" alt="Foto de Perfil">
            <h3>Seja bem-vindo <?php echo htmlspecialchars($username); ?></h3>
        </div>

        <div id="contacts-search">
            <h4>Conversas</h4>
            <input type="text" id="search-input" placeholder="Buscar nas conversas...">
        </div>

        <div id="contact-list">
            <?php if (empty($usuarios)): ?>
                <p style="padding: 20px; color: #888; font-size: 0.9em;">Nenhuma conversa recente.</p>
            <?php endif; ?>

            <?php foreach ($usuarios as $u) : ?>
                <div class="contact-item" 
                     data-id="<?php echo $u['id']; ?>" 
                     data-username="<?php echo htmlspecialchars($u['username']); ?>">
                    <?php echo htmlspecialchars($u['username']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p><a href="../../logout.php" style="color: #ff4d4d; text-decoration: none; padding: 15px; display: block;">Sair do Chat</a></p>
    </div>

    <div id="chat-main">
        <h3 id="chat-header">Selecione um contato para conversar</h3>
        
        <div id="messages"></div>

        <div id="input-area" style="display: none; flex-direction: column;">
            <div id="reply-bar" style="display: none;">
                <div id="reply-bar-content">
                    <small>Respondendo para <strong id="reply-user"></strong></small>
                    <div id="reply-text" style="font-size: 0.8em; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
                </div>
                <button id="reply-bar-close" type="button">✖</button>
            </div>
            
            <div id="emoji-picker-container" style="display: none; position: absolute; bottom: 80px; right: 20px; z-index: 100;">
                <emoji-picker class="light"></emoji-picker>
            </div>

            <div style="display: flex; align-items: center; width: 100%; gap: 10px;">
                <input type="file" id="media-input" accept="*" style="display: none;">
                <button id="media-button" type="button" title="Anexar Arquivo">📎</button>
                
                <input type="text" id="message-input" placeholder="Digite sua mensagem...">
                <button id="emoji-button" type="button">😀</button>
                <button id="send-button">Enviar</button>
            </div>
        </div>
    </div>

<script>
    const REMETENTE_ID = <?php echo json_encode($user_id); ?>;
    let destinatarioId = null; 
    let destinatarioUsername = null;
    let pollingInterval = null;
    let lastMessageId = null; // Controle para notificações
    let selectedFile = null; 
    let replyingToId = null; 
    let ultimaMensagemId = null;

    const messagesDiv = document.getElementById('messages');
    const messageInput = document.getElementById('message-input');
    const mediaInput = document.getElementById('media-input');
    const replyBar = document.getElementById('reply-bar');
    const contactList = document.getElementById('contact-list');

    // --- 1. CONFIGURAÇÃO DE NOTIFICAÇÕES ---
    document.addEventListener('DOMContentLoaded', () => {
        if ("Notification" in window && Notification.permission !== "granted") {
            Notification.requestPermission();
        }
    });

    function dispararNotificacao(usuario, texto) {
        if ("Notification" in window && Notification.permission === "granted") {
            const n = new Notification(`Nova mensagem de ${usuario}`, {
                body: texto || "Enviou um arquivo...",
                icon: 'https://cdn-icons-png.flaticon.com/512/733/733585.png'
            });
            n.onclick = () => { window.focus(); n.close(); };
        }
    }

    // --- 2. BUSCA GLOBAL (ENCONTRAR NOVOS USUÁRIOS) ---
    document.getElementById('search-input').addEventListener('input', async (e) => {
        const termo = e.target.value.trim().toLowerCase();
        const contatosLocais = document.querySelectorAll('.contact-item:not(.search-suggestion)');
        
        // Filtro local
        contatosLocais.forEach(item => {
            const nome = item.dataset.username.toLowerCase();
            item.style.display = nome.includes(termo) ? 'block' : 'none';
        });

        // Busca no Banco de Dados
        if (termo.length >= 2) {
            try {
                const response = await fetch(`../../engine/api_chat.php?action=search_global&q=${termo}`);
                const novosUsuarios = await response.json();
                
                document.querySelectorAll('.search-suggestion').forEach(el => el.remove());

                novosUsuarios.forEach(u => {
                    const jaExiste = Array.from(contatosLocais).some(el => el.dataset.id == u.id);
                    if (!jaExiste) {
                        const div = document.createElement('div');
                        div.className = 'contact-item search-suggestion';
                        div.dataset.id = u.id;
                        div.dataset.username = u.username;
                        div.innerHTML = `<span style="opacity: 0.6;">🔍</span> ${u.username}`;
                        div.style.borderLeft = "4px solid #54a7e5"; 
                        contactList.appendChild(div);
                    }
                });
            } catch (err) { console.error("Erro na busca global:", err); }
        } else {
            document.querySelectorAll('.search-suggestion').forEach(el => el.remove());
        }
    });

    // --- 3. SELEÇÃO DE CONTATO E POLLING ---
    contactList.addEventListener('click', (e) => {
        const item = e.target.closest('.contact-item');
        if (!item) return;

        destinatarioId = item.dataset.id;
        destinatarioUsername = item.dataset.username;
        lastMessageId = null; // Reseta para não notificar o histórico ao abrir
        
        document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('selected-chat'));
        item.classList.add('selected-chat');

        document.getElementById('chat-header').textContent = destinatarioUsername;
        document.getElementById('input-area').style.display = 'flex';
        
        cancelReply();
        loadMessages();
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(loadMessages, 3000);
    });

    // --- 4. CARREGAR MENSAGENS E LÓGICA DE NOTIFICAÇÃO ---
    function loadMessages() {
        if (!destinatarioId) return;
        fetch(`../../engine/api_chat.php?action=get_messages&destinatario_id=${destinatarioId}`)
            .then(res => res.json())
            .then(data => {
                // Checar se há mensagem nova para notificar
                if (data.length > 0) {
                    const ultimaMsg = data[data.length - 1];
                    if (lastMessageId !== null && ultimaMsg.id > lastMessageId) {
                        if (parseInt(ultimaMsg.remetente_id) !== parseInt(REMETENTE_ID)) {
                            dispararNotificacao(destinatarioUsername, ultimaMsg.mensagem);
                        }
                    }
                    lastMessageId = ultimaMsg.id;
                }

                messagesDiv.innerHTML = '';
                data.forEach(msg => {
                    const isSent = parseInt(msg.remetente_id) === parseInt(REMETENTE_ID);
                    const wrapper = document.createElement('div');
                    wrapper.className = `message-wrapper ${isSent ? 'sent' : 'received'}`;
                    
                    let fileHtml = '';
                    if (msg.arquivo_path) {
                        const ext = msg.arquivo_path.split('.').pop().toLowerCase();
                        if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                            fileHtml = `<img src="${msg.arquivo_path}" class="chat-img" onclick="window.open('${msg.arquivo_path}')">`;
                        } else {
                            fileHtml = `<div class="file-box"><a href="${msg.arquivo_path}" target="_blank">📄 Arquivo (.${ext})</a></div>`;
                        }
                    }

                    let quoteHtml = msg.reply_to_id ? `<div class="quoted-msg"><strong>${msg.replied_remetente_id == REMETENTE_ID ? 'Você' : 'Ele'}:</strong> ${msg.replied_mensagem || 'Arquivo'}</div>` : '';

                    wrapper.innerHTML = `
                        <div class="message-box">
                            ${quoteHtml}
                            ${fileHtml}
                            <p>${msg.mensagem || ''}</p>
                            <div class="msg-footer">
                                <span class="reply-btn" onclick="startReply('${msg.id}', '${isSent ? 'Você' : destinatarioUsername}', '${msg.mensagem || 'Arquivo'}')">↩️</span>
                                ${new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                ${isSent ? `<span onclick="deleteMessage(${msg.id})">🗑️</span>` : ''}
                            </div>
                        </div>
                    `;
                    messagesDiv.appendChild(wrapper);
                });
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });
    }

    // --- 5. ENVIAR MENSAGENS E ARQUIVOS ---
    function handleSend() {
        const text = messageInput.value.trim();
        if (!text && !selectedFile) return;

        const formData = new FormData();
        formData.append('destinatario_id', destinatarioId);
        formData.append('mensagem', text);
        formData.append('reply_to_id', replyingToId || '');

        let url = '../../engine/api_chat.php?action=send_message';
        if (selectedFile) {
            formData.append('media', selectedFile);
            url = '../../engine/api_chat.php?action=send_media';
        }

        fetch(url, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    messageInput.value = '';
                    messageInput.placeholder = "Digite sua mensagem...";
                    messageInput.style.backgroundColor = "";
                    selectedFile = null;
                    mediaInput.value = '';
                    cancelReply();
                    loadMessages();
                    
                    // Se era uma sugestão, remove o ícone de busca
                    document.querySelectorAll('.search-suggestion').forEach(el => {
                        if(el.dataset.id == destinatarioId) el.classList.remove('search-suggestion');
                    });
                }
            });
    }

    // --- 6. EVENTOS DE INTERFACE (EMOJI, RESPOSTA, DELETE) ---
    document.getElementById('send-button').addEventListener('click', handleSend);
    messageInput.addEventListener('keypress', e => { if(e.key === 'Enter') handleSend(); });

    document.getElementById('emoji-button').addEventListener('click', () => {
        const container = document.getElementById('emoji-picker-container');
        container.style.display = container.style.display === 'none' ? 'block' : 'none';
    });

    document.querySelector('emoji-picker').addEventListener('emoji-click', event => {
        messageInput.value += event.detail.unicode;
        messageInput.focus();
    });

    document.getElementById('media-button').addEventListener('click', () => mediaInput.click());
    mediaInput.addEventListener('change', (e) => {
        selectedFile = e.target.files[0];
        if(selectedFile) {
            messageInput.placeholder = "Arquivo: " + selectedFile.name;
            messageInput.style.backgroundColor = "#e1f5fe";
        }
    });

    function startReply(id, user, text) {
        replyingToId = id;
        document.getElementById('reply-user').textContent = user;
        document.getElementById('reply-text').textContent = text;
        replyBar.style.display = 'flex';
        messageInput.focus();
    }

    function cancelReply() {
        replyingToId = null;
        replyBar.style.display = 'none';
    }
    document.getElementById('reply-bar-close').addEventListener('click', cancelReply);

    function deleteMessage(id) {
        if(!confirm('Apagar mensagem?')) return;
        const form = new FormData();
        form.append('message_id', id);
        fetch('../../engine/api_chat.php?action=delete_message', { method: 'POST', body: form }).then(() => loadMessages());
    }


    function dispararNotificacao(usuario, texto) {
    // Verifica se o navegador suporta e se o usuário deu permissão
    if ("Notification" in window && Notification.permission === "granted") {
        const options = {
            body: texto || "Enviou um arquivo...",
            icon: 'https://cdn-icons-png.flaticon.com/512/733/733585.png', // Ícone opcional
            silent: false 
        };

        const n = new Notification(`Nova mensagem de ${usuario}`, options);

        // Se clicar na notificação, foca na janela do chat
        n.onclick = () => {
            window.focus();
            n.close();
        };
    }
}
</script>
</body>
</html>