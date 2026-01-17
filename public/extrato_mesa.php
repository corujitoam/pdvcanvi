<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

$mesaId = (int)($_GET['id'] ?? 0);
if ($mesaId <= 0) {
    http_response_code(400);
    die("ID da mesa nè´™o fornecido.");
}

try {
    // Configuraè´Æè´ÿes de impressè´™o (cabeè´Æalho/rodapè´±)
    $configuracoes = [];
    if (class_exists('Configuracao')) {
        $configModel = new Configuracao();
        if (method_exists($configModel, 'carregarConfiguracoes')) {
            $configuracoes = $configModel->carregarConfiguracoes();
        }
    }

    // Mesa e itens
    $mesaModel = new Mesa();

    // Buscar mesa (compatè´øvel com diferentes versè´ÿes)
    if (method_exists($mesaModel, 'buscarPorId')) {
        $mesa = $mesaModel->buscarPorId($mesaId);
    } elseif (method_exists($mesaModel, 'buscar')) {
        $mesa = $mesaModel->buscar($mesaId);
    } else {
        throw new Exception("Mè´±todo de busca da mesa nè´™o encontrado no model Mesa.");
    }

    // Buscar itens da mesa (compatè´øvel com diferentes versè´ÿes)
    if (method_exists($mesaModel, 'listarItens')) {
        $itens = $mesaModel->listarItens($mesaId);
    } elseif (method_exists($mesaModel, 'buscarItensDaMesa')) {
        $itens = $mesaModel->buscarItensDaMesa($mesaId);
    } elseif (method_exists($mesaModel, 'listarItensDaMesa')) {
        $itens = $mesaModel->listarItensDaMesa($mesaId);
    } else {
        throw new Exception("Mè´±todo de listagem de itens nè´™o encontrado no model Mesa.");
    }

    if (!$mesa) {
        http_response_code(404);
        die("Mesa nè´™o encontrada.");
    }

    $subtotal = 0.0;

} catch (Throwable $e) {
    http_response_code(500);
    // Mensagem enxuta (sem vazar detalhes sensè´øveis)
    die("Erro ao gerar extrato da mesa. (" . htmlspecialchars($e->getMessage()) . ")");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Extrato da Mesa <?php echo (int)($mesa['numero'] ?? $mesaId); ?></title>
  <style>
    body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 10px; }
    h1, h2 { text-align: center; margin: 5px 0; }
    h1 { font-size: 1.2em; }
    h2 { font-size: 1em; font-weight: normal; }
    hr { border: none; border-top: 1px dashed #000; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 2px 0; vertical-align: top; }
    .col-qtd { width: 15%; text-align: center; }
    .col-total { width: 25%; text-align: right; }
    .total-geral { font-weight: bold; font-size: 1.1em; }
    .footer { text-align: center; margin-top: 10px; font-size: 0.9em; }
    .muted { text-align:center; margin: 8px 0; font-size: 0.95em; }
    @media print {
      body { margin: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>
  <h1><?php echo htmlspecialchars($configuracoes['impressao_cabecalho'] ?? 'Extrato de Consumo'); ?></h1>
  <h2><?php echo date('d/m/Y H:i:s'); ?></h2>
  <hr>
  <h2>Mesa: <?php echo htmlspecialchars((string)($mesa['numero'] ?? $mesaId)); ?></h2>
  <?php if (!empty($mesa['descricao'])): ?>
    <h2>Cliente: <?php echo htmlspecialchars((string)$mesa['descricao']); ?></h2>
  <?php endif; ?>
  <hr>

  <?php if (empty($itens)): ?>
    <div class="muted">Nenhum item lanè´Æado nesta mesa.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th class="col-qtd">Qtd</th>
          <th>Produto</th>
          <th class="col-total">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($itens as $item):
          $qtd = (float)($item['quantidade'] ?? 0);
          $preco = (float)($item['preco'] ?? 0);
          $nome = (string)($item['nome'] ?? '');
          $totalItem = $preco * $qtd;
          $subtotal += $totalItem;
        ?>
          <tr>
            <td class="col-qtd"><?php echo (int)$qtd; ?></td>
            <td><?php echo htmlspecialchars($nome); ?></td>
            <td class="col-total"><?php echo number_format($totalItem, 2, ',', '.'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <hr>
    <table>
      <tr class="total-geral">
        <td>Subtotal:</td>
        <td class="col-total"><?php echo number_format($subtotal, 2, ',', '.'); ?></td>
      </tr>
    </table>
  <?php endif; ?>

  <hr>
  <div class="footer">
    <?php echo htmlspecialchars($configuracoes['impressao_rodape'] ?? 'Obrigado!'); ?>
  </div>

  <script>
    // Imprime automaticamente ao abrir.
    window.onload = function() {
      try { window.print(); } catch (e) {}
    };
  </script>
</body>
</html>
