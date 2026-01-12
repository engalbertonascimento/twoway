<?php
// 1. INÍCIO DA SESSÃO E SEGURANÇA
session_start();
include '../../engine/config.php'; 

// Verificação de segurança: Nível de acesso
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?erro=acesso_negado');
    exit();
}

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

// Filtro por nome do grupo
if (!empty($_GET['nome'])) {
    $nome = $conn->real_escape_string($_GET['nome']);
    $where .= " AND g.nome LIKE '%$nome%'";
}

// Filtro por descrição (substituindo o antigo 'usuario')
if (!empty($_GET['descricao'])) {
    $descricao = $conn->real_escape_string($_GET['descricao']);
    $where .= " AND g.descricao LIKE '%$descricao%'";
}

// 3. CONSULTA COMPATIBILIZADA
// Fazemos um JOIN com a tabela 'usuarios' para saber quem é o criador
$sql = "SELECT * FROM usuarios $where";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> - Chat Interno</title>
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
                 <strong>Monitoramento</strong>
            </div>
            <div>
                <span style="margin-right: 20px;">Olá, <strong><?php echo $_SESSION['username']; ?></strong></span>
                <a href="../../logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <form action="index.php" method="GET">
                <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <div style="display: flex; flex-direction: column;">
                        <label style="font-size: 0.8rem; margin-bottom: 5px;">Nome do Usuário</label>
                        <input type="text" name="nome" placeholder="Nome" value="<?php echo $_GET['nome'] ?? ''; ?>" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    </div>
                    
                    


                    <button type="submit" class="btn-acao">Buscar</button>
                   
                </div>
            </form>
        </div>

        <div style="background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #ecf0f1; border-bottom: 2px solid #ddd;">
                        <th style="padding: 15px; text-align: left;">ID</th>
                        <th style="padding: 15px; text-align: left;">Nome de usuário</th>
                        <th style="padding: 15px; text-align: left;">Ultimo login</th>
                        <th style="padding: 15px; text-align: left;">Usuário criador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px 15px;"><?php echo $row['id']; ?></td>
                                <td style="padding: 12px 15px;"><?php echo $row['nome_completo']; ?></td>
                                <td style="padding: 12px 15px;"><code><?php echo $row['data_cadastro']; ?></code></td>
                                <td style="padding: 12px 15px;"><code><?php echo $row['usuario_criador']; ?></code></td>
                                
                                    
                                
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: #999;">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
























</html>
