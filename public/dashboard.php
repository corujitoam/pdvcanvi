<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

$configModel = new Configuracao();
$configuracoes = $configModel->carregarConfiguracoes();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($configuracoes['nome_sistema'] ?? 'Sistema Quiosque'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
             <div class="header-start">
                 <a href="../index.php" class="btn-header-voltar" title="Voltar ao Menu">Voltar</a>
            </div>
            <span class="brand"><?php echo htmlspecialchars($configuracoes['nome_sistema'] ?? 'Sistema Quiosque'); ?></span>
            <div class="header-actions">
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema">
                    <span class="icon-sun">☀️</span><span class="icon-moon">🌙</span>
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Dashboard Analítico</h1>

        <div class="dashboard-layout">
            <div class="metric-card"><div class="metric-card-title">Faturamento do Dia</div><div class="metric-card-value" id="faturamento-hoje">...</div></div>
            <div class="metric-card"><div class="metric-card-title">Pedidos Hoje</div><div class="metric-card-value" id="pedidos-hoje">...</div></div>
            <div class="metric-card"><div class="metric-card-title">Novos Clientes Hoje</div><div class="metric-card-value" id="clientes-hoje">...</div></div>
            
            <div class="chart-card">
                <h3>Vendas nos Últimos 7 Dias</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="graficoVendas"></canvas>
                </div>
            </div>
            <div class="chart-card" id="card-produtos-vendidos">
                <h3>Top 5 Produtos Mais Vendidos</h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="graficoProdutos"></canvas>
                </div>
            </div>

            <div class="list-card" id="card-ultimas-vendas">
                <h3>📈 Atividade Recente</h3>
                <ul id="lista-ultimas-vendas"><li>A carregar...</li></ul>
            </div>
            <div class="list-card" id="card-estoque-baixo">
                <h3>⚠️ Alerta de Estoque Baixo</h3>
                <ul id="lista-estoque-baixo"><li>A carregar...</li></ul>
            </div>
        </div>
    </div>
    
    <script>
    const MOEDA = '<?php echo $configuracoes['moeda_simbolo'] ?? 'R$'; ?>';

    // VARIÁVEIS GLOBAIS PARA OS GRÁFICOS (Para podermos destruir antes de recriar)
    let chartVendasInstance = null;
    let chartProdutosInstance = null;

    document.addEventListener('DOMContentLoaded', async function() {
        
        function formatarMoeda(v) { 
            return `${MOEDA} ${parseFloat(v).toFixed(2).replace('.', ',')}`; 
        }

        async function fetchData(action) {
            try {
                const response = await fetch(`api_dashboard.php?acao=${action}`);
                if (!response.ok) throw new Error('Erro de rede');
                const result = await response.json();
                if (!result.sucesso) throw new Error(result.erro || 'Erro desconhecido na API');
                return result.dados;
            } catch (error) {
                console.error("Erro no fetch:", error);
                return null;
            }
        }

        async function carregarDashboard() {
            console.log("A carregar dashboard...");
            
            const d = await fetchData('carregar_dados');
            
            if (!d) {
                document.querySelector('.dashboard-layout').innerHTML = '<p style="padding:20px; color:red;">Erro ao carregar dados.</p>';
                return;
            }

            // 1. Preencher Métricas
            if (d.totais) {
                document.getElementById('faturamento-hoje').textContent = formatarMoeda(d.totais.faturamento || 0);
                document.getElementById('pedidos-hoje').textContent = d.totais.pedidos || 0;
                document.getElementById('clientes-hoje').textContent = d.totais.novos_clientes || 0;
            }

            // 2. Listas
            const listaVendas = document.getElementById('lista-ultimas-vendas');
            if (listaVendas) {
                listaVendas.innerHTML = (d.atividade_recente && d.atividade_recente.length > 0)
                    ? d.atividade_recente.map(v => `
                        <li style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between;">
                            <span>#${v.id} - ${v.cliente || 'Consumidor'}</span> 
                            <span style="color:var(--success); font-weight:bold;">${formatarMoeda(v.valor_total)}</span>
                        </li>`).join('')
                    : '<li style="padding:10px; color:#aaa; text-align:center;">Sem vendas hoje.</li>';
            }

            const listaEstoque = document.getElementById('lista-estoque-baixo');
            if (listaEstoque) {
                listaEstoque.innerHTML = (d.estoque_baixo && d.estoque_baixo.length > 0)
                    ? d.estoque_baixo.map(p => `
                        <li style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between;">
                            <span>${p.nome}</span> 
                            <span style="color:var(--danger); font-weight:bold;">${p.estoque} un.</span>
                        </li>`).join('')
                    : '<li style="padding:10px; color:var(--success); text-align:center;">Estoque OK!</li>';
            }

            // 3. GRÁFICO DE BARRAS (Vendas) - COM PROTEÇÃO CONTRA CRESCIMENTO INFINITO
            const ctxVendas = document.getElementById('graficoVendas').getContext('2d');
            
            // SE JÁ EXISTE, DESTRÓI
            if (chartVendasInstance) {
                chartVendasInstance.destroy();
            }

            chartVendasInstance = new Chart(ctxVendas, {
                type: 'bar',
                data: {
                    labels: d.grafico_vendas.map(item => {
                        const datePart = item.dia || item.data; 
                        if(!datePart) return '-';
                        const [ano, mes, dia] = datePart.split('-');
                        return `${dia}/${mes}`;
                    }),
                    datasets: [{
                        label: 'Faturamento',
                        data: d.grafico_vendas.map(item => parseFloat(item.total || 0)),
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                        barThickness: 'flex', // Adapta largura
                        maxBarThickness: 30   // Mas não deixa ficar gordo demais
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, // Importante: Respeita a altura do DIV pai
                    plugins: { legend: { display: false } },
                    scales: { 
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#aaa' } },
                        x: { grid: { display: false }, ticks: { color: '#aaa' } }
                    }
                }
            });

            // 4. GRÁFICO DE PIZZA - COM PROTEÇÃO
            const ctxProdutos = document.getElementById('graficoProdutos').getContext('2d');
            
            // SE JÁ EXISTE, DESTRÓI
            if (chartProdutosInstance) {
                chartProdutosInstance.destroy();
            }

            const labels = d.grafico_produtos.map(p => p.nome);
            const dataValues = d.grafico_produtos.map(p => parseFloat(p.total_qtd || p.quantidade_vendida || p.total_quantidade || 0));

            chartProdutosInstance = new Chart(ctxProdutos, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Respeita a altura do DIV pai
                    plugins: { 
                        legend: { position: 'right', labels: { color: '#aaa', boxWidth: 12, font: { size: 11 } } } 
                    },
                    cutout: '70%'
                }
            });
        }

        carregarDashboard();
    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>