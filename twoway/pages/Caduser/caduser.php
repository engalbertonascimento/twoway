<?php
// 1. INÍCIO DA SESSÃO E SEGURANÇA
session_start();
include '../../engine/config.php'; 

// Verificação de segurança: Nível de acesso
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: ../../login.php?erro=acesso_negado');
    exit();
}

// 2. LÓGICA DE BUSCA
$where = "WHERE 1=1"; 

if (!empty($_GET['nome'])) {
    $nome = $conn->real_escape_string($_GET['nome']);
    $where .= " AND nome_completo LIKE '%$nome%'";
}
if (!empty($_GET['usuario'])) {
    $usuario = $conn->real_escape_string($_GET['usuario']);
    $where .= " AND username LIKE '%$usuario%'";
}

$sql = "SELECT id, nome_completo, username, email, telefone FROM usuarios $where";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="../../styles/cadastro/cadastrointerno.css"> 
    
    
    <title>Usuário</title>
</head>
<body>
   


</div>
<div class="welcome-msg">
                Buscar <strong>Usuários</strong>
            </div>
            <div>
                <span style="margin-right: 20px;">Olá, <strong><?php echo $_SESSION['username']; ?></strong></span>
                <a href="../../logout.php" class="btn-logout">Sair</a>
            </div>
  <fieldset class="R1">
  
        <br> 
<p> <label>Nome Completo:</label>
    <input type="text" placeholder="Nome" style="width: 400px; height: 30px; border-radius: 10px;"> <br> <br> <br>
    <label>Usuário</label>
    <input type="texte" placeholder="Usuário" style="width: 200px; height: 24px; border-radius: 10px;"> 
    <label style="margin-left: 5%;">Senha:</label>
    <input type="password" placeholder="Senha" style="width: 200px; height: 24px; border-radius: 10px;"> <br> <br> <br>
    <label>E-mail:</label>
    <input type="text" placeholder="E-mail" style="width: 400px; height: 24px; border-radius: 10px;"><br><br>
    <label>Telefone:</label>
    <input type="tel" placeholder="(99) 99999-9999" 
     oninput="this.value = this.value.replace(/\D/g, '')" style="width:  ; height: 24px; border-radius: 10px;">
     <br> 

     
</p> 
<div class="R2"><p><label for="Grupos">Escolha de grupos:</label> 
    <select name="Grupos" id="Grupos">
    <option value="Informática">Informática</option>  
    <option value="Higienização">Higienização</option> 
</select> 
 </p></div>

<div class="R3"><p><label for="Permissão">Permissões</label>
<select name="Permissão" id="Permissão">
  <option value="ADM">Administrador</option>
  <option value="Usuário">Usuário</option> 
</select>
</p></div>

 <br> <br> <br> <br> <br> 
      <div class="R4"><button>Cadastrar</button></div>
</fieldset>      
    

<div class="R5">
      <div class="sidebar">
        <h2>Administração TwoWay</h2>
        <a href="../chat/chat.php">🏠 Voltar ao Chat</a>
        <a href="../search_users/index.php">🔍 Gerenciar Usuários</a>
        <a href="#">Funções Futuras</a>
        <a href="#">Funções Futuras</a>
        <!-- <a href="logout.php" style="color: #e74c3c; margin-top: 50px;">🚪 Sair</a> -->
    </div>

    <div class="main-content">
        
        <div class="header">
           
           
        </div>
 <div class="R6">
  <a href="../../logout.php" style="color: #ff4d4d; text-decoration: none; padding: 15px; display: block;">Sair do Chat</a>
</div>
<fieldset>
  <h4 style="text-align: center;">ADICIONADOS</h4> <br> <hr>
 <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: center;">Permissão para...</th>
                        
                    </tr>
                </thead>
                <tbody>
              
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px 15px;">rererer</td>
                               

</fieldset>
  
  
  
</div>     





    



    
</body>
</html>

























