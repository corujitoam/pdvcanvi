<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// 1. Carrega o sistema
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// 2. Carrega os modelos necessários
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Produto.php';
require_once __DIR__ . '/../models/Cliente.php';

try {
    // Instancia os modelos
    $pedidoModel = new Pedido();
    $produtoModel = new Produto();
    $clienteModel = new Cliente();

    // Pega a ação (aceita GET ou POST)
    $acao = $_REQUEST['acao'] ?? 'carregar_dados';

    $resposta = ['sucesso' => false];

    // --- SWITCH UNIVERSAL (Aceita novo e antigo) ---
    switch ($acao) {
        
        // 1. MÉTODO NOVO (Tudo de uma vez - Mais rápido)
        case 'carregar_dados':
            $dados = [
                'totais' => [
                    'faturamento' => $pedidoModel->getFaturamentoHoje(),
                    'pedidos' => $pedidoModel->getTotalPedidosHoje(),
                    'novos_clientes' => $clienteModel->getTotalClientesHoje()
                ],
                'grafico_vendas' => $pedidoModel->getVendasUltimos7Dias(),
                'grafico_produtos' => $produtoModel->getProdutosMaisVendidos(5),
                'estoque_baixo' => $produtoModel->getProdutosComEstoqueBaixo(5),
                'atividade_recente' => $pedidoModel->getUltimasVendas(5)
            ];
            $resposta['sucesso'] = true;
            $resposta['dados'] = $dados;
            break;

        // 2. COMPATIBILIDADE: Métricas (Frontend Antigo)
        case 'metricas_hoje':
            $resposta['dados'] = [
                'faturamentoHoje' => number_format($pedidoModel->getFaturamentoHoje(), 2, ',', '.'),
                'pedidosHoje' => $pedidoModel->getTotalPedidosHoje(),
                'clientesHoje' => $clienteModel->getTotalClientesHoje(),
                'estoqueBaixo' => $produtoModel->getProdutosComEstoqueBaixo(5),
                'ultimasVendas' => $pedidoModel->getUltimasVendas(5)
            ];
            $resposta['sucesso'] = true;
            break;

        // 3. COMPATIBILIDADE: Vendas Semana (Frontend Antigo)
        case 'vendas_semana':
            $resposta['dados'] = $pedidoModel->getVendasUltimos7Dias();
            $resposta['sucesso'] = true;
            break;

        // 4. COMPATIBILIDADE: Top Produtos (Frontend Antigo)
        case 'produtos_mais_vendidos':
            $resposta['dados'] = $produtoModel->getProdutosMaisVendidos(5);
            $resposta['sucesso'] = true;
            break;

        default:
            throw new Exception("Ação inválida ou desconhecida: " . htmlspecialchars($acao));
    }

} catch (Exception $e) {
    http_response_code(500);
    $resposta = ['sucesso' => false, 'erro' => 'Erro no Dashboard: ' . $e->getMessage()];
}

echo json_encode($resposta);
?>