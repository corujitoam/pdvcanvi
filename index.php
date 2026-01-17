<?php
/* *
 * ==========================================================
 * INÍCIO DO CÓDIGO DE DEBUG
 * ==========================================================
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/* FIM DO CÓDIGO DE DEBUG */


// 1. Carrega a aplicação e inicia a sessão
require_once __DIR__ . '/bootstrap.php';
// 2. Verifica if o utilizador está logado
require_once __DIR__ . '/auth_check.php';

// Carrega as configurações do banco para usar na página
$configModel = new Configuracao();
$configuracoes = $configModel->carregarConfiguracoes();

/* * * Verificamos o estado do caixa para o JavaScript
 */
$isCaixaAberto = isset($_SESSION['id_sessao_ativa']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Menu Principal - <?php echo htmlspecialchars($configuracoes['nome_sistema'] ?? 'Sistema Quiosque'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS do Scroll do Modal Config (Correto) */
        .modal-overlay#modal-config .modal-box {
            max-height: 85vh; display: flex; flex-direction: column;
            width: 90vw; max-width: 900px; overflow: hidden;
        }
        .modal-overlay#modal-config .modal-header { flex-shrink: 0; }
        .modal-overlay#modal-config form#form-config-modal {
            display: flex; flex-direction: column;
            flex-grow: 1; min-height: 0;
        }
        .modal-overlay#modal-config .modal-body {
            flex-grow: 1; overflow-y: auto !important; overflow-x: hidden;
            padding: 24px; min-height: 0;
        }
         .modal-overlay#modal-config .modal-footer { flex-shrink: 0; }
         
        /* ==========================================================
         * == CSS: Estilo do Modal de Abrir Caixa ==
         * ========================================================== */
        .modal-overlay#modal-abrir-caixa .modal-box {
            background: var(--surface); padding: 32px 40px; border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px;
            text-align: center;
        }
        .modal-overlay#modal-abrir-caixa h2 { color: var(--text-primary); margin-bottom: 16px; }
        .modal-overlay#modal-abrir-caixa p { margin-bottom: 24px; color: var(--text-secondary); }
        .modal-overlay#modal-abrir-caixa .form-group { text-align: left; margin-bottom: 20px; }
        .modal-overlay#modal-abrir-caixa label { display: block; margin-bottom: 8px; font-weight: 600; }
        .modal-overlay#modal-abrir-caixa input { width: 100%; }
        .modal-overlay#modal-abrir-caixa .btn { width: 100%; }

        /* Regra que força a exibição (Reutilizada) */
        .modal-overlay.visivel {
            display: flex !important; opacity: 1 !important; visibility: visible !important;
        }
    </style>
</head>
<body>
    <header class="header"> 
        <div class="header-inner header-inner--standard"> 
            <div class="header-start"> 
                <div class="user-menu"> 
                    <button class="user-menu-button" id="user-menu-btn" title="Menu do Utilizador">⋮</button> 
                    <div class="user-menu-dropdown" id="user-menu-dropdown"> 
                        <div class="dropdown-header"> 
                            <?php
                                // CORREÇÃO DO ERRO: Verifica se é array antes de acessar
                                $nomeExibicao = is_array($utilizadorLogado) ? ($utilizadorLogado['nome'] ?? 'Utilizador') : ($_SESSION['user_nome'] ?? 'Utilizador');
                                $cargoExibicao = is_array($utilizadorLogado) ? ($utilizadorLogado['cargo'] ?? '') : ($_SESSION['user_cargo'] ?? '');
                            ?>
                            <div class="user-name"><?php echo htmlspecialchars($nomeExibicao); ?></div> 
                            <div class="user-role"><?php echo htmlspecialchars($cargoExibicao); ?></div> 
                        </div>
    
                        <a href="#" id="btn-abrir-config">⚙️ Configurações</a>
                        
                        <?php 
                        /* *
                         * DOCUMENTAÇÃO: Link para Fechar Caixa
                         * Isto agora aponta para a página 'public/fechar_caixa.php' (corrigida)
                         */
                        if ($isCaixaAberto): 
                        ?>
                            <a href="public/fechar_caixa.php">💰 Fechar Caixa</a>
                        <?php endif; ?>
                        
                        <a href="public/logout.php">🚪 Logout</a>
    
                    </div> 
                </div> 
            </div> 
            <span class="brand"><?php echo htmlspecialchars($configuracoes['nome_sistema'] ?? 'Sistema Quiosque'); ?></span> 
            <div class="header-actions"> 
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema"> 
                    <span class="icon-sun">☀️</span> 
                    <span class="icon-moon">🌙</span> 
                </button> 
            </div> 
        </div> 
    </header> 
    
    <div class="container"> 
        <div class="main-menu-grid">
    
    <a href="public/pdv.php" class="card card-destaque" id="card-pdv"> 
        <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg> <div class="card-title">PDV / Vendas</div> <div class="card-sub">Registrar vendas</div> 
    </a> 
    <a href="public/dashboard.php" class="card"> 
        <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2V6h2v5zm4-3H6v3h2V8zm4-3h-2v6h2V5zM12 4H2a1 1 0 0 0-1 1v9h12V5a1 1 0 0 0-1-1zM1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V3z"/></svg> <div class="card-title">Dashboard</div> <div class="card-sub">Visualizar métricas</div> 
    </a> 
    <a href="public/mesas.php" class="card" id="card-mesas"> 
        <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M22 7.5a2.5 2.5 0 0 0-2.5-2.5h-15A2.5 2.5 0 0 0 2 7.5v1a.5.5 0 0 0 .5.5h19a.5.5 0 0 0 .5-.5v-1zM4.68 11.5h-.18l-1.3 5.2a.5.5 0 0 0 .48.6h1.64a.5.5 0 0 0 .48-.6l-1.3-5.2h.18a.5.5 0 0 0 0-1h-1.3a.5.5 0 0 0 0 1zm14.82 0h.18l-1.3 5.2a.5.5 0 0 0 .48.6h1.64a.5.5 0 0 0 .48-.6l-1.3-5.2h.18a.5.5 0 0 0 0-1h-1.3a.5.5 0 0 0 0 1zM11.5 11a.5.5 0 0 0-.5.5v5a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-5a.5.5 0 0 0-.5-.5h-1z"/></svg> <div class="card-title">Controle de Mesas</div> <div class="card-sub">Gerir contas de mesas</div> 
    </a> 
    <a href="public/pedidos.php" class="card"> <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 2.5A2.5 2.5 0 0 1 8 0s2.5 2.292 2.5 5.5c0 2.373-1.4 3.77-2.5 4.905C6.9 14.27 5.5 12.873 5.5 10.5A2.5 2.5 0 0 1 5.5 2.5z"/><path d="M8 4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/><path d="M4 10.748c-.968-.974-1.787-2.13-2.228-3.412a31.334 31.334 0 0 1 2.228-3.412l.002-.002.002-.002.001-.002c.002-.003.004-.006.007-.009a.266.266 0 0 1 .03-.028c.003-.002.007-.004.01-.006.004-.003.008-.005.013-.007a.5.5 0 0 1 .07-.036C4.41 3.8 4.7 3.7 5 3.7c.3 0 .59.1.81.203.023.01.044.022.065.035.004.002.008-.004.01.007.004.002.007.005.01.006.002.002.005.005.007.008l.001.002.002.002.002.002c.442 1.282 1.26 2.438 2.228 3.412a31.334 31.334 0 0 1-2.228 3.412l-.002.002-.002.002-.001.002c-.002.003-.004.006-.007.009a.266.266 0 0 1-.03.028c-.003.002-.007.004-.01.006-.004.003-.008.005-.013.007a.5.5 0 0 1-.07.036c-.22.103-.51.203-.81.203-.3 0-.59-.1-.81-.203a.5.5 0 0 1-.065-.035c-.004-.002-.008-.004-.01-.007-.004-.002-.007-.005-.01-.006a.266.266 0 0 1-.007-.008l-.001-.002-.002-.002-.002-.002z"/></svg> <div class="card-title">Pedidos</div> <div class="card-sub">Consultar pedidos</div> </a> <a href="public/produtos.php" class="card"> <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg> <div class="card-title">Cadastro de Produtos</div> <div class="card-sub">Adicionar e listar</div> </a> <a href="public/clientes.php" class="card"> <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg> <div class="card-title">Cadastro de Clientes</div> <div class="card-sub">Adicionar e listar</div> </a> <a href="public/relatorios.php" class="card"> <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1.5 1a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h12a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5H1.5zM0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg> <div class="card-title">Relatórios</div> <div class="card-sub">Analisar performance</div> </a> <a href="public/utilizadores.php" class="card"> <svg class="card-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-6-4-6 3-6 4 1 1 1 1h10zm-9.995-.944v-.002.002zM3.022 13h9.956a.274.274 0 0 0 .014-.002l.008-.002c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664a1.05 1.05 0 0 0 .022.004zM8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg> <div class="card-title">Utilizadores</div> <div class="card-sub">Gerir acessos</div> </a>
    </div>

    <div class="modal-overlay" id="modal-config">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Configurações do Sistema</h2>
                <button class="btn-fechar-modal" id="btn-fechar-config" title="Fechar">&times;</button>
            </div>
            <form id="form-config-modal">
                <div class="modal-body" id="modal-config-body">
                    <p>A carregar configurações...</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modal-abrir-caixa">
        <div class="modal-box">
            <form id="form-abrir-caixa">
                <h2>Abrir Caixa</h2>
                <p>Insira o valor inicial em caixa (fundo de troco) para iniciar o turno.</p>
                <div id="erro-abrir-caixa" class="erro-mensagem" style="display: none; margin-bottom: 20px;"></div>
                <div class="form-group">
                    <label for="valor_abertura">Valor de Abertura (R$)</label>
                    <input type="text" id="valor_abertura" name="valor_abertura" value="0,00" required autofocus onfocus="this.select();">
                </div>
                <button type="submit" class="btn btn-primary">Abrir Caixa e Iniciar</button>
            </form>
        </div>
    </div>
    
    <div id="toast"></div>

    <script>
    // Passa o estado do caixa de PHP para JavaScript
    let isCaixaAberto = <?php echo $isCaixaAberto ? 'true' : 'false'; ?>;
    
    document.addEventListener('DOMContentLoaded', function() {

        const toast = document.getElementById('toast');
        function showToast(mensagem, isErro = false) { 
            if(toast) { 
                toast.textContent = mensagem; 
                toast.className = `show ${isErro ? 'erro' : 'sucesso'}`; 
                toast.style.animation = 'none'; 
                toast.offsetHeight; /* trigger reflow */ 
                toast.style.animation = null; 
                setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000); 
            } else { 
                console.warn("Elemento Toast não encontrado!"); 
            } 
        }

        // --- Lógica do Menu de Utilizador (Inalterada) ---
        const userMenuBtn = document.getElementById('user-menu-btn'); 
        const userMenuDropdown = document.getElementById('user-menu-dropdown'); 
        if(userMenuBtn && userMenuDropdown) { 
            userMenuBtn.addEventListener('click', function(event) { 
                event.stopPropagation(); 
                userMenuDropdown.style.display = userMenuDropdown.style.display === 'block' ? 'none' : 'block'; 
            }); 
            window.addEventListener('click', function() { 
                if (userMenuDropdown.style.display === 'block') { 
                    userMenuDropdown.style.display = 'none'; 
                } 
            }); 
        }

        // --- Lógica do Modal de Configurações (Inalterada) ---
        const modalConfig = document.getElementById('modal-config'); 
        const btnAbrirConfig = document.getElementById('btn-abrir-config'); 
        const btnFecharConfig = document.getElementById('btn-fechar-config'); 
        const formConfig = document.getElementById('form-config-modal'); 
        const modalConfigBody = document.getElementById('modal-config-body');
        async function carregarDadosConfig() { 
            modalConfigBody.innerHTML = '<p>A carregar configurações...</p>'; 
            try { 
                const response = await fetch('public/api_configuracoes.php?acao=carregar'); 
                const res = await response.json(); 
                if (res.sucesso) { 
                    // (O HTML longo do formulário de config)
                    modalConfigBody.innerHTML = ` <fieldset> <legend>Geral</legend> <div class="grid-2-col"> <div> <label for="nome_sistema">Nome do Sistema</label> <input type="text" id="nome_sistema" name="nome_sistema" value="${res.dados.nome_sistema || ''}"> <small>Exibido no topo da página e nos recibos.</small> </div> <div> <label for="moeda_simbolo">Símbolo da Moeda</label> <input type="text" id="moeda_simbolo" name="moeda_simbolo" value="${res.dados.moeda_simbolo || ''}"> <small>Ex: R$, €</small> </div> </div> </fieldset> <fieldset> <legend>Impressão de Recibos</legend> <div class="grid-2-col"> <div> <label for="impressora_nome">Nome da Impressora</label> <input type="text" id="impressora_nome" name="impressora_nome" value="${res.dados.impressora_nome || ''}"> <small>Nome exato da impressora térmica no sistema.</small> </div> <div> <label for="impressao_cabecalho">Cabeçalho do Recibo</label> <input type="text" id="impressao_cabecalho" name="impressao_cabecalho" value="${res.dados.impressao_cabecalho || ''}"> <small>Primeira linha do recibo. Ex: Nome da Loja.</small> </div> <div> <label for="impressao_rodape">Rodapé do Recibo</label> <input type="text" id="impressao_rodape" name="impressao_rodape" value="${res.dados.impressao_rodape || ''}"> <small>Última linha do recibo. Ex: Agradecimento.</small> </div> </div> </fieldset> <fieldset> <legend>Financeiro</legend> <div class="grid-2-col"> <div> <label for="taxa_servico_padrao">Taxa de Serviço Padrão (%)</label> <input type="number" step="0.1" id="taxa_servico_padrao" name="taxa_servico_padrao" min="0" max="100" value="${res.dados.taxa_servico_padrao || '0'}"> <small>Valor percentual (ex: 10). Deixe 0 para desativar.</small> </div> <div> <label for="cliente_padrao_pdv">ID Cliente Padrão PDV</label> <input type="number" id="cliente_padrao_pdv" name="cliente_padrao_pdv" min="1" value="${res.dados.cliente_padrao_pdv || ''}"> <small>ID do cliente "Consumidor Final" para PDV.</small> </div> </div> </fieldset> <fieldset> <legend>Operacional</legend> <div class="grid-2-col"> <div> <label for="numero_mesas">Quantidade de Mesas</label> <input type="number" id="numero_mesas" name="numero_mesas" min="1" step="1" value="${res.dados.numero_mesas || '10'}"> <small>Número total de mesas disponíveis no controle.</small> </div> <div> <label for="alerta_estoque_baixo">Alerta de Estoque Baixo</label> <input type="number" id="alerta_estoque_baixo" name="alerta_estoque_baixo" value="${res.dados.alerta_estoque_baixo || ''}"> <small>Alertar quando estoque <= este valor.</small> </div> <div> <label for="permitir_venda_sem_estoque">Permitir Venda Sem Estoque</label> <select id="permitir_venda_sem_estoque" name="permitir_venda_sem_estoque"> <option value="sim" ${res.dados.permitir_venda_sem_estoque === 'sim' ? 'selected' : ''}>Sim</option> <option value="nao" ${res.dados.permitir_venda_sem_estoque === 'nao' ? 'selected' : ''}>Não</option> </select> <small>Permite vender produtos com estoque 0 ou negativo.</small> </div> </div> </fieldset> `; 
                } else { 
                    modalConfigBody.innerHTML = `<p class="erro-mensagem" style="margin: 24px;">Erro: ${res.erro || 'Falha ao carregar configurações.'}</p>`; 
                } 
            } catch (error) { 
                modalConfigBody.innerHTML = '<p class="erro-mensagem" style="margin: 24px;">Erro de conexão ao carregar dados.</p>'; 
            } 
        }
        if (btnAbrirConfig && modalConfig) { btnAbrirConfig.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); carregarDadosConfig(); modalConfig.classList.add('visivel'); }); }
        function fecharModalConfig() { if(modalConfig) { modalConfig.classList.remove('visivel'); } }
        if (btnFecharConfig) btnFecharConfig.addEventListener('click', fecharModalConfig); 
        if (modalConfig) modalConfig.addEventListener('click', function(e) { if (e.target === modalConfig) { fecharModalConfig(); } });
        if (formConfig) { formConfig.addEventListener('submit', async function(e) { e.preventDefault(); const formData = new FormData(formConfig); formData.append('acao', 'salvar'); try { const response = await fetch('public/api_configuracoes.php', { method: 'POST', body: formData }); const res = await response.json(); if (res.sucesso) { showToast(res.mensagem); fecharModalConfig(); setTimeout(() => window.location.reload(), 1000); } else { showToast(res.erro || 'Ocorreu uma falha ao salvar.', true); } } catch (error) { showToast('Erro de conexão ao salvar dados.', true); } }); }

        
        // --- LÓGICA: Modal de Abrir Caixa (A que estava a funcionar) ---
        const modalAbrirCaixa = document.getElementById('modal-abrir-caixa');
        const formAbrirCaixa = document.getElementById('form-abrir-caixa');
        const erroAbrirCaixaDiv = document.getElementById('erro-abrir-caixa');
        const cardsQueExigemCaixa = document.querySelectorAll('#card-pdv, #card-mesas');
        let urlParaRedirecionar = '';
        
        cardsQueExigemCaixa.forEach(card => {
            card.addEventListener('click', function(e) {
                if (isCaixaAberto) {
                    // Se o caixa já está aberto, deixa o clique acontecer
                    return true;
                }
                
                // Se o caixa está FECHADO:
                e.preventDefault(); // 1. Cancela o clique
                urlParaRedirecionar = this.href; // 2. Guarda onde o utilizador queria ir
                
                // 3. Mostra o modal de abertura
                if (modalAbrirCaixa) {
                    if (erroAbrirCaixaDiv) erroAbrirCaixaDiv.style.display = 'none'; // Esconde erros antigos
                    modalAbrirCaixa.classList.add('visivel');
                    // Foco automático no input para UX
                    const inputValor = modalAbrirCaixa.querySelector('input[name="valor_abertura"]');
                    if (inputValor) inputValor.focus();
                }
            });
        });

        // 2. Adiciona o "ouvinte" ao formulário do modal
        if (formAbrirCaixa) {
            formAbrirCaixa.addEventListener('submit', async function(e) {
                e.preventDefault(); // Não deixa o formulário recarregar a página
                
                const formData = new FormData(formAbrirCaixa);
                const btnSubmit = formAbrirCaixa.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'A abrir...';

                try {
                    const response = await fetch('public/api_caixa.php?acao=abrir', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const res = await response.json();

                    if (res.sucesso) {
                        showToast(res.mensagem || 'Caixa aberto com sucesso!');
                        isCaixaAberto = true; 
                        window.location.href = urlParaRedirecionar;
                    } else {
                        if (erroAbrirCaixaDiv) {
                            erroAbrirCaixaDiv.textContent = res.erro || 'Falha desconhecida.';
                            erroAbrirCaixaDiv.style.display = 'block';
                        }
                        btnSubmit.disabled = false;
                        btnSubmit.textContent = 'Abrir Caixa e Iniciar';
                    }
                } catch (error) {
                    if (erroAbrirCaixaDiv) {
                        erroAbrirCaixaDiv.textContent = 'Erro de conexão com a API.';
                        erroAbrirCaixaDiv.style.display = 'block';
                    }
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Abrir Caixa e Iniciar';
                }
            });
        }
        
        // 3. Lógica para fechar o modal de abrir caixa (clicando fora)
        if (modalAbrirCaixa) {
            modalAbrirCaixa.addEventListener('click', function(e) {
                if (e.target === modalAbrirCaixa) {
                    modalAbrirCaixa.classList.remove('visivel');
                }
            });
        }

        console.log("Index: Script principal carregado.");

    });
    </script>
    <script src="assets/js/theme.js" defer></script>
</body>
</html>