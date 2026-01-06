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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Perfil - <?php echo $usuario['nome_completo']; ?></title>
    <link rel="stylesheet" href="../../styles/profile/styles.css">
    <link rel="stylesheet" href="../../styles/search_users/styles.css">
    <link rel="stylesheet" href="../../styles/minimo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
                Perfil de Usuário
            </div>
            <div>
                <span style="margin-right: 20px;">Olá, <strong><?php echo $_SESSION['username']; ?></strong></span>
                <a href="../../logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

<div class="main-wrapper">

    <div class="content-layout">
        <aside class="sidebar-profile">
            <div class="profile-card">
                <div id="perfil-user">
                    <img src="../../<?php echo $usuario['profile_pic']; ?>">
                </div>
                <h2><?php echo htmlspecialchars($usuario['nome_completo']); ?></h2>
                <span class="badge-role <?php echo $usuario['nivel_acesso']; ?>">
                    <?php echo strtoupper($usuario['nivel_acesso']); ?>
                </span>
                
                <div class="quick-stats">
                    <div class="stat-box">
                        <strong><?php echo $meus_grupos->num_rows; ?></strong>
                        <span>Grupos</span>
                    </div>
                    <div class="stat-box">
                        <strong><?php echo $criados_count; ?></strong>
                        <span>Criados</span>
                    </div>
                </div>

                <ul class="contact-list">
                    <li><i class="fa fa-at"></i> <?php echo $usuario['username']; ?></li>
                    <li><i class="fa fa-envelope"></i> <?php echo $usuario['email']; ?></li>
                    <li><i class="fa fa-phone"></i> <?php echo $usuario['telefone'] ?? '(00) 0000-0000'; ?></li>
                </ul>
                
                <button class="btn-main-edit">Editar Dados Básicos</button>
            </div>
        </aside>

        <main class="main-tabs">
            <ul class="nav-fichario">
                <li class="tab-link active" onclick="switchTab(event, 'tab-grupos')">Grupos Participantes</li>
                <li class="tab-link" onclick="switchTab(event, 'tab-info')">Dados Técnicos</li>
                <li class="tab-link admin-only" onclick="switchTab(event, 'tab-admin')">Gestão Administrativa</li>
            </ul>

            <div id="tab-grupos" class="tab-content active">
                <div class="group-grid">
                    <?php while($g = $meus_grupos->fetch_assoc()): ?>
                        <div class="group-item">
                            <img src="../../<?php echo $g['capa_path']; ?>">
                            <div class="group-details">
                                <h4><?php echo $g['nome']; ?></h4>
                                <small>Entrou em: <?php echo date('d/m/y', strtotime($g['joined_at'])); ?></small>
                                <?php if($g['is_admin']) echo '<span class="owner-tag">Admin do Grupo</span>'; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="tab-info" class="tab-content">
                <div class="technical-info">
                    <div class="info-row"><span>ID Interno:</span> <strong>#<?php echo $usuario['id']; ?></strong></div>
                    <div class="info-row"><span>Data de Cadastro:</span> <strong><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></strong></div>
                    <div class="info-row"><span>Status do Sistema:</span> <strong><?php echo $usuario['status'] ? 'Ativo' : 'Bloqueado'; ?></strong></div>
                </div>
            </div>

            <div id="tab-admin" class="tab-content">
                <div class="admin-panel">
                    <h3>Ações de Controle</h3>
                    <div class="admin-actions">
                        <div class="action-card">
                            <label>Nível de Acesso</label>
                            <select class="form-control" onchange="updateAccess(<?php echo $usuario_id; ?>, this.value)">
                                <option value="usuario" <?php if($usuario['nivel_acesso'] == 'usuario') echo 'selected'; ?>>Usuário Comum</option>
                                <option value="admin" <?php if($usuario['nivel_acesso'] == 'admin') echo 'selected'; ?>>Administrador</option>
                            </select>
                        </div>

                        <div class="action-card">
                            <label>Status da Conta</label>
                            <button class="btn-status <?php echo $usuario['status'] ? 'btn-active' : 'btn-blocked'; ?>">
                                <?php echo $usuario['status'] ? 'Conta Ativa' : 'Conta Bloqueada'; ?>
                            </button>
                        </div>

                        <div class="action-card danger-zone">
                            <label>Zona de Perigo</label>
                            <button class="btn-delete">Excluir Usuário Permanentemente</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

                </div>

<script src="script.js"></script>
</body>
</html>