<?php
session_start();
include '../../engine/config.php';

// Proteção básica: apenas admin acessa esta página de gestão
if ($_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../dashboard.php?erro=acesso_negado');
    exit();
}

// Dados do usuário logado para exibir no topo
$username = $_SESSION['username'];

// Recebe o ID via URL
$usuario_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$usuario_id) {
    die("ID de usuário não fornecido.");
}

// 1. Busca dados do Usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    die("Usuário não encontrado.");
}

// 2. Busca Grupos onde o usuário é membro
$sql_grupos = "SELECT g.*, gm.is_admin, gm.joined_at 
               FROM grupos g 
               JOIN grupo_membros gm ON g.id = gm.grupo_id 
               WHERE gm.usuario_id = ?";
$stmt_g = $conn->prepare($sql_grupos);
$stmt_g->bind_param("i", $usuario_id);
$stmt_g->execute();
$meus_grupos = $stmt_g->get_result();

// 3. Estatística extra: Grupos que ele mesmo CRIOU
$stmt_c = $conn->prepare("SELECT COUNT(*) as total FROM grupos WHERE criador_id = ?");
$stmt_c->bind_param("i", $usuario_id);
$stmt_c->execute();
$criados_count = $stmt_c->get_result()->fetch_assoc()['total'];
?>






<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Admin</title>
    <link rel="stylesheet" href="../../styles/cadastro/caduser.css">
</head>
<body>

<div class="sidebar">
    <div class="profile">
        <img src="../../<?php echo $foto_perfil; ?>">
        <div>
            <span>Bem-vindo,</span>
            <h2><?php echo htmlspecialchars($username); ?></h2>
        </div>
    </div>
    <a href="../admin/index.php">📊 Dashboard</a>
    <a href="../chat/chat.php">🏠 Voltar ao Chat</a>
    <a href="../search_users/index.php">🔍 Usuários</a>
    <a href="../../logout.php" style="color:#ffa2a2">🚪 Sair</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Cadastro de Funcionários</h1>
        <a href="../../logout.php" class="btn-logout">Sair do Sistema</a>
    </div>

    <form action="index.php" method="POST">
     
        <label for="nome_completo">Nome Completo</label>
        <input styles= "width: 900px" type="text" id="nome_completo" name="nome_completo" value="<?php echo htmlspecialchars($usuario['nome_completo']); ?>">
    
    <label for="username">Usuário de Acesso</label> 
    <input type="text" id="username" name="username" placeholder="Ex: joao.silva" value="<?php echo htmlspecialchars($usuario['username']); ?>">

    <label for="password">Senha Provisória</label>
    <input type="password" id="password" name="password" placeholder="******">

    <label for="email">E-mail Corporativo</label>
    <input type="email" id="email" name="email" placeholder="email@empresa.com" value="<?php echo htmlspecialchars($usuario['email']); ?>">

    <label for="tel">Telefone/Ramal</label>
    <input type="tel" id="tel" name="tel" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($usuario['telefone']); ?>">

    <button type="submit" name="btn_register" class="btn-cadastrar">Alterar</button>
</form>

    <div class="info-grid">

        <div class="info-box">
            <h3>MEMBRO DOS GRUPOS</h3>
            <table style="width:100%">
                <tbody id="tabela_visual_grupos"> <tr id="msg_vazia"><td>Aguardando seleção...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="info-box">
            <th>PERMISSÕES ATIVAS</th>
            <table><tr><td>Aguardando seleção...</td></tr></table>
        </div>

    </div>


</div>

<script>
const selecionados = new Set();

document.getElementById('select_grupos').addEventListener('change', function() {
    const grupoId = this.value;
    const grupoNome = this.options[this.selectedIndex].text;
    
    if (grupoId && !selecionados.has(grupoId)) {
        selecionados.add(grupoId);

        const msgVazia = document.getElementById('msg_vazia');
        if (msgVazia) msgVazia.remove();

        // Alvo corrigido para coincidir com o ID no HTML
        const tbody = document.getElementById('tabela_visual_grupos');
        const tr = document.createElement('tr');
        tr.id = `row_grupo_${grupoId}`;
        tr.innerHTML = `
            <td style="padding: 8px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                <span>✅ ${grupoNome}</span>
                <button type="button" onclick="removerGrupo('${grupoId}')" style="color: red; border:none; background:none; cursor:pointer;">✖</button>
            </td>
        `;
        tbody.appendChild(tr);

        const container = document.getElementById('hidden_inputs_container');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'grupos_selecionados[]';
        input.value = grupoId;
        input.id = `input_grupo_${grupoId}`;
        container.appendChild(input);
    }
    this.value = "";
});

function removerGrupo(id) {
    selecionados.delete(id);
    document.getElementById(`row_grupo_${id}`).remove();
    document.getElementById(`input_grupo_${id}`).remove();
    
    if (selecionados.size === 0) {
        document.getElementById('tabela_visual_grupos').innerHTML = '<tr id="msg_vazia"><td>Aguardando seleção...</td></tr>';
    }
}
</script>

</body>
</html>