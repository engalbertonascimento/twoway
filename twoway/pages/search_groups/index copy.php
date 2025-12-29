<?php
// 1. INÍCIO DA SESSÃO E SEGURANÇA
session_start();

// Verifica se o usuário está logado e se o perfil é 'admin'
// Se não for admin, redireciona para a página de login
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Acesso negado']));
}

// Dados do usuário logado para exibir no topo
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Chat Interno</title>
    <link rel="stylesheet" href="styles/admin.css"> <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
        }

        /* Barra Lateral (Sidebar) */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            padding-top: 20px;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #ecf0f1;
        }

        .sidebar a {
            display: block;
            color: #bdc3c7;
            padding: 15px 25px;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #34495e;
            color: white;
        }

        /* Conteúdo Principal */
        .main-content {
            margin-left: 250px;
            padding: 40px;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .welcome-msg {
            font-size: 1.5rem;
            color: #333;
        }

        /* Cards de Configuração */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            margin-top: 0;
            color: #2c3e50;
        }

        .card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .btn-acao {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }

        .btn-acao:hover {
            background-color: #2980b9;
        }

        .btn-logout {
            background-color: #e74c3c;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Chat</h2>
        <a href="admin.php">🏠 Dashboard</a>
        <a href="../search_users/index.php">🔍 Gerenciar Usuários</a>
        <a href="relatorios.php">📊 Relatórios</a>
        <a href="configuracoes.php">⚙️ Configurações</a>
        <a href="logout.php" style="color: #e74c3c; margin-top: 50px;">🚪 Sair</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="welcome-msg">
                Bem-vindo, <strong><?php echo htmlspecialchars($username); ?></strong>!
            </div>
            <a href="logout.php" class="btn-logout">Sair do Sistema</a>
        </div>

        <h2>Painel de Controle Interno</h2>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Usuários</h3>
                <p>Pesquise, edite, cadastre ou exclua membros do chat interno.</p>
                <a href="buscar_usuario.php" class="btn-acao">Ir para Busca</a>
            </div>

            <div class="card">
                <h3>Configurações</h3>
                <p>Ajuste as permissões globais e termos de uso do sistema.</p>
                <a href="configuracoes.php" class="btn-acao">Configurar</a>
            </div>

            <div class="card">
                <h3>Logs de Acesso</h3>
                <p>Veja quem entrou no sistema e as ações realizadas recentemente.</p>
                <a href="relatorios.php" class="btn-acao">Ver Logs</a>
            </div>
        </div>
    </div>

</body>
</html>