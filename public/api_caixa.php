<?php
// public/api_caixa.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- CORREÇÃO DOS CAMINHOS ---
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../models/CaixaSessao.php';
require_once __DIR__ . '/../models/CaixaMovimentacao.php';
require_once __DIR__ . '/../models/Relatorio.php';

// --- Instância dos Modelos ---
$sessaoModel = new CaixaSessao();
$movModel = new CaixaMovimentacao();
$relatorioModel = new Relatorio(); 

$id_utilizador = $utilizadorLogado['id'];
$acao = $_REQUEST['acao'] ?? ''; 
$resposta = ['sucesso' => false, 'erro' => 'Ação desconhecida ou inválida.'];

try {
    // Busca se existe alguma sessão ABERTA no banco
    $sessaoAberta = $sessaoModel->buscarSessaoAberta();
    $id_sessao_ativa = $sessaoAberta['id'] ?? null;

    // =========================================================
    // AÇÃO: ABRIR CAIXA (COM AUTO-RECUPERAÇÃO)
    // =========================================================
    if ($acao === 'abrir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // 1. VERIFICAÇÃO DE SEGURANÇA (O TRUQUE):
        // Se o banco diz que já está aberto, recuperamos a sessão silenciosamente.
        if ($sessaoAberta) {
            $_SESSION['id_sessao_ativa'] = $sessaoAberta['id'];
            
            $resposta = [
                'sucesso' => true, 
                'mensagem' => 'Sessão recuperada com sucesso! Redirecionando...', 
                'id_sessao' => $sessaoAberta['id'],
                'recuperado' => true
            ];
            
            // Envia a resposta e encerra o script para não tentar abrir de novo
            echo json_encode($resposta);
            exit;
        }

        // 2. Se não estiver aberto, segue o fluxo normal de abertura
        $valor_abertura_str = str_replace(['R$', ' ', ','], ['', '', '.'], $_POST['valor_abertura']);
        $valor_abertura = (float) $valor_abertura_str;
        
        $novo_id_sessao = $sessaoModel->abrir($id_utilizador, $valor_abertura);

        if ($novo_id_sessao) {
            $_SESSION['id_sessao_ativa'] = $novo_id_sessao;
            $resposta = ['sucesso' => true, 'mensagem' => 'Caixa aberto com sucesso!', 'id_sessao' => $novo_id_sessao];
            unset($resposta['erro']);
        } else {
            throw new Exception("Erro ao criar sessão no banco de dados.");
        }

    // --- AÇÃO: GET RESUMO ---
    } elseif ($acao === 'get_resumo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        
        if (!$id_sessao_ativa) { 
            throw new Exception("Nenhuma sessão de caixa aberta encontrada."); 
        }
        
        $detalhes_sessao = $sessaoModel->buscarPorId($id_sessao_ativa);
        
        $resumo_vendas = $relatorioModel->gerarFechamentoSimples(
            $detalhes_sessao['data_abertura'], 
            date('Y-m-d H:i:s')
        );

        $dados_finais = [
            'valor_abertura' => (float) $detalhes_sessao['valor_abertura'],
            'total_suprimentos' => (float) $detalhes_sessao['total_suprimentos'],
            'total_sangrias' => (float) $detalhes_sessao['total_sangrias'],
            'total_vendas' => (float) $resumo_vendas['total_geral']['faturamento_total'],
            'detalhes_pagamento' => $resumo_vendas['detalhes_pagamento'],
        ];
        $resposta = ['sucesso' => true, 'dados' => $dados_finais];
        unset($resposta['erro']);

    // --- AÇÃO: FECHAR CAIXA ---
    } elseif ($acao === 'fechar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$id_sessao_ativa) { throw new Exception("Nenhuma sessão de caixa aberta encontrada."); }
        
        $detalhes_sessao = $sessaoModel->buscarPorId($id_sessao_ativa);
        $resumo_vendas = $relatorioModel->gerarFechamentoSimples($detalhes_sessao['data_abertura'], date('Y-m-d H:i:s'));
        
        $total_vendas_dinheiro = 0; 
        if (!empty($resumo_vendas['detalhes_pagamento'])) {
            foreach ($resumo_vendas['detalhes_pagamento'] as $pgto) {
                if (strtolower($pgto['forma_pagamento']) === 'dinheiro') { 
                    $total_vendas_dinheiro = (float) $pgto['valor_total']; 
                } 
            }
        }

        // Pega valores do POST
        $configuracoes = (new Configuracao())->carregarConfiguracoes();
        $moeda = $configuracoes['moeda_simbolo'] ?? 'R$';
        
        $contado_dinheiro = (float) str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['contado_dinheiro']);
        $contado_cartao = (float) str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['contado_cartao']);
        $contado_outros = (float) str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['contado_pix']); 
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Cálculos
        $total_apurado_sistema = (float) $detalhes_sessao['valor_abertura'] + (float) $detalhes_sessao['total_suprimentos'] - (float) $detalhes_sessao['total_sangrias'] + (float) $resumo_vendas['total_geral']['faturamento_total'];
        $esperado_dinheiro = (float) $detalhes_sessao['valor_abertura'] + (float) $detalhes_sessao['total_suprimentos'] - (float) $detalhes_sessao['total_sangrias'] + $total_vendas_dinheiro;
        $diferenca = $contado_dinheiro - $esperado_dinheiro;
        
        $valores_fecho = [
            'total_apurado_sistema' => $total_apurado_sistema, 
            'contado_dinheiro' => $contado_dinheiro,
            'contado_cartao' => $contado_cartao, 
            'contado_outros' => $contado_outros,
            'diferenca' => $diferenca, 
            'observacoes' => $observacoes
        ];
        
        $sucesso = $sessaoModel->fechar($id_sessao_ativa, $id_utilizador, $valores_fecho); 
        
        if ($sucesso) { 
            unset($_SESSION['id_sessao_ativa']);
            $resposta = ['sucesso' => true, 'mensagem' => 'Caixa fechado com sucesso!'];
            unset($resposta['erro']);
        } else { 
            throw new Exception("Ocorreu um erro ao atualizar o status da sessão no banco de dados."); 
        }

    // --- AÇÃO: REGISTRAR MOVIMENTAÇÃO ---
    } elseif ($acao === 'registrar_movimentacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$id_sessao_ativa) { throw new Exception("Nenhuma sessão de caixa ativa encontrada."); }
        
        $configuracoes = (new Configuracao())->carregarConfiguracoes(); // Instancia aqui caso precise
        $moeda = $configuracoes['moeda_simbolo'] ?? 'R$';

        $tipoRecebido = $_POST['tipo'] ?? null;
        $valor_str = str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['valor']);
        $valor = (float) $valor_str;
        $motivo = $_POST['motivo'] ?? null; 
        
        if (empty($tipoRecebido) || !in_array($tipoRecebido, ['ENTRADA', 'SAIDA']) || $valor <= 0) {
            throw new InvalidArgumentException("Tipo ('ENTRADA'/'SAIDA') e Valor positivo são obrigatórios.");
        }
        
        $tipoParaSalvar = (strtolower($tipoRecebido) === 'entrada') ? 'SUPRIMENTO' : 'SANGRIA'; 
        $movId = $movModel->registrarMovimentacao($id_sessao_ativa, $id_utilizador, $tipoParaSalvar, $valor, $motivo);
        
        $resposta = ['sucesso' => true, 'mensagem' => ucfirst(strtolower($tipoRecebido)) . " registrada com sucesso!", 'mov_id' => $movId];
        unset($resposta['erro']);
    }

} catch (Exception $e) { 
    $resposta = ['sucesso' => false, 'erro' => $e->getMessage()]; 
    error_log("Erro API Caixa [" . $acao . "]: " . $e->getMessage()); 
    http_response_code(500);
}

echo json_encode($resposta);
?>