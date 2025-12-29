<?php
session_start();
include 'config.php'; // Sua conexão com o banco

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? '';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($action === 'create_group') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'O nome é obrigatório']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Criar o grupo
        $stmt = $conn->prepare("INSERT INTO grupos (nome, descricao, criador_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nome, $descricao, $user_id);
        $stmt->execute();
        $grupo_id = $conn->insert_id;

        // 2. Adicionar o criador como membro Admin
        $stmt_membro = $conn->prepare("INSERT INTO grupo_membros (grupo_id, usuario_id, is_admin) VALUES (?, ?, 1)");
        $stmt_membro->bind_param("ii", $grupo_id, $user_id);
        $stmt_membro->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Grupo criado!', 'grupo_id' => $grupo_id]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
}