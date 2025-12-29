<?php
// config.php
$servername = "localhost";
$username_db = "root"; // Altere se o seu usuário MySQL não for 'root'
$password_db = "";     // Altere se você tiver uma senha para o MySQL
$dbname = "twoway"; // Nome da base de dados que você criou

// Cria a conexão
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    // Para ambientes de produção, use uma mensagem mais genérica por segurança
    die("Falha na Conexão com o Banco de Dados: " . $conn->connect_error);
}

// Opcional: Define o charset para evitar problemas com acentuação
$conn->set_charset("utf8mb4");
?>