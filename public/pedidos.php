<?php
require_once __DIR__ . '/../bootstrap.php';
// ADICIONADO: Garante que apenas utilizadores logados possam ver os pedidos
require_once __DIR__ . '/../auth_check.php';

$pedidoModel = new Pedido();
$pedidos = $pedidoModel->listarTodos();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Pedidos - Sistema Quiosque</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Garante que o modal começa escondido */
        .modal-overlay {
            display: none; 
            /* Outros estilos do overlay (fundo escuro, etc.) devem vir do style.css */
            /* Se o seu overlay usa flex para centrar, display: flex pode ser melhor que block */
            /* display: flex; */ 
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">LISTA DE PEDIDOS</span>
            <div class="header-actions">
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="tabela-wrapper">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>ID do Pedido</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Valor Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($pedido['id']); ?></td>
                                <td><?php echo htmlspecialchars($pedido['cliente_nome'] ?? 'Cliente não encontrado'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                <td>
                                    <button class="btn btn-secondary btn-detalhes" data-id="<?php echo $pedido['id']; ?>">Ver Detalhes</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Nenhum pedido encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> </div>

    <div class="modal-overlay" id="modal-detalhes">
        <div class="modal-content"> <div class="modal-header">
                <h3 id="modal-titulo">Detalhes do Pedido</h3>
                <button class="modal-close" id="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <p>A carregar detalhes...</p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const modalOverlay = document.getElementById('modal-detalhes');
        const modalCloseBtn = document.getElementById('modal-close-btn');
        const modalTitle = document.getElementById('modal-titulo');
        const modalBody = document.getElementById('modal-body-content');
        
        document.querySelectorAll('.btn-detalhes').forEach(button => {
            button.addEventListener('click', async function() {
                const pedidoId = this.dataset.id;
                modalBody.innerHTML = '<p>A carregar detalhes...</p>';
                
                // =======================================================
                // ==== CORREÇÃO: Usar style.display para mostrar ====
                if (modalOverlay) { // Verifica se o elemento existe
                   modalOverlay.style.display = 'flex'; // Use 'flex' ou 'block'
                }
                // =======================================================

                try {
                    const response = await fetch(`api_pedidos.php?acao=detalhes&id=${pedidoId}`);
                    const res = await response.json();

                    if (res.sucesso) {
                        const pedido = res.dados;
                        modalTitle.textContent = `Detalhes do Pedido #${pedido.id}`;
                        
                        let itensHtml = '<h4>Itens do Pedido:</h4>';
                        if (pedido.itens && pedido.itens.length > 0) {
                            itensHtml += '<ul style="padding-left: 20px;">';
                            pedido.itens.forEach(item => {
                                const subtotal = item.quantidade * item.preco_unitario;
                                itensHtml += `<li>${item.quantidade}x ${item.produto_nome} - R$ ${parseFloat(subtotal).toFixed(2).replace('.',',')}</li>`;
                            });
                            itensHtml += '</ul>';
                        } else {
                            itensHtml += '<p>Nenhum item encontrado para este pedido.</p>';
                        }

                        modalBody.innerHTML = `
                            <p><strong>Cliente:</strong> ${pedido.cliente_nome || 'N/A'}</p>
                            <p><strong>Data:</strong> ${new Date(pedido.data_pedido).toLocaleString('pt-BR')}</p>
                            <p><strong>Valor Total:</strong> R$ ${parseFloat(pedido.valor_total).toFixed(2).replace('.',',')}</p>
                            <hr style="border-color: var(--border-dark);">
                            ${itensHtml}
                        `;
                    } else {
                        modalBody.innerHTML = `<p style="color: var(--danger);">Erro: ${res.erro || 'Resposta inválida da API.'}</p>`;
                    }
                } catch(error) {
                    modalBody.innerHTML = `<p style="color: var(--danger);">Erro de conexão ao buscar detalhes.</p>`;
                    console.error("Erro no fetch detalhes pedido:", error); // Adiciona log de erro
                }
            });
        });

        function fecharModal() {
             // =======================================================
             // ==== CORREÇÃO: Usar style.display para esconder ====
             if (modalOverlay) { // Verifica se o elemento existe
                 modalOverlay.style.display = 'none';
             }
             // =======================================================
        }

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', fecharModal);
        if (modalOverlay) modalOverlay.addEventListener('click', (e) => {
            // Fecha se clicar no fundo escuro (overlay)
            if (e.target === modalOverlay) {
                fecharModal();
            }
        });

        // Adiciona um log para verificar se o script chega até aqui
        console.log("Script pedidos.php carregado."); 

    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>