<?php
// admin/index.php
session_start();

// Proteção da página: Só entra se for admin
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - TWOWAY</title>
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --bg: #f4f7f6;
        }

        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 20px; }
        
        .container { max-width: 1000px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        
        h2 { margin: 0; color: #333; }

        /* Estilo dos Inputs Laterais */
        .search-area { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 25px; 
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 8px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .input-group { display: flex; flex-direction: column; flex: 1; min-width: 200px; }
        
        .input-group label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #666; }

        input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }

        button#btn-buscar { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 10px 25px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: bold;
            height: 38px;
        }

        /* Estilo da Tabela */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        
        th { background: #333; color: white; padding: 12px; text-align: left; }
        
        td { padding: 12px; border-bottom: 1px solid #eee; color: #444; }

        tr:hover { background: #f1f1f1; }

        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-admin { background: #e3f2fd; color: #0d47a1; }
        .badge-user { background: #f5f5f5; color: #616161; }

        .btn-voltar { text-decoration: none; color: #666; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h2>🛡️ Gestão de Usuários</h2>
        <a href="../chat/chat.php" class="btn-voltar">← Voltar ao Chat</a>
    </header>

    <div class="search-area">
        <div class="input-group">
            <label>USUÁRIO (Login)</label>
            <input type="text" id="s-username" placeholder="Ex: joao.silva">
        </div>
        <div class="input-group">
            <label>NOME COMPLETO</label>
            <input type="text" id="s-nome" placeholder="Ex: João da Silva">
        </div>
        <div class="input-group">
            <label>CPF</label>
            <input type="text" id="s-cpf" placeholder="000.000.000-00">
        </div>
        <button id="btn-buscar">PESQUISAR</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuário</th>
                <th>Nome Completo</th>
                <th>CPF</th>
                <th>Nível</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="resultado-tabela">
            <tr>
                <td colspan="6" style="text-align:center; padding: 40px; color: #999;">
                    Preencha os campos acima para realizar uma consulta.
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('btn-buscar').addEventListener('click', function() {
        const user = document.getElementById('s-username').value;
        const nome = document.getElementById('s-nome').value;
        const cpf = document.getElementById('s-cpf').value;
        const tbody = document.getElementById('resultado-tabela');

        // Feedback visual de carregamento
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px;">Processando consulta...</td></tr>';

        // Faz a requisição ao search_users.php
        fetch(`../../engine/search_users.php?username=${encodeURIComponent(user)}&nome=${encodeURIComponent(nome)}&cpf=${encodeURIComponent(cpf)}`)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px; color: red;">Nenhum usuário encontrado com esses critérios.</td></tr>';
                    return;
                }

                data.forEach(u => {
                    const badgeClass = u.nivel_acesso === 'admin' ? 'badge-admin' : 'badge-user';
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${u.id}</td>
                        <td><strong>${u.username}</strong></td>
                        <td>${u.nome_completo || '---'}</td>
                        <td>${u.cpf || '---'}</td>
                        <td><span class="badge ${badgeClass}">${u.nivel_acesso}</span></td>
                        <td>
                            <button onclick="editarUser(${u.id})" title="Editar" style="border:none; background:none; cursor:pointer;">✏️</button>
                            <button onclick="excluirUser(${u.id})" title="Excluir" style="border:none; background:none; cursor:pointer; color:red; margin-left:10px;">🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Erro:', error);
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Erro interno ao processar busca.</td></tr>';
            });
    });

    // Funções de alerta (Exemplos)
    function editarUser(id) { alert('Abrir tela de edição para o ID: ' + id); }
    function excluirUser(id) { if(confirm('Deseja realmente excluir este usuário?')) alert('ID ' + id + ' excluído!'); }
</script>

</body>
</html>