<?php
// (Este ficheiro está em /public/)

// Sobe um nível (../) para encontrar o bootstrap e auth
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// Sobe um nível (../) para encontrar os modelos
require_once __DIR__ . '/../models/CaixaSessao.php';
require_once __DIR__ . '/../models/Relatorio.php';
require_once __DIR__ . '/../models/Configuracao.php';

// --- Instancia os modelos ---
$sessaoModel = new CaixaSessao();
$relatorioModel = new Relatorio();
$configModel = new Configuracao();

// --- Pega dados de configuração e utilizador ---
$configuracoes = $configModel->carregarConfiguracoes();
$moeda = $configuracoes['moeda_simbolo'] ?? 'R$';
$id_utilizador_fechamento = $utilizadorLogado['id'];

$erro = null;
$resumo = null; 
$sistema_dinheiro = 0;
$sistema_cartao = 0;
$sistema_pix_outros = 0;

// --- 1. Verifica se o caixa ESTÁ ABERTO ---
$sessaoAberta = $sessaoModel->buscarSessaoAberta();
$id_sessao_ativa = $sessaoAberta['id'] ?? null;

if (!$id_sessao_ativa) {
    header("Location: ../index.php"); 
    exit;
}

try {
    // --- 2. LÓGICA DE PROCESSAMENTO (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $detalhes_sessao = $sessaoModel->buscarPorId($id_sessao_ativa);
        $resumo_vendas = $relatorioModel->gerarFechamentoSimples(
            $detalhes_sessao['data_abertura'], 
            date('Y-m-d H:i:s')
        );

        // (Lógica de cálculo de totais)
        $total_vendas_dinheiro = 0; $total_vendas_cartao = 0; $total_vendas_outros = 0; 
        if (!empty($resumo_vendas['detalhes_pagamento'])) {
            foreach ($resumo_vendas['detalhes_pagamento'] as $pgto) {
                $nomePgto = strtolower($pgto['forma_pagamento']);
                if ($nomePgto === 'dinheiro') { $total_vendas_dinheiro = (float) $pgto['valor_total']; } 
                elseif (strpos($nomePgto, 'cart') !== false) { $total_vendas_cartao += (float) $pgto['valor_total']; } 
                else { $total_vendas_outros += (float) $pgto['valor_total']; }
            }
        }
        $contado_dinheiro = (float) str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['contado_dinheiro']);
        $contado_cartao = (float) str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['contado_cartao']);
        $contado_outros = (float) str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['contado_pix']);
        $observacoes = $_POST['observacoes'] ?? '';
        $total_apurado_sistema = (float) $detalhes_sessao['valor_abertura'] + (float) $detalhes_sessao['total_suprimentos'] - (float) $detalhes_sessao['total_sangrias'] + (float) $resumo_vendas['total_geral']['faturamento_total'];
        $esperado_dinheiro = (float) $detalhes_sessao['valor_abertura'] + (float) $detalhes_sessao['total_suprimentos'] - (float) $detalhes_sessao['total_sangrias'] + $total_vendas_dinheiro;
        $diferenca = $contado_dinheiro - $esperado_dinheiro;
        $valores_fecho = [
            'total_apurado_sistema' => $total_apurado_sistema, 'contado_dinheiro' => $contado_dinheiro,
            'contado_cartao' => $contado_cartao, 'contado_outros' => $contado_outros,
            'diferenca' => $diferenca, 'observacoes' => $observacoes
        ];
        
        $sucesso = $sessaoModel->fechar($id_sessao_ativa, $id_utilizador_fechamento, $valores_fecho); 

        if ($sucesso) { 
            unset($_SESSION['id_sessao_ativa']);
            header("Location: ../index.php?fechado=sucesso"); 
            exit;
        } else { 
            throw new Exception("Ocorreu um erro ao atualizar o status da sessão. Já estava fechado?"); 
        }
    }

    // --- 3. LÓGICA GET (Carregar página) ---
    $detalhes_sessao = $sessaoModel->buscarPorId($id_sessao_ativa);
    $resumo_vendas = $relatorioModel->gerarFechamentoSimples($detalhes_sessao['data_abertura'], date('Y-m-d H:i:s'));
    $resumo = [
        'valor_abertura' => (float) $detalhes_sessao['valor_abertura'],
        'total_suprimentos' => (float) $detalhes_sessao['total_suprimentos'],
        'total_sangrias' => (float) $detalhes_sessao['total_sangrias'],
        'total_vendas' => (float) $resumo_vendas['total_geral']['faturamento_total'],
        'detalhes_pagamento' => $resumo_vendas['detalhes_pagamento'],
    ];
    $total_vendas_dinheiro = 0;
    if (!empty($resumo['detalhes_pagamento'])) {
        foreach ($resumo['detalhes_pagamento'] as $pgto) {
            if (strtolower($pgto['forma_pagamento']) === 'dinheiro') {
                $total_vendas_dinheiro = (float) $pgto['valor_total'];
                break;
            }
        }
    }
    $resumo['esperado_dinheiro'] = $resumo['valor_abertura'] + $resumo['total_suprimentos'] - $resumo['total_sangrias'] + $total_vendas_dinheiro;

} catch (Exception $e) {
    $erro = $e->getMessage();
}

// Funções Auxiliares
function formatarMoeda($valor, $simbolo) { return $simbolo . ' ' . number_format($valor ?? 0, 2, ',', '.'); }
function getValorPorPagamento($detalhes, $nomeProcurado) {
    $nomeProcurado = strtolower($nomeProcurado);
    if (empty($detalhes)) return 0;
    $total = 0;
    foreach ($detalhes as $pgto) {
        $nomePgto = strtolower($pgto['forma_pagamento']);
        if ($nomeProcurado === 'dinheiro' && $nomePgto === 'dinheiro') { return (float) $pgto['valor_total']; }
        if ($nomeProcurado === 'cartao' && strpos($nomePgto, 'cart') !== false) { $total += (float) $pgto['valor_total']; }
        if ($nomeProcurado === 'pix_outros' && $nomePgto !== 'dinheiro' && strpos($nomePgto, 'cart') === false) { $total += (float) $pgto['valor_total']; }
    }
    return $total;
}
$sistema_dinheiro = getValorPorPagamento($resumo['detalhes_pagamento'] ?? [], 'dinheiro');
$sistema_cartao = getValorPorPagamento($resumo['detalhes_pagamento'] ?? [], 'cartao');
$sistema_pix_outros = getValorPorPagamento($resumo['detalhes_pagamento'] ?? [], 'pix_outros');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fechar Caixa - Sistema Quiosque</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        /* ESTILOS ESPECÍFICOS DESTA PÁGINA */
        
        .fechamento-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
            align-items: start;
        }

        /* Painéis (Esquerda e Direita) */
        .painel-resumo, .painel-formulario {
            background-color: var(--card-bg-dark);
            border: 1px solid var(--border-dark);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }

        h2 { margin-top: 0; font-size: 1.2rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-dark); padding-bottom: 10px; }

        /* Linhas do Resumo */
        .resumo-grupo { margin-bottom: 20px; }
        .resumo-grupo h4 { margin: 0 0 10px 0; color: #aaa; font-size: 0.85rem; text-transform: uppercase; }
        
        .linha-valor {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed var(--border-dark);
            font-size: 0.95rem;
        }
        .linha-valor:last-child { border-bottom: none; }
        
        .valor-positivo { color: var(--success); }
        .valor-negativo { color: var(--danger); }

        /* Destaque Final */
        .resumo-final {
            background-color: rgba(79, 156, 249, 0.1);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            text-align: center;
        }
        .resumo-final span { display: block; font-size: 0.9rem; color: #aaa; text-transform: uppercase; margin-bottom: 5px; }
        .resumo-final strong { display: block; font-size: 1.8rem; color: var(--primary); }

        /* Formulário */
        .form-group-fecho { margin-bottom: 16px; }
        .form-group-fecho label { display: flex; justify-content: space-between; align-items: center; }
        .sistema-info { font-size: 0.8rem; color: #aaa; font-weight: normal; }
        
        @media (max-width: 900px) {
            .fechamento-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">FECHAR CAIXA</span>
            <div class="header-actions">
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema">
                    <span class="icon-sun">☀️</span><span class="icon-moon">🌙</span>
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        
        <?php if ($erro && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div style="background: var(--danger); color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <div class="fechamento-layout">

            <div class="painel-resumo">
                <h2>Resumo do Sistema</h2>

                <div class="resumo-grupo">
                    <h4>Fluxo de Caixa</h4>
                    <div class="linha-valor">
                        <span>Fundo de Troco (Abertura)</span>
                        <span><?php echo formatarMoeda($resumo['valor_abertura'] ?? 0, $moeda); ?></span>
                    </div>
                    <div class="linha-valor">
                        <span>(+) Suprimentos</span>
                        <span class="valor-positivo"><?php echo formatarMoeda($resumo['total_suprimentos'] ?? 0, $moeda); ?></span>
                    </div>
                    <div class="linha-valor">
                        <span>(-) Sangrias</span>
                        <span class="valor-negativo"><?php echo formatarMoeda($resumo['total_sangrias'] ?? 0, $moeda); ?></span>
                    </div>
                </div>

                <div class="resumo-grupo">
                    <h4>Vendas Registadas</h4>
                    <div class="linha-valor">
                        <span>Dinheiro</span>
                        <span class="valor-positivo"><?php echo formatarMoeda($sistema_dinheiro, $moeda); ?></span>
                    </div>
                    <div class="linha-valor">
                        <span>Cartão</span>
                        <span class="valor-positivo"><?php echo formatarMoeda($sistema_cartao, $moeda); ?></span>
                    </div>
                    <div class="linha-valor">
                        <span>Pix / Outros</span>
                        <span class="valor-positivo"><?php echo formatarMoeda($sistema_pix_outros, $moeda); ?></span>
                    </div>
                </div>

                <div class="resumo-final">
                    <span>Esperado em Dinheiro (Gaveta)</span>
                    <strong><?php echo formatarMoeda($resumo['esperado_dinheiro'] ?? 0, $moeda); ?></strong>
                </div>
            </div>

            <div class="painel-formulario">
                <h2>Contagem e Fecho</h2>
                
                <form method="POST" action="fechar_caixa.php" onsubmit="return confirm('Tem a certeza que deseja fechar o caixa? Esta ação não pode ser desfeita.');">
                    
                    <div class="form-group-fecho">
                        <label for="contado_dinheiro">
                            Dinheiro em Gaveta
                            <span class="sistema-info">Sistema: <?php echo formatarMoeda($resumo['esperado_dinheiro'] ?? 0, $moeda); ?></span>
                        </label>
                        <input type="text" id="contado_dinheiro" name="contado_dinheiro" class="input-mask-moeda" value="0,00" required autofocus>
                    </div>

                    <div class="form-group-fecho">
                        <label for="contado_cartao">
                            Cartão (Maquininha)
                            <span class="sistema-info">Sistema: <?php echo formatarMoeda($sistema_cartao, $moeda); ?></span>
                        </label>
                        <input type="text" id="contado_cartao" name="contado_cartao" class="input-mask-moeda" value="<?php echo number_format($sistema_cartao, 2, ',', '.'); ?>" required>
                    </div>

                    <div class="form-group-fecho">
                        <label for="contado_pix">
                            Pix / Outros
                            <span class="sistema-info">Sistema: <?php echo formatarMoeda($sistema_pix_outros, $moeda); ?></span>
                        </label>
                        <input type="text" id="contado_pix" name="contado_pix" class="input-mask-moeda" value="<?php echo number_format($sistema_pix_outros, 2, ',', '.'); ?>" required>
                    </div>

                    <div class="form-group-fecho">
                        <label for="observacoes">Observações / Justificativa de Quebra</label>
                        <textarea id="observacoes" name="observacoes" rows="3" placeholder="Opcional..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger" style="width: 100%; height: 50px; font-size: 1.1em;">
                        🔒 Confirmar Fechamento
                    </button>

                </form>
            </div>

        </div>
    </div>

    <script src="../assets/js/theme.js" defer></script>
    <script>
        // Script de máscara de moeda
        document.addEventListener('DOMContentLoaded', () => {
            const inputsMoeda = document.querySelectorAll('.input-mask-moeda');
            
            const formatarMoedaInput = (input) => {
                if (!input) return;
                let valor = input.value.replace(/\D/g, '');
                valor = (parseInt(valor, 10) || 0) / 100;
                valor = valor.toFixed(2) + '';
                valor = valor.replace('.', ',');
                
                let parts = valor.split(',');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                
                input.value = parts.join(',');
            };

            inputsMoeda.forEach(input => {
                input.addEventListener('keyup', (e) => {
                    formatarMoedaInput(e.target);
                });
                // Formata valor inicial
                formatarMoedaInput(input);
                
                // Seleciona tudo ao clicar para facilitar digitação
                input.addEventListener('focus', function() {
                    this.select();
                });
            });
        });
    </script>
</body>
</html>