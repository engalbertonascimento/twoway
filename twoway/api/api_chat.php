<?php
// api_chat.php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ------------------------------------------
// AÇÃO 1: ENVIAR MENSAGEM (Método POST)
// ------------------------------------------
if ($action == 'send_message' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Usamos $_POST para o corpo da requisição POST
    $destinatario_id = filter_var($_POST['destinatario_id'], FILTER_VALIDATE_INT);
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!$destinatario_id || empty($mensagem)) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $destinatario_id, $mensagem);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// ------------------------------------------
// AÇÃO 2: BUSCAR MENSAGENS (Método GET)
// ------------------------------------------
if ($action == 'get_messages' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    // Usamos $_GET para os parâmetros da URL
    $destinatario_id = filter_var($_GET['destinatario_id'], FILTER_VALIDATE_INT);

    if (!$destinatario_id) {
        echo json_encode([]);
        exit;
    }

    // Busca mensagens entre o usuário logado ($user_id) e o contato selecionado ($destinatario_id)
    $query = "
        SELECT remetente_id, destinatario_id, mensagem, timestamp
        FROM mensagens
        WHERE (remetente_id = ? AND destinatario_id = ?) 
           OR (remetente_id = ? AND destinatario_id = ?)
        ORDER BY timestamp ASC";

    $stmt = $conn->prepare($query);
    // Bind 4 vezes: $user_id/$destinatario_id e $destinatario_id/$user_id
    $stmt->bind_param("iiii", $user_id, $destinatario_id, $destinatario_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode($messages);
    $stmt->close();
    $conn->close();
    exit;
}

// Se nenhuma ação válida for encontrada
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Ação inválida']);
$conn->close();

?>