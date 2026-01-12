<?php
session_start();
require_once '../../engine/config.php';

if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_register'])) {
    $nome_completo = $_POST['nome_completo'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $tel_ramal = $_POST['tel_ramal'];
    $email = $_POST['email'];
    $permissao = $_POST['permissao'];

    // Validações básicas
    if ($pass !== $conf) {
        $error = "As senhas não coincidem!";
    } else {
        // Verifica se o usuário já existe
        $check = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $check->bind_param("s", $user);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Este nome de usuário já está em uso.";
        } else {
            // Hash da senha e inserção
            $password_hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome_completo, username, password_hash, telefone, email, nivel_acesso) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $nome_completo, $username, $password, $tel_ramal, $email, $permissao);
            
            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit;
            } else {
                $error = "Erro ao cadastrar: " . $conn->error;
            }
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
                <select>
                    <option>Informática</option>
                    <option>Higienização</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nível de Permissão</label>
                <select>
                    <option>Usuário</option>
                    <option>Administrador</option>
                </select>
            </div>
        </div>

        <button type="submit" name= "btn_register" class="btn-cadastrar">Cadastrar Funcionário</button>

        <input type="text" name="groups" hidden id="">
        
    </form>

    <div class="info-grid">

        <div class="info-box">
            <th>MEMBRO DOS GRUPOS</th>
            <table><tr><td>Aguardando seleção...</td></tr></table>
        </div>
        
        <div class="info-box">
            <th>PERMISSÕES ATIVAS</th>
            <table><tr><td>Aguardando seleção...</td></tr></table>
        </div>

    </div>


</div>

</body>
</html>