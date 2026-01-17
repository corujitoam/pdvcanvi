<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// Somente supervisor/admin? Se não houver regra, permite logged-in.

$movs = [];
$sessoes = [];
$erro = null;

try {
    $db = pdo();

    // Movimentacoes (entrada/sangria)
    $sqlMov = "
        SELECT
            cm.id,
            cm.data_hora,
            cm.tipo,
            cm.valor,
            cm.motivo,
            cm.id_sessao,
            u.nome AS usuario
        FROM caixa_movimentacoes cm
        LEFT JOIN utilizadores u ON u.id = cm.id_utilizador
        ORDER BY cm.data_hora DESC
        LIMIT 200
    ";
    $movs = $db->query($sqlMov)->fetchAll();

    // Sessoes (abertura/fechamento)
    $sqlSes = "
        SELECT
            cs.id,
            cs.data_abertura,
            cs.valor_inicial,
            uab.nome AS aberto_por,
            cs.data_fechamento,
            cs.valor_final_sistema,
            cs.valor_final_informado,
            ufe.nome AS fechado_por,
            cs.diferenca
        FROM caixa_sessoes cs
        LEFT JOIN utilizadores uab ON uab.id = cs.id_utilizador_abertura
        LEFT JOIN utilizadores ufe ON ufe.id = cs.id_utilizador_fechamento
        ORDER BY cs.id DESC
        LIMIT 50
    ";
    $sessoes = $db->query($sqlSes)->fetchAll();

} catch (Throwable $e) {
    $erro = $e->getMessage();
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Auditoria</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    .wrap { max-width: 1100px; margin: 0 auto; padding: 20px; }
    .card { background: var(--card); border: 1px solid var(--border-dark); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 900px) { .grid2 { grid-template-columns: 1fr; } }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 10px; border-bottom: 1px solid var(--border-dark); text-align: left; vertical-align: top; }
    .pill { display:inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .pill.in { background: rgba(34,197,94,.15); color: var(--success); border: 1px solid rgba(34,197,94,.3); }
    .pill.out { background: rgba(220,38,38,.15); color: var(--danger); border: 1px solid rgba(220,38,38,.3); }
    .muted { color: var(--text-secondary); }
    .header-actions { display:flex; gap:10px; align-items:center; }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-inner header-inner--standard">
      <div class="header-start">
        <a href="pdv.php" class="btn-header-voltar">Voltar</a>
      </div>
      <span class="brand">AUDITORIA</span>
      <div class="header-actions">
        <button class="theme-toggle" id="theme-toggle"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
      </div>
    </div>
  </header>

  <div class="wrap">
    <?php if ($erro): ?>
      <div class="card" style="border-color: var(--danger);">
        <strong>Erro ao carregar auditoria:</strong>
        <div class="muted"><?php echo h($erro); ?></div>
      </div>
    <?php endif; ?>

    <div class="grid2">
      <div class="card">
        <h3 style="margin:0 0 10px 0;">Movimentações (Entrada / Sangria)</h3>
        <div class="muted" style="margin-bottom:10px;">Últimas 200 movimentações registradas.</div>
        <div style="overflow:auto;">
          <table class="table">
            <thead>
              <tr>
                <th>Data/Hora</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Usuário</th>
                <th>Motivo</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($movs)): ?>
                <tr><td colspan="5" class="muted">Sem registros.</td></tr>
              <?php else: foreach ($movs as $m): ?>
                <tr>
                  <td><?php echo h($m['data_hora']); ?></td>
                  <td>
                    <?php if (($m['tipo'] ?? '') === 'ENTRADA'): ?>
                      <span class="pill in">ENTRADA</span>
                    <?php else: ?>
                      <span class="pill out">SAÍDA</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo h(number_format((float)$m['valor'], 2, ',', '.')); ?></td>
                  <td><?php echo h($m['usuario'] ?: '—'); ?></td>
                  <td><?php echo h($m['motivo'] ?: '—'); ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="margin:0 0 10px 0;">Sessões de Caixa (Abertura / Fechamento)</h3>
        <div class="muted" style="margin-bottom:10px;">Últimas 50 sessões.</div>
        <div style="overflow:auto;">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Abertura</th>
                <th>Fechamento</th>
                <th>Diferença</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($sessoes)): ?>
                <tr><td colspan="4" class="muted">Sem sessões.</td></tr>
              <?php else: foreach ($sessoes as $s): ?>
                <tr>
                  <td>#<?php echo (int)$s['id']; ?></td>
                  <td>
                    <div><strong><?php echo h($s['aberto_por'] ?: '—'); ?></strong></div>
                    <div class="muted"><?php echo h($s['data_abertura']); ?></div>
                    <div class="muted">Inicial: <?php echo h(number_format((float)$s['valor_inicial'], 2, ',', '.')); ?></div>
                  </td>
                  <td>
                    <div><strong><?php echo h($s['fechado_por'] ?: '—'); ?></strong></div>
                    <div class="muted"><?php echo h($s['data_fechamento'] ?: '—'); ?></div>
                    <div class="muted">Sistema: <?php echo h(number_format((float)$s['valor_final_sistema'], 2, ',', '.')); ?></div>
                    <div class="muted">Informado: <?php echo h(number_format((float)$s['valor_final_informado'], 2, ',', '.')); ?></div>
                  </td>
                  <td>
                    <?php $dif = (float)($s['diferenca'] ?? 0); ?>
                    <div style="font-weight:700; color: <?php echo $dif == 0 ? 'var(--success)' : 'var(--warn)'; ?>;">
                      <?php echo h(number_format($dif, 2, ',', '.')); ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 10px 0;">Observação</h3>
      <div class="muted">As movimentações (entrada/sangria) e o fechamento do caixa já são registrados com o usuário logado. Esta página apenas exibe os registros para conferência.</div>
    </div>
  </div>

  <script src="../assets/js/theme.js" defer></script>
</body>
</html>
