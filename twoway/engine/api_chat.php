<?php
// api_chat.php - Versão com Upload de Mídia, Respostas e Grupos
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

// Pasta onde os arquivos serão salvos
$upload_dir = '../pages/chat/uploads/'; 

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

switch ($action) {
    
    // ------------------------------------------
    // AÇÃO 1: ENVIAR MENSAGEM DE TEXTO
    // ------------------------------------------
    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }

        $destinatario_id = filter_var($_POST['destinatario_id'] ?? '', FILTER_VALIDATE_INT);
        $tipo = $_POST['tipo'] ?? 'privado'; // NOVO: identifica se é 'privado' ou 'grupo'
        $mensagem = trim($_POST['mensagem'] ?? '');
        $reply_to_id = filter_var($_POST['reply_to_id'] ?? '', FILTER_VALIDATE_INT) ?: NULL; 

        if (!$destinatario_id || (empty($mensagem) && !$reply_to_id)) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            break;
        }

        // Define qual coluna será preenchida
        $col_destino = ($tipo === 'grupo') ? "grupo_id" : "destinatario_id";
        $null_path = NULL; 
        
        $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, $col_destino, mensagem, arquivo_path, reply_to_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $user_id, $destinatario_id, $mensagem, $null_path, $reply_to_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao enviar: ' . $conn->error]);
        }
        $stmt->close();
        break;

    // ------------------------------------------
    // AÇÃO 2: ENVIAR ARQUIVO
    // ------------------------------------------
    case 'send_media':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $destinatario_id = filter_var($_POST['destinatario_id'] ?? '', FILTER_VALIDATE_INT);
        $tipo = $_POST['tipo'] ?? 'privado';
        $mensagem = trim($_POST['mensagem'] ?? ''); 
        $reply_to_id = filter_var($_POST['reply_to_id'] ?? '', FILTER_VALIDATE_INT) ?: NULL; 

        if (!$destinatario_id || !isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Arquivo não enviado.']);
            break;
        }

        $file = $_FILES['media'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $forbidden = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'bat', 'sh', 'js', 'html'];

        if (in_array($extension, $forbidden)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido.']);
            break;
        }
        
        $filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $path_for_db = 'uploads/' . $filename; 
            $col_destino = ($tipo === 'grupo') ? "grupo_id" : "destinatario_id";

            $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, $col_destino, mensagem, arquivo_path, reply_to_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $user_id, $destinatario_id, $mensagem, $path_for_db, $reply_to_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                @unlink($destination);
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao mover arquivo.']);
        }
        break;

    // ------------------------------------------
    // AÇÃO 3: BUSCAR MENSAGENS
    // ------------------------------------------
    case 'get_messages':
        $destinatario_id = filter_var($_GET['destinatario_id'] ?? '', FILTER_VALIDATE_INT);
        $tipo = $_GET['tipo'] ?? 'privado';

        if (!$destinatario_id) {
            echo json_encode([]);
            break;
        }
        
        if ($tipo === 'grupo') {
            // SQL para Grupos: busca pelo grupo_id e traz o nome do remetente
            $query = "
                SELECT m.*, u.username as remetente_nome,
                       r.mensagem AS replied_mensagem, r.arquivo_path AS replied_arquivo_path
                FROM mensagens m
                LEFT JOIN usuarios u ON m.remetente_id = u.id
                LEFT JOIN mensagens r ON m.reply_to_id = r.id
                WHERE m.grupo_id = ?
                ORDER BY m.timestamp ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $destinatario_id);
        } else {
            // SQL Privado: Mantém sua lógica original de conversa entre dois IDs
            $query = "
                SELECT m.*, 
                       r.mensagem AS replied_mensagem, r.arquivo_path AS replied_arquivo_path
                FROM mensagens m
                LEFT JOIN mensagens r ON m.reply_to_id = r.id
                WHERE (m.remetente_id = ? AND m.destinatario_id = ?) 
                   OR (m.remetente_id = ? AND m.destinatario_id = ?)
                ORDER BY m.timestamp ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiii", $user_id, $destinatario_id, $destinatario_id, $user_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        echo json_encode($messages);
        $stmt->close();
        break;
        
    // ------------------------------------------
    // AÇÃO 4: APAGAR MENSAGEM
    // ------------------------------------------
    case 'delete_message':
        $message_id = filter_var($_POST['message_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$message_id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            break;
        }
        
        $stmt_select = $conn->prepare("SELECT arquivo_path FROM mensagens WHERE id = ? AND remetente_id = ?");
        $stmt_select->bind_param("ii", $message_id, $user_id);
        $stmt_select->execute();
        $res = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();

        if ($res) {
            $stmt_delete = $conn->prepare("DELETE FROM mensagens WHERE id = ? AND remetente_id = ?");
            $stmt_delete->bind_param("ii", $message_id, $user_id);
            if ($stmt_delete->execute()) {
                if (!empty($res['arquivo_path']) && file_exists($res['arquivo_path'])) {
                    @unlink($res['arquivo_path']);
                }
                echo json_encode(['success' => true]);
            }
            $stmt_delete->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        break;
}

if ($conn->ping()) {
    $conn->close();
}
?>