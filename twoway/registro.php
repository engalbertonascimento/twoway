<?php
session_start();
include 'engine/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_register'])) {
    $nome = $_POST['nome_completo'];
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $conf = $_POST['confirm_password'];

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
            $stmt = $conn->prepare("INSERT INTO usuarios (nome_completo, username, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $user, $password_hash);
            
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
        <h2>Criar Nova Conta</h2>

        <?php if ($error) : ?>
            <div class="error-msg">
                <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="registro.php">
            <div class="input-group">
                <label>Nome Completo</label>
                <input type="text" name="nome_completo" placeholder="Ex: Samuel Doe" required>
            </div>

            <div class="input-group">
                <label>Usuário</label>
                <input type="text" name="username" placeholder="Seu nome de usuário" required>
            </div>

            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="input-group">
                <label>Confirmar Senha</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required>
            </div>

            <button type="submit" name="btn_register" class="btn-cadastrar">Cadastrar</button>
        </form>

        <p class="footer-link">Já tem uma conta? <a href="index.php">Faça login aqui</a></p>
    </div>

</body>
</html>