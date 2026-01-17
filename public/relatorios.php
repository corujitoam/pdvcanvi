<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

$configModel = new Configuracao();
$configuracoes = $configModel->carregarConfiguracoes();
$moeda = $configuracoes['moeda_simbolo'] ?? 'R$';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios - Sistema Quiosque</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
           CSS LOCAL - Aplica scroll APENAS nesta página
           sem quebrar o CSS global do sistema
           ============================================================ */
        
        /* Garante que o container ocupe o espaço disponível mas não vaze */
        .container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 80px); /* Altura total menos o Header */
            overflow: hidden; /* Container pai não rola */
            padding-bottom: 0 !important;
        }

        /* O cartão branco/cinza do relatório */
        .relatorios-painel {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            height: 100%;
            overflow: hidden; /* Painel não rola, quem rola é o filho */
            padding: 24px;
            background-color: var(--card-bg-dark); /* Garante a cor do fundo */
        }

        /* Área dos Filtros (Fixa no topo) */
        .filtros-form {
            flex-shrink: 0; /* Não encolhe */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Ajustes de Inputs para ficarem alinhados */
        .filtros-form label { margin-bottom: 8px; font-size: 0.85rem; color: #aaa; }
        .filtros-form input, .filtros-form select, .filtros-form button {
            height: 45px; 
            width: 100%;
            box-sizing: border-box;
        }

        /* STATUS (Fixo) */
        #status-relatorio {
            flex-shrink: 0;
            margin-bottom: 20px;
            text-align: center;
            color: #aaa;
        }

        /* --- AQUI ESTÁ A MÁGICA DO SCROLL --- */
        /* Esta div envolve os cards e a tabela */
        .area-resultado {
            flex-grow: 1;          /* Ocupa todo o espaço restante */
            overflow-y: auto;      /* SCROLL VERTICAL AQUI */
            overflow-x: hidden;    /* Evita scroll horizontal na janela */
            padding-right: 10px;   /* Espaço para a barra de rolagem */
            min-height: 0;         /* Fix para Flexbox */
            display: none;         /* Começa escondido */
        }

        /* Estilização da Scrollbar interna */
        .area-resultado::-webkit-scrollbar { width: 8px; }
        .area-resultado::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
        .area-resultado::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

        /* Cards de Métricas */
        .resultados-resumo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .resumo-metric {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .resumo-metric-title { font-size: 0.8rem; color: #aaa; text-transform: uppercase; margin-bottom: 10px; }
        .resumo-metric-value { font-size: 1.8rem; font-weight: 700; color: var(--primary); }

        /* Tabela Responsiva */
        .table-responsive {
            width: 100%;
            overflow-x: auto; /* Scroll horizontal APENAS na tabela */
            padding-bottom: 10px;
        }
        .tabela-relatorio {
            width: 100%;
            min-width: 800px; /* Força largura mínima */
            border-collapse: collapse;
            white-space: nowrap;
        }
        .tabela-relatorio th {
            background-color: #1e293b;
            padding: 16px;
            text-align: left;
            position: sticky;
            top: 0; /* Cabeçalho da tabela não fixa aqui pois está dentro do scroll da area-resultado */
        }
        .tabela-relatorio td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        /* Cores de status */
        .status-ok { color: var(--success); font-weight: bold; }
        .status-bad { color: var(--danger); font-weight: bold; }

    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">RELATÓRIOS E INDICADORES</span>
            <div class="header-actions">
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="relatorios-painel">
            
            <form id="filtros-form" class="filtros-form">
                <div style="grid-column: span 2;">
                    <label for="tipo-relatorio">TIPO DE RELATÓRIO</label>
                    <select id="tipo-relatorio" name="acao" required>
                        <option value="vendas_por_periodo">Vendas Detalhadas (Lista de Pedidos)</option>
                        <option value="fechamento_simples">Resumo Financeiro (Por Pagamento)</option>
                        <option value="fechamento_detalhado">Ranking de Produtos Vendidos</option>
                        <option value="relatorio_caixa">Histórico de Fechamento de Caixa</option>
                    </select>
                </div>

                <div>
                    <label for="data-inicio">DATA DE INÍCIO</label>
                    <input type="date" id="data-inicio" name="data_inicio" required>
                </div>
                <div>
                    <label for="data-fim">DATA DE FIM</label>
                    <input type="date" id="data-fim" name="data_fim" required>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">Gerar Relatório</button>
                </div>
            </form>

            <div id="status-relatorio">
                <p>Selecione os filtros acima e clique em "Gerar Relatório".</p>
            </div>
            
            <div id="resultado-vendas-periodo" class="area-resultado">
                <h3 style="margin-bottom:20px; color:var(--text-primary);">Resumo do Período</h3>
                <div class="resultados-resumo">
                    <div class="resumo-metric"><div class="resumo-metric-title">Faturamento Total</div><div class="resumo-metric-value" id="resumo-faturamento">--</div></div>
                    <div class="resumo-metric"><div class="resumo-metric-title">Pedidos</div><div class="resumo-metric-value" id="resumo-pedidos">0</div></div>
                    <div class="resumo-metric"><div class="resumo-metric-title">Ticket Médio</div><div class="resumo-metric-value" id="resumo-ticket-medio">--</div></div>
                </div>
                <h3 style="margin-bottom:10px; color:var(--text-primary);">Lista de Pedidos</h3>
                <div class="table-responsive">
                    <table class="tabela-relatorio">
                        <thead><tr><th>ID</th><th>Cliente</th><th>Data/Hora</th><th>Pagamento</th><th>Valor</th></tr></thead>
                        <tbody id="tabela-corpo-pedidos"></tbody>
                    </table>
                </div>
            </div>

            <div id="resultado-fechamento-simples" class="area-resultado">
                <h3 style="margin-bottom:20px; color:var(--text-primary);">Resumo Financeiro</h3>
                <div class="resultados-resumo">
                     <div class="resumo-metric"><div class="resumo-metric-title">Total Vendido</div><div class="resumo-metric-value" id="resumo-simples-faturamento">--</div></div>
                    <div class="resumo-metric"><div class="resumo-metric-title">Total Pedidos</div><div class="resumo-metric-value" id="resumo-simples-pedidos">0</div></div>
                </div>
                <h3 style="margin-bottom:10px; color:var(--text-primary);">Detalhamento por Forma de Pagamento</h3>
                <div class="table-responsive">
                    <table class="tabela-relatorio">
                        <thead><tr><th>Forma de Pagamento</th><th>Qtd. Pedidos</th><th>Valor Total</th></tr></thead>
                        <tbody id="tabela-corpo-simples"></tbody>
                    </table>
                </div>
            </div>

            <div id="resultado-fechamento-detalhado" class="area-resultado">
                <h3 style="margin-bottom:20px; color:var(--text-primary);">Ranking de Produtos Vendidos</h3>
                <div class="table-responsive">
                    <table class="tabela-relatorio">
                        <thead><tr><th>ID</th><th>Produto</th><th>Qtd. Vendida</th><th>Total Arrecadado</th></tr></thead>
                        <tbody id="tabela-corpo-detalhado"></tbody>
                    </table>
                </div>
            </div>

            <div id="resultado-relatorio-caixa" class="area-resultado">
                <h3 style="margin-bottom:20px; color:var(--text-primary);">Histórico de Fechamentos de Caixa</h3>
                <div class="table-responsive">
                    <table class="tabela-relatorio">
                        <thead>
                            <tr>
                                <th>ID Sessão</th>
                                <th>Operador</th>
                                <th>Abertura</th>
                                <th>Fechamento</th>
                                <th>Fundo Inicial</th>
                                <th>Apurado Sistema</th>
                                <th>Quebra (Diferença)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-corpo-caixa"></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    
    <div id="toast"></div>

    <script>
    const MOEDA = '<?php echo $moeda; ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filtros-form');
        const statusRelatorio = document.getElementById('status-relatorio');
        const toast = document.getElementById('toast');
        const tipoRelatorioSelect = document.getElementById('tipo-relatorio');
        
        // Áreas
        const resVendas = document.getElementById('resultado-vendas-periodo');
        const resSimples = document.getElementById('resultado-fechamento-simples');
        const resDetalhado = document.getElementById('resultado-fechamento-detalhado');
        const resCaixa = document.getElementById('resultado-relatorio-caixa');

        // Datas
        const hoje = new Date().toISOString().split('T')[0];
        document.getElementById('data-inicio').value = hoje;
        document.getElementById('data-fim').value = hoje;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const dataInicio = document.getElementById('data-inicio').value;
            const dataFim = document.getElementById('data-fim').value;
            const acao = tipoRelatorioSelect.value;

            statusRelatorio.style.display = 'block';
            statusRelatorio.innerHTML = '<p>A processar dados...</p>';
            
            // Esconde todas as áreas antes de carregar
            resVendas.style.display = 'none';
            resSimples.style.display = 'none';
            resDetalhado.style.display = 'none';
            resCaixa.style.display = 'none';

            try {
                const url = `api_relatorios.php?acao=${acao}&data_inicio=${dataInicio}&data_fim=${dataFim}`;
                const response = await fetch(url);
                const res = await response.json();

                if (res.sucesso) {
                    statusRelatorio.style.display = 'none';
                    
                    // Exibe a área correta baseada na ação
                    switch (acao) {
                        case 'vendas_por_periodo': 
                            exibirRelatorioVendas(res.dados); 
                            resVendas.style.display = 'block'; // AQUI MOSTRA O DIV COM SCROLL
                            break;
                        case 'fechamento_simples': 
                            exibirFechamentoSimples(res.dados); 
                            resSimples.style.display = 'block';
                            break;
                        case 'fechamento_detalhado': 
                            exibirFechamentoDetalhado(res.dados); 
                            resDetalhado.style.display = 'block';
                            break;
                        case 'relatorio_caixa': 
                            exibirRelatorioCaixa(res.dados); 
                            resCaixa.style.display = 'block';
                            break;
                    }
                } else {
                    showToast(res.erro, true);
                    statusRelatorio.innerHTML = `<p style="color:#ef4444;">Erro: ${res.erro}</p>`;
                }
            } catch (error) {
                showToast('Erro de conexão.', true);
                statusRelatorio.innerHTML = '<p style="color:#ef4444;">Erro de conexão.</p>';
            }
        });

        function formatarMoeda(v) { return parseFloat(v || 0).toFixed(2).replace('.', ','); }
        function formatarData(d) { if(!d) return '-'; return new Date(d).toLocaleString('pt-BR'); }

        function exibirRelatorioVendas(dados) {
            document.getElementById('resumo-faturamento').textContent = `${MOEDA} ${formatarMoeda(dados.resumo.faturamentoTotal)}`;
            document.getElementById('resumo-pedidos').textContent = dados.resumo.totalPedidos;
            document.getElementById('resumo-ticket-medio').textContent = `${MOEDA} ${formatarMoeda(dados.resumo.ticketMedio)}`;
            const tbody = document.getElementById('tabela-corpo-pedidos');
            tbody.innerHTML = '';
            if (dados.pedidos.length > 0) {
                dados.pedidos.forEach(p => {
                    tbody.innerHTML += `<tr><td>#${p.id}</td><td>${p.cliente_nome || 'Consumidor'}</td><td>${formatarData(p.data_pedido)}</td><td>${p.forma_pagamento}</td><td style="color:var(--primary)">${MOEDA} ${formatarMoeda(p.valor_total)}</td></tr>`;
                });
            } else { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px">Sem dados.</td></tr>'; }
        }

        function exibirFechamentoSimples(dados) {
            document.getElementById('resumo-simples-faturamento').textContent = `${MOEDA} ${formatarMoeda(dados.total_geral.faturamento_total)}`;
            document.getElementById('resumo-simples-pedidos').textContent = dados.total_geral.total_pedidos || 0;
            const tbody = document.getElementById('tabela-corpo-simples');
            tbody.innerHTML = '';
            if (dados.detalhes_pagamento.length > 0) {
                dados.detalhes_pagamento.forEach(p => {
                    tbody.innerHTML += `<tr><td>${p.forma_pagamento}</td><td>${p.quantidade_pedidos}</td><td style="color:var(--primary)">${MOEDA} ${formatarMoeda(p.valor_total)}</td></tr>`;
                });
            } else { tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:20px">Sem dados.</td></tr>'; }
        }

        function exibirFechamentoDetalhado(dados) {
            const tbody = document.getElementById('tabela-corpo-detalhado');
            tbody.innerHTML = '';
            if (dados.produtos_vendidos && dados.produtos_vendidos.length > 0) {
                dados.produtos_vendidos.forEach(p => {
                    tbody.innerHTML += `<tr><td>${p.produto_id}</td><td>${p.produto_nome}</td><td>${p.total_quantidade}</td><td style="color:var(--primary)">${MOEDA} ${formatarMoeda(p.valor_total_vendido)}</td></tr>`;
                });
            } else { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px">Sem dados.</td></tr>'; }
        }

        function exibirRelatorioCaixa(dados) {
            const tbody = document.getElementById('tabela-corpo-caixa');
            tbody.innerHTML = '';
            if (dados.sessoes_caixa && dados.sessoes_caixa.length > 0) {
                dados.sessoes_caixa.forEach(c => {
                    let corDiferenca = parseFloat(c.diferenca) >= 0 ? 'status-ok' : 'status-bad';
                    let statusLabel = c.status === 'ABERTO' ? '<span style="color:var(--warn)">ABERTO</span>' : 'FECHADO';
                    
                    tbody.innerHTML += `
                        <tr>
                            <td>#${c.id}</td>
                            <td>${c.operador_abertura || 'Admin'}</td>
                            <td>${formatarData(c.data_abertura)}</td>
                            <td>${formatarData(c.data_fechamento)}</td>
                            <td>${MOEDA} ${formatarMoeda(c.valor_abertura)}</td>
                            <td>${MOEDA} ${formatarMoeda(c.total_apurado_sistema)}</td>
                            <td class="${corDiferenca}">${MOEDA} ${formatarMoeda(c.diferenca)}</td>
                            <td>${statusLabel}</td>
                        </tr>
                    `;
                });
            } else { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px">Nenhuma sessão encontrada.</td></tr>'; }
        }

        function showToast(msg, erro) {
            if(toast) { toast.textContent = msg; toast.className = erro ? 'show erro' : 'show sucesso'; setTimeout(() => toast.className = '', 3000); }
        }
    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>