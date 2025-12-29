function switchTab(evt, tabId) {
    // Esconde todas as abas
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));

    // Remove classe active dos botões
    const tabs = document.querySelectorAll('.tab-link');
    tabs.forEach(tab => tab.classList.remove('active'));

    // Mostra a selecionada
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// Exemplo de função administrativa (apenas o esqueleto)
function updateAccess(userId, newLevel) {
    if(confirm("Deseja alterar o nível de acesso deste usuário para " + newLevel + "?")) {
        // Aqui você faria um fetch() para um arquivo PHP de processamento
        console.log("Alterando usuário " + userId + " para " + newLevel);
    }
}