<?php
// 1. INÍCIO DA SESSÃO E SEGURANÇA
session_start();
include '../../engine/config.php'; 

// Verificação de segurança: Nível de acesso
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?erro=acesso_negado');
    exit();
}

// Dados do usuário logado para exibir no topo
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// --- BUSCA OS DADOS EXTRAS DO USUÁRIO (FOTO) ---
$query_user = "SELECT profile_pic FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($query_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res_user = $stmt->get_result();
$usuario = $res_user->fetch_assoc();

// Caso não tenha foto, define uma padrão
$foto_perfil = !empty($usuario['profile_pic']) ? $usuario['profile_pic'] : 'assets/img/default-user.png';

// 2. LÓGICA DE BUSCA
$where = "WHERE 1=1"; 

if (!empty($_GET['nome'])) {
    $nome = $conn->real_escape_string($_GET['nome']);
    $where .= " AND nome_completo LIKE '%$nome%'";
}
if (!empty($_GET['usuario'])) {
    $usuario = $conn->real_escape_string($_GET['usuario']);
    $where .= " AND username LIKE '%$usuario%'";
}

$sql = "SELECT id, nome_completo, username, email, telefone FROM usuarios $where";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Novo Grupo | TwoWay Chat</title>
    <link rel="stylesheet" href="../../styles/search_users/styles.css">
    <link rel="stylesheet" href="../../styles/minimo.css">
</head>
<body>

    <div class="sidebar">

        <div class="profile clearfix">
                <div id="perfil-user">
                    <img src="../../<?php echo $usuario['profile_pic']; ?>">
                </div>

                <div class="profile_info">
                    <span>Seja bem-vindo,</span>
                    <h2><?php echo $_SESSION['username']; ?></h2>
                </div>
        </div>

        <a href="../chat/chat.php">🏠 Voltar ao Chat</a>
        <a href="../search_users/index.php">🔍 Gerenciar Usuários</a>
        <a href="#">Funções Futuras</a>
        <a href="#">Funções Futuras</a>
        <!-- <a href="logout.php" style="color: #e74c3c; margin-top: 50px;">🚪 Sair</a> -->
    </div>

    <div class="main-content">

        <div class="header">
            <div class="welcome-msg">
                Gestão de <strong>Grupos</strong>

                <a href="javascript:history.back()" title="Voltar" style="padding: 0; margin: 0; cursor: pointer;">
                  <i class="fa fa-chevron-left" style="font-size: 18px; color: #5A738E;"></i>
                </a>

            </div>
            <div>
                <span style="margin-right: 20px;">Olá, <strong><?php echo $_SESSION['username']; ?></strong></span>
                <a href="../../logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div class="group-card">
                <div class="form-group">
                    <label>Nome do Grupo</label>
                    <input type="text" id="nome" placeholder="Ex: Time de Vendas" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <textarea id="descricao" rows="3" placeholder="Do que se trata este grupo?" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;"></textarea>
                </div>
                    <button id="btnCriar">Criar Grupo Agora</button>
                <div id="msg"></div>
            </div>
        </div>

    </div>

<script>
document.getElementById('btnCriar').addEventListener('click', function() {
    const nome = document.getElementById('nome').value;
    const descricao = document.getElementById('descricao').value;
    const msgDiv = document.getElementById('msg');

    if(!nome) {
        msgDiv.innerText = "⚠️ Por favor, dê um nome ao grupo.";
        msgDiv.style.color = "orange";
        return;
    }

    const formData = new FormData();
    formData.append('nome', nome);
    formData.append('descricao', descricao);

    // Desativa o botão para evitar cliques duplos
    this.disabled = true;
    this.innerText = "Criando...";

    fetch('../../engine/api_grupos.php?action=create_group', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            msgDiv.innerText = "✅ " + data.message;
            msgDiv.style.color = "green";
            // Limpa o formulário após 1.5s ou redireciona
            setTimeout(() => location.href = '../chat/chat.php', 1500);
        } else {
            msgDiv.innerText = "❌ " + data.message;
            msgDiv.style.color = "red";
            this.disabled = false;
            this.innerText = "Criar Grupo Agora";
        }
    })
    .catch(err => {
        console.error(err);
        this.disabled = false;
    });
});
</script>

</body>
</html>