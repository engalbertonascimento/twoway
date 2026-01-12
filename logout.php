<?php
// logout.php

// 1. Inicia a sessão (precisamos dela para acessar os dados)
session_start();

// 2. Destrói todas as variáveis de sessão (limpa $_SESSION)
$_SESSION = array();

// 3. Se estiver usando cookies de sessão, destrói o cookie também.
// Isso garante que o navegador esqueça a sessão.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destrói a sessão
session_destroy();

// 5. Redireciona o usuário para a página de login
header("Location: index.php");
exit;
?>