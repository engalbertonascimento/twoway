<?php
session_start();
include 'config.php';

// 1. Segurança: Apenas administradores podem deletar usuários
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: index.php?erro=sem_permissao");
    exit;
}

// 2. Verifica se o ID foi passado na URL
if (isset($_GET['id'])) {
    $id_para_excluir = intval($_GET['id']);
    $id_logado = $_SESSION['user_id'];

    // 3. Impedir que o administrador exclua a si próprio por acidente
    if ($id_para_excluir == $id_logado) {
        header("Location: ../pages/index.php?erro=auto_exclusao");
        exit;
    }

    // 4. Prepara a exclusão
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_para_excluir);

    if ($stmt->execute()) {
        // Sucesso: Redireciona para a lista de usuários com mensagem
        header("Location: ../pages/search_user/index.php?status=sucesso_exclusao");
    } else {
        // Erro de banco de dados
        header("Location: ../pages/search_user/index.php?erro=falha_banco&detalhe=" . $conn->error);
    }
    
    $stmt->close();
} else {
    // Se acessarem o arquivo sem passar o ID
    header("Location: ../pages/search_user/index.php");
}

$conn->close();
?>