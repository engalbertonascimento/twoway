<?php
session_start();
include '../../engine/config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuário Desconhecido'; 

$contatos = [];

if (!$conn->connect_error) {
    // 1. BUSCAR GRUPOS QUE O USUÁRIO PERTENCE
    $sql_grupos = "SELECT g.id, g.nome as nome_exibicao, 'grupo' as tipo 
                   FROM grupos g 
                   INNER JOIN grupo_membros gm ON g.id = gm.grupo_id 
                   WHERE gm.usuario_id = ?";
    $stmt_g = $conn->prepare($sql_grupos);
    $stmt_g->bind_param("i", $user_id);
    $stmt_g->execute();
    $res_g = $stmt_g->get_result();
    while ($row = $res_g->fetch_assoc()) { $contatos[] = $row; }
    $stmt_g->close();

    // Substitua o bloco // 2. BUSCAR APENAS CONVERSAS RECENTES por este:
    $sql_recentes = "SELECT DISTINCT u.id, u.username as nome_exibicao, 'privado' as tipo 
                    FROM usuarios u
                    WHERE u.id IN (
                        SELECT destinatario_id FROM mensagens WHERE remetente_id = ?
                        UNION
                        SELECT remetente_id FROM mensagens WHERE destinatario_id = ?
                    ) AND u.id != ?";
    $stmt_r = $conn->prepare($sql_recentes);
    $stmt_r->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt_r->execute();
    $res_r = $stmt_r->get_result();
    while ($row = $res_r->fetch_assoc()) { $contatos[] = $row; }
    $stmt_r->close();
    
    // 3. BUSCAR TODOS OS OUTROS USUÁRIOS (Para a busca dinâmica via JS)
    // Vamos carregar todos mas manter ocultos, para que o JS os encontre ao digitar
    $todos_usuarios = [];
    $res_all = $conn->query("SELECT id, username as nome_exibicao, 'privado' as tipo FROM usuarios WHERE id != $user_id");
    while ($row = $res_all->fetch_assoc()) { $todos_usuarios[] = $row; }
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
    <link rel="stylesheet" href="../../styles/search_users/style.css"> 
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
            <div id="recent-contacts">
                <?php foreach ($contatos as $u) : ?>
                    <div class="contact-item" 
                        data-id="<?php echo $u['id']; ?>" 
                        data-username="<?php echo htmlspecialchars($u['nome_exibicao']); ?>"
                        data-tipo="<?php echo ($u['tipo'] == 'grupo') ? 'grupo' : 'privado'; ?>"> <span><?php echo ($u['tipo'] == 'grupo') ? '👥' : '👤'; ?></span>
                        <?php echo htmlspecialchars($u['nome_exibicao']); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="search-results" style="display: none;">
                <p style="font-size: 0.7em; color: #888; padding-left: 15px;">RESULTADOS DA BUSCA</p>
                <?php foreach ($todos_usuarios as $u) : ?>
                    <div class="contact-item all-users" data-id="<?php echo $u['id']; ?>" data-username="<?php echo htmlspecialchars($u['nome_exibicao']); ?>">
                        <span>👤</span>
                        <?php echo htmlspecialchars($u['nome_exibicao']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="out-config">
            <a href="../../logout.php" style="color: #ff4d4d; text-decoration: none; padding: 15px; display: block;">Sair do Chat</a>

            <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                <a href="../../pages/admin/index.php" title="Painel de Controle" style="text-decoration:none; font-size: 14px;">⚙️</a>
            <?php endif; ?>
        </div>

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
    let chatTipo = 'privado'; // NOVO: Armazena se é 'privado' ou 'grupo'
    let pollingInterval = null;
    let lastMessageCount = 0; 
    let selectedFile = null; 
    let replyingToId = null; 

    // --- LÓGICA DE BUSCA DINÂMICA --- (Mantida)
    const searchInput = document.getElementById('search-input');
    const recentDiv = document.getElementById('recent-contacts');
    const searchResultsDiv = document.getElementById('search-results');
    const allUsers = document.querySelectorAll('.all-users');
    const recentUsers = document.querySelectorAll('#recent-contacts .contact-item');

    searchInput.addEventListener('input', () => {
        const termo = searchInput.value.toLowerCase().trim();
        if (termo === '') {
            recentDiv.style.display = 'block';
            searchResultsDiv.style.display = 'none';
            recentUsers.forEach(el => el.style.display = 'flex');
        } else {
            recentDiv.style.display = 'none';
            searchResultsDiv.style.display = 'block';
            allUsers.forEach(user => {
                const nome = user.dataset.username.toLowerCase();
                user.style.display = nome.includes(termo) ? 'flex' : 'none';
            });
        }
    });

    const messagesDiv = document.getElementById('messages');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const mediaInput = document.getElementById('media-input');
    const replyBar = document.getElementById('reply-bar');

    // --- SELECIONAR CONTATO OU GRUPO ---
    document.getElementById('contact-list').addEventListener('click', (e) => {
        const item = e.target.closest('.contact-item');
        if (!item) return;

        destinatarioId = item.dataset.id;
        destinatarioUsername = item.dataset.username;
        // NOVO: Verifica no dataset do HTML se é grupo ou usuário
        chatTipo = item.dataset.tipo || 'privado'; 
        
        document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('selected-chat'));
        item.classList.add('selected-chat');

        document.getElementById('chat-header').textContent = destinatarioUsername;
        document.getElementById('input-area').style.display = 'flex';
        
        cancelReply();
        loadMessages();
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(loadMessages, 3000);
    });

    // --- EMOJIS (Mantido) ---
    document.getElementById('emoji-button').addEventListener('click', () => {
        const container = document.getElementById('emoji-picker-container');
        container.style.display = container.style.display === 'none' ? 'block' : 'none';
    });
    document.querySelector('emoji-picker').addEventListener('emoji-click', event => {
        messageInput.value += event.detail.unicode;
        messageInput.focus();
    });

    // --- ANEXOS (Mantido) ---
    document.getElementById('media-button').addEventListener('click', () => mediaInput.click());
    mediaInput.addEventListener('change', (e) => {
        selectedFile = e.target.files[0];
        if(selectedFile) {
            messageInput.placeholder = "Arquivo: " + selectedFile.name;
            messageInput.style.backgroundColor = "#e1f5fe";
        }
    });

    // --- RESPOSTAS (Mantido) ---
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

    // --- CARREGAR MENSAGENS (Atualizado para Grupos) ---
    function loadMessages() {
        if (!destinatarioId) return;
        // ADICIONADO: &tipo=${chatTipo} na URL
        fetch(`../../engine/api_chat.php?action=get_messages&destinatario_id=${destinatarioId}&tipo=${chatTipo}`)
            .then(res => res.json())
            .then(data => {
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
                            fileHtml = `<div class="file-box"><a href="${msg.arquivo_path}" target="_blank">📄 Baixar Arquivo (.${ext})</a></div>`;
                        }
                    }

                    let quoteHtml = msg.reply_to_id ? `<div class="quoted-msg"><strong>${msg.replied_username || 'Usuário'}:</strong> ${msg.replied_mensagem || 'Arquivo'}</div>` : '';

                    // NOVO: Exibe o nome do remetente se for grupo e a mensagem for recebida
                    let remetenteNomeHtml = (chatTipo === 'grupo' && !isSent) ? `<small class="remetente-nome" style="color: #075e54; font-weight: bold; display: block; margin-bottom: 2px;">${msg.remetente_nome}</small>` : '';

                    wrapper.innerHTML = `
                        <div class="message-box">
                            ${remetenteNomeHtml}
                            ${quoteHtml}
                            ${fileHtml}
                            <p>${msg.mensagem || ''}</p>
                            <div class="msg-footer">
                                <span class="reply-btn" onclick="startReply('${msg.id}', '${isSent ? 'Você' : (msg.remetente_nome || destinatarioUsername)}', '${msg.mensagem || 'Arquivo'}')">↩️</span>
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

    // --- ENVIAR (Atualizado para Grupos) ---
    function handleSend() {
        const text = messageInput.value.trim();
        if (!text && !selectedFile) return;

        const formData = new FormData();
        formData.append('destinatario_id', destinatarioId);
        formData.append('tipo', chatTipo); // NOVO: Envia se é privado ou grupo
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
                }
            });
    }

    document.getElementById('send-button').addEventListener('click', handleSend);
    messageInput.addEventListener('keypress', e => { if(e.key === 'Enter') handleSend(); });

    function deleteMessage(id) {
        if(!confirm('Apagar mensagem?')) return;
        const form = new FormData();
        form.append('message_id', id);
        fetch('../../engine/api_chat.php?action=delete_message', { method: 'POST', body: form }).then(() => loadMessages());
    }
</script>
</body>
</html>