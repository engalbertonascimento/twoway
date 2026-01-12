<?php
session_start();
require_once '../../engine/config.php';

if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$admin_name = $_SESSION['username'];
$username = $_SESSION['username'];

// Busca foto
$foto_perfil = 'assets/img/default-user.png';
if ($conn) {
    $stmt = $conn->prepare("SELECT profile_pic FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        if(!empty($row['profile_pic'])) $foto_perfil = $row['profile_pic'];
    }
}

// LÓGICA DE CADASTRO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_register'])) {
    $nome_completo = $_POST['nome_completo'];
    $novo_username = $_POST['username'];
    $password_input = $_POST['password'];
    $tel_ramal = $_POST['tel']; // Nome corrigido para o que está no input
    $email = $_POST['email'];
    $permissao = $_POST['permissao'];


    // Verifica se o usuário já existe
    $check = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
    $check->bind_param("s", $novo_username);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "Este nome de usuário já está em uso.";
    } else {
        $password_hash = password_hash($password_input, PASSWORD_DEFAULT);

        // 2. Query de Inserção (Ajustado para 5 parâmetros 's')
        $stmt = $conn->prepare("INSERT INTO usuarios (nome_completo, username, password_hash, telefone, email, nivel_acesso) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nome_completo, $novo_username, $password_hash, $tel_ramal, $email, $permissao);

        if ($stmt->execute()) {
            $novo_id = $conn->insert_id;
            
            // Grava os grupos se existirem
            if (isset($_POST['grupos_selecionados']) && !empty($_POST['grupos_selecionados'])) {
                // Prepara uma vez fora do loop para melhor performance
                $stmt_g = $conn->prepare("INSERT INTO grupo_membros (usuario_id, grupo_id) VALUES (?, ?)");
                
                foreach ($_POST['grupos_selecionados'] as $id_grupo) {
                    $stmt_g->bind_param("ii", $novo_id, $id_grupo);
                    $stmt_g->execute();
                }
            }
            header("Location: index.php?success=1");
            exit;
        } else {
            $error = "Erro ao cadastrar: " . $conn->error;
        }
    }
}
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

    <form class="card" action="index.php" method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Nome Completo</label>
            <input type="text" name="nome_completo" placeholder="Nome do funcionário">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Usuário de Acesso</label>
                <input type="text" name="username" placeholder="Ex: joao.silva">
            </div>
            <div class="form-group">
                <label>Senha Provisória</label>
                <input type="password" name="password" placeholder="******">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>E-mail Corporativo</label>
                <input type="text" name="email" placeholder="email@empresa.com">
            </div>
            <div class="form-group">
                <label>Telefone/Ramal</label>
                <input type="number" name="tel" placeholder="(00) 00000-0000" style="text-decoration: none;">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Grupo</label>
                <select id="select_grupos">
                    <option value="">Selecione os grupos...</option>
                    <?php 
                    $res_grupos = $conn->query("SELECT id, nome FROM grupos ORDER BY nome ASC");
                    while($g = $res_grupos->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $g['id']; ?>"><?php echo $g['nome']; ?></option>
                    <?php endwhile; ?>
                </select>
                <div id="hidden_inputs_container"></div>
            </div>

            <div class="form-group">
                <label>Nível de Permissão</label>
                <select name="permissao" id="select_permissao">
                    <option value="usuario" selected>Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
        </div>

        <button type="submit" name= "btn_register" class="btn-cadastrar">Cadastrar Funcionário</button>

        <input type="text" name="groups" hidden id="">
        
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
            <h3>PERMISSÕES ATIVAS</h3>
            <table>
                <tbody id="tabela_visual_permissao">
                    <tr>
                        <td id="permissao_texto" style="color: #2ecc71;">✅ Usuário (Padrão)</td>
                    </tr>
                </tbody>
            </table>
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

// Localize onde você colocou os outros scripts e adicione este:
const selectPermissao = document.getElementById('select_permissao');
const permissaoTexto = document.getElementById('permissao_texto');

selectPermissao.addEventListener('change', function() {
    const nomeSelecionado = this.options[this.selectedIndex].text;
    
    // Altera o texto e a cor para dar feedback de mudança
    permissaoTexto.innerHTML = `✅ ${nomeSelecionado}`;
    
    // Efeito visual rápido para o admin notar a mudança
    permissaoTexto.style.opacity = '0';
    setTimeout(() => {
        permissaoTexto.style.opacity = '1';
        permissaoTexto.style.transition = '0.3s';
    }, 50);
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