<?php
// admin/search_users.php
session_start();
include 'config.php'; // Verifique se o caminho para o seu config está correto

// Segurança: Se não for admin, bloqueia o acesso
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Acesso negado']));
}

// Captura os termos de busca via GET
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$nome     = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$cpf      = isset($_GET['cpf']) ? trim($_GET['cpf']) : '';

// Prepara os termos para o LIKE (busca parcial)
$paramUser = "%$username%";
$paramNome = "%$nome%";
$paramCpf  = "%$cpf%";

// SQL que filtra pelas 3 colunas simultaneamente
$sql = "SELECT id, username, nome_completo, cpf, nivel_acesso 
        FROM usuarios 
        WHERE username LIKE ? 
          AND nome_completo LIKE ? 
          AND cpf LIKE ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $paramUser, $paramNome, $paramCpf);
$stmt->execute();
$result = $stmt->get_result();

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

// Retorna o resultado para o JavaScript
header('Content-Type: application/json');
echo json_encode($usuarios);

$stmt->close();
$conn->close();