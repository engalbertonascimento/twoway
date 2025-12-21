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
    <title>Login - Chat Interno</title>
    <link rel="stylesheet" href="styles/login/style.css">
</head>

<body  style ="background-image:url('Teladelogin/background.png');
              background-position: center 30%;">


    <br><br><br><br>

    <div style="
    box-shadow: 15px 4px 10px rgba(0, 0, 0, 0.64);
    background-color: rgba(137, 250, 244, 0.4);
    text-align: center;
    border-radius: 200px;
    width: 350px;
    height: 550px;
">
        <br>
        <h1 style= "color: blue">TWOWAY</h1>

            <?php if ($error) : ?>
                <p style="color: <?php echo isset($_GET['success']) ? 'green' : 'red'; ?>;">
                    <?php echo $error; ?>
                </p>
            <?php endif; ?>
            

            <form method="POST" action="index.php">
                <label>Usuário:</label>
                <br>
                <input type="text" name="username" required style= "border-radius: 10px; width: 200px; height: 25px;"><br><br>
                
                <label>Senha:</label>
                <br>
                <input type="password" name="password" required style= "border-radius: 10px; width: 200px; height: 25px;"><br><br>
                
                <button type="submit">Entrar</button>
            </form>
        <p>
        <a href="redefinir.php" style="text-decoration: none;">Alterar senha</a>
        </p>
        <p>Não tem conta? <br> <a href="registro.php" style = "text-decoration: none">Cadastre-se aqui</a></p>
        <br>
    </div>


</body>
</html>