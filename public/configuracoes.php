<?php
// 1. Inicia a aplicação e a sessão
require_once __DIR__ . '/../bootstrap.php';
// 2. Garante que apenas utilizadores logados possam aceder
require_once __DIR__ . '/../auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações - Sistema Quiosque</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    </head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
             <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">CONFIGURAÇÕES DO SISTEMA</span>
            <div class="header-actions">
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="config-wrapper"> 
            <form id="form-config" class="form-config">
                <fieldset>
                    <legend>Geral</legend>
                    <div class="grid-2-col">
                        <div>
                            <label for="nome_sistema">Nome do Sistema</label>
                            <input type="text" id="nome_sistema" name="nome_sistema">
                            <small>Exibido no topo da página e nos recibos.</small>
                        </div>
                        <div>
                            <label for="moeda_simbolo">Símbolo da Moeda</label>
                            <input type="text" id="moeda_simbolo" name="moeda_simbolo">
                            <small>Ex: R$, €</small>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Impressão de Recibos</legend>
                    <div class="grid-2-col">
                         <div>
                            <label for="impressora_nome">Nome da Impressora</label>
                            <input type="text" id="impressora_nome" name="impressora_nome">
                            <small>Nome exato da impressora térmica no sistema.</small>
                        </div>
                         <div>
                            <label for="impressao_cabecalho">Cabeçalho do Recibo</label>
                            <input type="text" id="impressao_cabecalho" name="impressao_cabecalho">
                            <small>Primeira linha do recibo. Ex: Nome da Loja.</small>
                        </div>
                         <div>
                            <label for="impressao_rodape">Rodapé do Recibo</label>
                            <input type="text" id="impressao_rodape" name="impressao_rodape">
                            <small>Última linha do recibo. Ex: Agradecimento.</small>
                        </div>
                         </div>
                </fieldset>

                 <fieldset>
                    <legend>Financeiro</legend>
                    <div class="grid-2-col">
                        <div>
                            <label for="taxa_servico_padrao">Taxa de Serviço Padrão (%)</label>
                            <input type="number" step="0.1" id="taxa_servico_padrao" name="taxa_servico_padrao" min="0" max="100">
                            <small>Valor percentual (ex: 10 para 10%). Deixe 0 para desativar.</small>
                        </div>
                        <div>
                            <label for="cliente_padrao_pdv">ID Cliente Padrão PDV</label>
                            <input type="number" id="cliente_padrao_pdv" name="cliente_padrao_pdv" min="1">
                             <small>ID do cliente "Consumidor Final" para pré-selecionar no PDV.</small>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Operacional</legend>
                    <div class="grid-2-col">
                       <div>
                            <label for="numero_mesas">Quantidade de Mesas</label>
                            <input type="number" id="numero_mesas" name="numero_mesas" min="1" step="1" value="10">
                            <small>Número total de mesas disponíveis no controle.</small>
                        </div>
                        <div>
                            <label for="alerta_estoque_baixo">Alerta de Estoque Baixo</label>
                            <input type="number" id="alerta_estoque_baixo" name="alerta_estoque_baixo">
                            <small>Alertar quando o estoque for igual ou menor que este valor.</small>
                        </div>
                        <div>
                            <label for="permitir_venda_sem_estoque">Permitir Venda Sem Estoque</label>
                            <select id="permitir_venda_sem_estoque" name="permitir_venda_sem_estoque">
                                <option value="sim">Sim</option>
                                <option value="nao">Não</option>
                            </select>
                            <small>Permite vender produtos com estoque 0 ou negativo.</small>
                        </div>
                    </div>
                </fieldset>
                
                <div class="botoes-acao">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div> </div>

    <div id="toast"></div>

    <script>
    document.addEventListener('DOMContentLoaded', async function() {
        // ... (SEU SCRIPT - 100% INTACTO) ...
        const form = document.getElementById('form-config');
        const toast = document.getElementById('toast');

        async function carregarDados() {
            try {
                const response = await fetch('api_configuracoes.php?acao=carregar');
                const res = await response.json();

                if (res.sucesso) {
                    for (const chave in res.dados) {
                        const campo = form.elements[chave];
                        if (campo) {
                            campo.value = res.dados[chave];
                        }
                    }
                } else {
                    showToast(res.erro || 'Falha ao carregar configurações.', true);
                }
            } catch (error) {
                showToast('Erro de conexão ao carregar dados.', true);
            }
        }

        function showToast(mensagem, isErro = false) {
            toast.textContent = mensagem;
            toast.className = isErro ? 'erro' : 'sucesso';
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault(); 
            const formData = new FormData(form);
            formData.append('acao', 'salvar');

            try {
                const response = await fetch('api_configuracoes.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.sucesso) {
                    showToast(res.mensagem);
                } else {
                    showToast(res.erro || 'Ocorreu uma falha ao salvar.', true);
                }
            } catch (error) {
                showToast('Erro de conexão ao salvar dados.', true);
            }
        });
        
        carregarDados();
    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>