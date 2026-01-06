<?php
// Página de Login
session_start();
include 'engine/config.php';

$error = '';

if (isset($_GET['success'])) {
    $error = "Usuário cadastrado com sucesso! Faça login.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // NOVO: Adicionamos 'nivel_acesso' no SELECT
    $stmt = $conn->prepare("SELECT id, username, password_hash, nivel_acesso FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($password, $usuario['password_hash'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            // NOVO: Salvamos o nível de acesso na sessão para usar no Chat
            $_SESSION['nivel_acesso'] = $usuario['nivel_acesso']; 
            
            header("Location: pages/chat/chat.php");
            exit;
        } else {
            $error = "Senha incorreta.";
        }
    } else {
        $error = "Usuário não encontrado.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - TWOWAY</title>
    <link rel="stylesheet" href="styles/cadastro/cadastro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

    <body class="registro-body">

        <div class="registro-container">
            <h1 class="logo-text">TWOWAY</h1>
                <h2>Faça login em sua conta</h2>

                    <?php if ($error) : ?>
                        <p style="color: <?php echo isset($_GET['success']) ? 'green' : 'red'; ?>;">
                            <?php echo $error; ?>
                        </p>
                    <?php endif; ?>

                <form method="POST" action="index.php">

                    <div class="input-group">
                        <label>Usuário</label>
                        <input type="text" name="username" placeholder="Seu nome de usuário" required>
                    </div>

                    <div class="input-group">
                        <label>Senha</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    
                    <button class="btn-cadastrar" type="submit">Entrar</button>
                </form>

            <p class="footer-link">Esqueceu sua Senha? <br> <a href="redefinir.php" style="text-decoration: none;">Alterar senha</a></p>

            <p class="footer-link">Não tem conta? <br> <a href="registro.php" style = "text-decoration: none">Cadastre-se aqui</a></p>

            <p class="footer-link">v0.0.15</p>
        </div>


    </body>
</html>