<?php
// (Este ficheiro está em /public/)

// --- CÓDIGO DE DEPURAÇÃO ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CORREÇÃO 1: CAMINHOS (Subir um nível ../) ---
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// --- Carrega TODOS os modelos necessários ---
// (O bootstrap.php deveria fazer isto, mas garantimos aqui)
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Produto.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Mesa.php'; // <--- Necessário para fechar a mesa
require_once __DIR__ . '/../models/Configuracao.php';

header('Content-Type: application/json');

$acao = $_REQUEST['acao'] ?? '';
$resposta = ['sucesso' => false, 'erro' => 'Ação desconhecida.'];

try {
    if ($acao === 'carregar_dados') {
        $clienteModel = new Cliente();
        $produtoModel = new Produto();
        $configuracoes = (new Configuracao())->carregarConfiguracoes();
        $permitirSemEstoque = (($configuracoes['permitir_venda_sem_estoque'] ?? 'sim') === 'sim');
        
        $resposta['dados'] = [
            'clientes' => $clienteModel->listar(),
            'produtos' => $produtoModel->listar(['ativo' => 1]), // Lista apenas produtos ativos
            'permitir_venda_sem_estoque' => $permitirSemEstoque
        ];
        $resposta['sucesso'] = true;
        unset($resposta['erro']);

    } elseif ($acao === 'registrar_venda' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        $id_sessao_ativa = $_SESSION['id_sessao_ativa'] ?? null;

        if ($id_sessao_ativa === null) {
            throw new Exception("Sessão de caixa não encontrada. Faça login novamente e abra o caixa.");
        }
        
        $pedidoModel = new Pedido();

        $configuracoes = (new Configuracao())->carregarConfiguracoes();
        $permitirSemEstoque = (($configuracoes['permitir_venda_sem_estoque'] ?? 'sim') === 'sim');
        
        // 2. Cria o pedido
        // (O pdv.php agora envia o ID do cliente correto, seja da mesa ou do select)
        $novoPedidoId = $pedidoModel->criar(
            (int)($dados['cliente_id'] ?? 0), // Garante que é INT
            $dados['itens'] ?? [],
            $dados['valor_total'] ?? 0,
            $dados['forma_pagamento'] ?? 'Não definido',
            $id_sessao_ativa,
            $permitirSemEstoque
        );
        
        // --- CORREÇÃO 2: FECHAR A MESA (Se a venda veio de uma) ---
        $mesaIdParaFechar = (int)($dados['mesa_id'] ?? 0);
        
        if ($novoPedidoId > 0 && $mesaIdParaFechar > 0) {
            // A Venda foi um sucesso E ela veio de uma mesa.
            // Agora, vamos fechar/limpar a mesa.
            try {
                $mesaModel = new Mesa();
                
                // Assumindo que você tem um método 'fecharMesa' 
                // que apaga os itens E muda o status para 'livre'
                $mesaModel->fecharMesa($mesaIdParaFechar); 
                
            } catch (Exception $e) {
                // Se falhar ao fechar a mesa, não quebra a venda.
                // Apenas regista o erro para depuração.
                error_log("AVISO: Venda $novoPedidoId registada, mas falha ao fechar mesa $mesaIdParaFechar: " . $e->getMessage());
            }
        }
        // --- FIM DA CORREÇÃO 2 ---

        $resposta = [
            'sucesso' => true, 
            'mensagem' => 'Venda registrada com sucesso!',
            'pedido_id' => $novoPedidoId 
        ];
        unset($resposta['erro']);
    }

} catch (Throwable $e) { 
    $resposta['sucesso'] = false; 
    $resposta['erro'] = "ERRO FATAL NA API: " . $e->getMessage() . " (Ficheiro: " . basename($e->getFile()) . " Linha: " . $e->getLine() . ")";
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500); 
}

echo json_encode($resposta);
?>