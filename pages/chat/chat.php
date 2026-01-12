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
    <link rel="stylesheet" href="../../styles/search_users/styles.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1.21.2/index.js"></script>
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

        <div id="contact-list" style="flex-grow: 1; overflow-y: auto;">
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
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                <a href="../../logout.php" style="color: #ff4d4d; text-decoration: none; font-weight: bold; font-size: 14px;">
                    Sair do Chat
                </a>
                
                <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                    <a href="../../pages/admin/index.php" title="Painel de Controle" style="text-decoration:none; font-size: 18px; border-left: 1px solid #ddd; padding-left: 15px;">
                        ⚙️
                    </a>
                <?php endif; ?>
            </div>
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
            
            <div id="emoji-container" style="display:none; position:absolute; bottom:60px; right:20px; z-index:1000;">
                <emoji-picker></emoji-picker>
            </div>

            <div style="display: flex; align-items: center; width: 100%; gap: 10px;">
                        <input type="file" id="media-input" style="display: none;">
                        <button id="media-button" type="button">📎</button>
                        
                        <input type="text" id="message-input" placeholder="Digite sua mensagem...">
                        
                        <button id="emoji-button" type="button">😀</button>
                        <button id="send-button">Enviar</button>
                    </div>
        </div>
    </div>

        <audio id="notif-sound" preload="auto">
            <source src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" type="audio/mpeg">
        </audio>

<script>
    // Única ponte necessária: passa o ID do PHP para o escopo global do navegador
    window.REMETENTE_PHP_ID = <?php echo json_encode($user_id); ?>;
</script>

<script type="module" src="../../engine/chat/main.js"></script>

</body>
</html>