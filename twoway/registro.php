<?php
// registro.php
session_start();
include 'engine/config.php';

$error = '';

if (isset($_POST['register'])) {
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];

    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $new_username, $password_hash);
    
    if ($stmt->execute()) {
        // Redireciona para o login com uma mensagem de sucesso via URL
        header("Location: index.php?success=1");
        exit;
    } else {
        $error = "Erro ao cadastrar: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Chat Interno</title>
    <link rel="stylesheet" href="styles\cadastro\style.css">
    
<body style ="background-image:url('Teladelogin/background.png');
              background-position: center 30%;">
              <br><br><br><br> 

   <div style="box-shadow: 15px 4px 10px rgba(0, 0, 0, 0.64);
            background-color: rgba(137, 250, 244, 0.4);
            text-align: center;
            border-radius: 200px;
            width: 350px;
            height: 550px;">
    <br>
    <h1 style= "color: blue">TWOWAY</h1>
   <h2>Criar Nova Conta</h2>
    <?php if ($error) : ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

  <form method="POST" action="index.php">
                <label>Usuário</label>
                <br>
                <input type="text" name="username" required style= "border-radius: 10px; width: 200px; height: 25px;"><br><br>
                
                <label>Senha</label>
                <br>
                <input type="password" name="password" required style= "border-radius: 10px; width: 200px; height: 25px;"><br><br>

                <label> Confirmar senha </label>
                <br>
                <input type="password" name="password" required style= "border-radius: 10px; width: 200px; height: 25px;"><br><br>

                
                <button type="submit">Cadastrar</button>
            </form>
        
        <p>Já tem uma conta? <a href="index.php">Faça login aqui</a></p>
        <br>
    </div>
</body>
</html>