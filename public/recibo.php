<?php
require_once __DIR__ . '/../bootstrap.php';

// Configuração (nome do sistema e moeda)
$nomeSistema = 'Sistema Quiosque';
$moeda = 'R$';
if (class_exists('Configuracao')) {
    try {
        $conf = (new Configuracao())->carregarConfiguracoes();
        $nomeSistema = $conf['nome_sistema'] ?? $nomeSistema;
        $moeda = $conf['moeda_simbolo'] ?? $moeda;
    } catch (Throwable $e) {
        // ignora e segue com defaults
    }
}

$autoPrint = ((int)($_GET['autoprint'] ?? 0) === 1);

$pedidoId = (int)($_GET['id'] ?? 0);
if ($pedidoId <= 0) {
    die('ID do pedido inválido.');
}

$pedidoModel = new Pedido();
$pedido = $pedidoModel->buscarPedidoComItens($pedidoId);

if (!$pedido) {
    die('Pedido não encontrado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($nomeSistema); ?> - Recibo #<?php echo (int)$pedido['id']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }

        /* Estilos para visualização no ECRÃ (pop-up) */
        body {
            padding: 10px;
        }

        .recibo {
            text-align: center;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .recibo h1 { font-size: 16px; margin-bottom: 10px; }
        .recibo p { font-size: 12px; line-height: 1.4; }
        .recibo hr { border: 0; border-top: 1px dashed #000; margin: 10px 0; }
        .itens-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .itens-table th, .itens-table td { padding: 2px 0; }
        .itens-table th { text-align: left; }
        .itens-table .col-qtd { width: 15%; }
        .itens-table .col-valor { width: 25%; text-align: right; }
        .total { font-size: 14px; font-weight: bold; text-align: right; margin-top: 10px; }

        /* --- ESTILOS APLICADOS APENAS NA IMPRESSÃO --- */
        @media print {
            body {
                padding: 10mm 0;
            }
            .recibo {
                /* ALTERAÇÃO AQUI: Usando 'ch' em vez de 'mm' para um layout de colunas */
                width: 48ch; /* Define a largura para 48 caracteres */
                margin: 0 auto; /* Mantém o recibo centrado na página A4 */
            }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(window.close, 500);">
    <div class="recibo">
        <h1><?php echo htmlspecialchars($nomeSistema); ?></h1>
        <p>Pedido: #<?php echo htmlspecialchars($pedido['id']); ?></p>
        <p>Cliente: <?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
        <p>Data: <?php echo date('d/m/Y H:i:s', strtotime($pedido['data_pedido'])); ?></p>
        <hr>
        <table class="itens-table">
            <thead>
                <tr>
                    <th class="col-qtd">Qtd</th>
                    <th>Produto</th>
                    <th class="col-valor">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedido['itens'] as $item): ?>
                    <tr>
                        <td><?php echo $item['quantidade']; ?></td>
                        <td><?php echo htmlspecialchars($item['produto_nome']); ?></td>
                        <td class="col-valor"><?php echo htmlspecialchars($moeda); ?> <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
        <p class="total">Total: <?php echo htmlspecialchars($moeda); ?> <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></p>
        <p>Pagamento: <?php echo htmlspecialchars($pedido['forma_pagamento']); ?></p>
        <hr>
        <p>Obrigado pela preferência!</p>
    </div>

    <?php if ($autoPrint): ?>
    <script>
      // Se abrir direto (janela), imprime; se estiver num iframe (impressao sem popup), quem imprime é o iframe.
      window.addEventListener('load', function () {
        try {
          if (window.self === window.top) window.print();
        } catch (e) {
          try { window.print(); } catch (err) {}
        }
      });
    </script>
    <?php endif; ?>
</body>
</html>