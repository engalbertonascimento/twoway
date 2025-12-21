<?php
$error = ""; // inicializa a variável

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($_POST['nova_senha'] != $_POST['confirmar_senha']) {
        $error = "As senhas não coincidem";
    } else {
        // aqui segue o processo quando estiver tudo certo
    }
}
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
   <h2>Redefinição de senha</h2>
    <?php if ($error) : ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

 <form method="POST" action="">

    <label>Usuário</label><br>
    <input type="text" name="username" required
           style="border-radius: 10px; width: 200px; height: 25px;">
    <br><br>

    <label>Senha atual</label><br>
    <input type="password" name="senha_atual" required
           style="border-radius: 10px; width: 200px; height: 25px;">
    <br><br>

    <label>Nova senha</label><br>
    <input type="password" name="nova_senha" required
           style="border-radius: 10px; width: 200px; height: 25px;">
    <br><br>

    <label>Confirmar senha</label><br>
    <input type="password" name="confirmar_senha" required
           style="border-radius: 10px; width: 200px; height: 25px;">
    <br><br>

    <button type="submit">Alterar</button>
</form>

<p>
    <a href="index.php">Faça login aqui</a>
</p>

<br>
    </div>
</body>
</html>