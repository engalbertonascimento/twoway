<?php
// api_chat.php - v0.7
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

// Pasta onde os arquivos serão salvos (ajuste se necessário)
$upload_dir = 'uploads/'; 

// Garante que a pasta de upload existe
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


switch ($action) {
    
    // ------------------------------------------
    // AÇÃO 1: ENVIAR MENSAGEM DE TEXTO (Método POST)
    // ------------------------------------------
    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }

        $destinatario_id = filter_var($_POST['destinatario_id'] ?? '', FILTER_VALIDATE_INT);
        $mensagem = trim($_POST['mensagem'] ?? '');
        // NOVO: ID da mensagem que está sendo respondida. Usa NULL se não houver resposta.
        $reply_to_id = filter_var($_POST['reply_to_id'] ?? '', FILTER_VALIDATE_INT) ?: NULL; 

        if (!$destinatario_id || (empty($mensagem) && !$reply_to_id)) { // Permite mensagem vazia se for uma resposta
            echo json_encode(['success' => false, 'message' => 'Dados incompletos (destinatário, mensagem ou ID de resposta)']);
            break;
        }

        // Usamos NULL para arquivo_path
        $null_path = NULL; 
        
        $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, arquivo_path, reply_to_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $user_id, $destinatario_id, $mensagem, $null_path, $reply_to_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao enviar: ' . $conn->error]);
        }
        $stmt->close();
        break;

// ------------------------------------------
    // AÇÃO 2: ENVIAR ARQUIVO (Qualquer tipo)
    // ------------------------------------------
    case 'send_media':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $destinatario_id = filter_var($_POST['destinatario_id'] ?? '', FILTER_VALIDATE_INT);
        $mensagem = trim($_POST['mensagem'] ?? ''); 
        $reply_to_id = filter_var($_POST['reply_to_id'] ?? '', FILTER_VALIDATE_INT) ?: NULL; 

        if (!$destinatario_id || !isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Arquivo não enviado ou erro no upload.']);
            break;
        }

        $file = $_FILES['media'];
        $original_name = $file['name'];
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // --- SEGURANÇA: Lista negra de extensões perigosas ---
        $forbidden = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', 'exe', 'bat', 'sh', 'js', 'html', 'htm'];
        if (in_array($extension, $forbidden)) {
            echo json_encode(['success' => false, 'message' => 'Este tipo de arquivo não é permitido por motivos de segurança.']);
            break;
        }
        
        // Gerar nome único para evitar sobrescrever arquivos
        $filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // DICA: Se quiser guardar o nome original, você pode concatenar na mensagem 
            // ou criar uma coluna 'nome_original' no seu banco de dados.
            // Aqui, vamos apenas salvar o caminho.
            
            $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, arquivo_path, reply_to_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $user_id, $destinatario_id, $mensagem, $destination, $reply_to_id);

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
    // AÇÃO 3: BUSCAR MENSAGENS (Método GET) - ALTERADA
    // ------------------------------------------
    case 'get_messages':
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }

        $destinatario_id = filter_var($_GET['destinatario_id'] ?? '', FILTER_VALIDATE_INT);

        if (!$destinatario_id) {
            echo json_encode([]);
            break;
        }
        
        // NOVO SQL: Usa LEFT JOIN (JOIN mensagens r) para buscar os dados da mensagem respondida
        $query = "
            SELECT 
                m.id, m.remetente_id, m.destinatario_id, m.mensagem, m.arquivo_path, m.timestamp, m.reply_to_id,
                
                -- Dados da Mensagem Respondida (r)
                r.mensagem AS replied_mensagem,
                r.arquivo_path AS replied_arquivo_path,
                r.remetente_id AS replied_remetente_id
            FROM mensagens m
            LEFT JOIN mensagens r ON m.reply_to_id = r.id
            WHERE (m.remetente_id = ? AND m.destinatario_id = ?) 
                OR (m.remetente_id = ? AND m.destinatario_id = ?)
            ORDER BY m.timestamp ASC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiii", $user_id, $destinatario_id, $destinatario_id, $user_id);
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
    // AÇÃO 4: APAGAR MENSAGEM (Método POST)
    // ------------------------------------------
    case 'delete_message':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }
        
        $message_id = filter_var($_POST['message_id'] ?? '', FILTER_VALIDATE_INT);

        if (!$message_id) {
            echo json_encode(['success' => false, 'message' => 'ID da mensagem é obrigatório.']);
            break;
        }
        
        // 1. Antes de apagar, precisamos saber se há um arquivo associado
        $stmt_select = $conn->prepare("SELECT arquivo_path FROM mensagens WHERE id = ? AND remetente_id = ?");
        $stmt_select->bind_param("ii", $message_id, $user_id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result();
        $message_data = $result_select->fetch_assoc();
        $stmt_select->close();

        if (!$message_data) {
             echo json_encode(['success' => false, 'message' => 'Mensagem não encontrada ou você não tem permissão.']);
             break;
        }
        
        $file_to_delete = $message_data['arquivo_path'];
        
        // 2. Apaga do banco de dados (segurança garantida pelo remetente_id)
        $stmt_delete = $conn->prepare("DELETE FROM mensagens WHERE id = ? AND remetente_id = ?");
        $stmt_delete->bind_param("ii", $message_id, $user_id);

        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                // 3. Apaga o arquivo físico se existir
                if (!empty($file_to_delete) && file_exists($file_to_delete)) {
                    @unlink($file_to_delete);
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'A mensagem não foi encontrada ou você não tem permissão.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao apagar: ' . $conn->error]);
        }
        $stmt_delete->close();
        break;

// ------------------------------------------
    // AÇÃO 5: BUSCA GLOBAL DE USUÁRIOS (Para encontrar novos contatos)
    // ------------------------------------------
    case 'search_global':
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
        }

        $termo = trim($_GET['q'] ?? '');

        // Segurança: Só busca se o usuário digitar pelo menos 2 caracteres
        if (strlen($termo) < 2) {
            echo json_encode([]);
            break;
        }

        $termo_param = $termo . '%'; // Busca nomes que começam com o termo

        // SQL: Busca usuários que NÃO são o logado e que batem com o nome
        $stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE username LIKE ? AND id != ? LIMIT 10");
        $stmt->bind_param("si", $termo_param, $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro na busca']);
        }
        $stmt->close();
        break;

        // DENTRO DO switch ($action) no api_chat.php
case 'search_global':
    $termo = trim($_GET['q'] ?? '');
    if (strlen($termo) < 2) { echo json_encode([]); exit; }

    $termo_param = $termo . '%';
    // Busca usuários que NÃO são você e que batem com o nome
    $stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE username LIKE ? AND id != ? LIMIT 10");
    $stmt->bind_param("si", $termo_param, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
    exit; // Importante para não executar o default


    // ------------------------------------------
    // AÇÃO PADRÃO: Ação inválida
    // ------------------------------------------
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida ou não especificada']);
        break;
}

// Fecha a conexão no final, após o processamento de qualquer ação
$conn->close();

?>