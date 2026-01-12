<?php
// admin/search_users.php
session_start();
include 'config.php'; // Certifique-se que o caminho está correto

// Se não for admin, redireciona para a página de login
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Acesso negado']));
}

// Parâmetros de busca (recebidos via GET do JavaScript)
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$nome     = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$email    = isset($_GET['email']) ? trim($_GET['email']) : '';
$telefone = isset($_GET['telefone']) ? trim($_GET['telefone']) : '';

// Prepara os termos para o LIKE (busca parcial)
$paramUser     = "%$username%";
$paramNome     = "%$nome%";
$paramEmail    = "%$email%";
$paramTelefone = "%$telefone%"; // Adicionado o ponto e vírgula que faltava

// SQL ajustado para as colunas da sua tabela 'usuarios'
// Como telefone é DECIMAL, o SQL converte automaticamente para string ao usar LIKE
$sql = "SELECT id, username, nome_completo, email, nivel_acesso, telefone 
        FROM usuarios 
        WHERE username LIKE ? 
          AND nome_completo LIKE ? 
          AND email LIKE ?
          AND CAST(telefone AS CHAR) LIKE ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

// CORREÇÃO: Eram 4 "?" no SQL, então precisamos de "ssss" e apenas 4 variáveis
$stmt->bind_param("ssss", $paramUser, $paramNome, $paramEmail, $paramTelefone);

$stmt->execute();
$result = $stmt->get_result();

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    // Garantir que o telefone venha como string limpa para o JSON
    $usuarios[] = $row;
}

// Retorna o resultado para o JavaScript
header('Content-Type: application/json');
echo json_encode($usuarios);

$stmt->close();
$conn->close();