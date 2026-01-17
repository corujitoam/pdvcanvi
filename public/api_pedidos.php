<?php
// --- INICIALIZAÇÃO E SEGURANÇA ---
ini_set('display_errors', 1); // Garante que erros sejam mostrados
error_reporting(E_ALL); // Mostra todos os tipos de erro
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
header('Content-Type: application/json'); // Define o tipo de conteúdo

$acao = $_REQUEST['acao'] ?? '';
$resposta = ['sucesso' => false, 'erro' => 'Ação desconhecida ou inválida.']; // Resposta padrão inicial

// DEBUG: Ponto de entrada
// echo json_encode(['debug' => 'API iniciada', 'acao' => $acao, 'GET' => $_GET]); exit; 

try {
    // DEBUG: Antes de instanciar o modelo
    // echo json_encode(['debug' => 'Antes de instanciar PedidoModel']); exit;
    $pedidoModel = new Pedido(); 
    // DEBUG: Depois de instanciar o modelo
    // echo json_encode(['debug' => 'PedidoModel instanciado']); exit;

    // --- AÇÃO: BUSCAR DETALHES DE UM PEDIDO ---
    if ($acao === 'detalhes' && isset($_GET['id'])) {
        // DEBUG: Dentro do IF detalhes
        // echo json_encode(['debug' => 'Dentro do IF acao=detalhes', 'pedido_id_get' => $_GET['id']]); exit;

        $pedidoId = filter_var($_GET['id'], FILTER_VALIDATE_INT);

        if ($pedidoId === false || $pedidoId <= 0) {
            $resposta['erro'] = "ID do pedido inválido.";
            // DEBUG: Erro de ID inválido
            // echo json_encode(['debug' => 'ID inválido', 'resposta' => $resposta]); exit; 
        } else {
            // DEBUG: Antes de chamar buscarPedidoComItens
            // echo json_encode(['debug' => 'Antes de chamar buscarPedidoComItens', 'pedidoId' => $pedidoId]); exit;
            
            // Chama a função CORRETA do modelo
            $detalhes = $pedidoModel->buscarPedidoComItens($pedidoId);

            // DEBUG: Depois de chamar buscarPedidoComItens
            // echo json_encode(['debug' => 'Depois de chamar buscarPedidoComItens', 'resultado_detalhes' => $detalhes]); exit; 

            if ($detalhes !== null) { 
                $resposta = [
                    'sucesso' => true,
                    'dados' => $detalhes 
                ];
                unset($resposta['erro']);
                // DEBUG: Sucesso ao buscar detalhes
                // echo json_encode(['debug' => 'Sucesso ao buscar detalhes', 'resposta' => $resposta]); exit;
            } else {
                $resposta['erro'] = "Pedido com ID $pedidoId não encontrado ou erro ao buscar detalhes.";
                // DEBUG: Pedido não encontrado ou erro no modelo
                // echo json_encode(['debug' => 'Pedido não encontrado ou erro modelo', 'resposta' => $resposta]); exit;
            }
        }
    }
    // --- FIM DA AÇÃO DETALHES ---

    // (Outras ações como 'listar', 'registrar_venda' poderiam estar aqui)

} catch (Throwable $e) { 
    $resposta['sucesso'] = false;
    $resposta['erro'] = "Erro na API: " . $e->getMessage(); 
    error_log("Erro API Pedidos [" . $acao . "]: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    // DEBUG: Erro pego no CATCH
    // echo json_encode(['debug' => 'Erro pego no CATCH', 'mensagem' => $e->getMessage(), 'resposta' => $resposta]); exit;
}

// DEBUG: Antes do echo final
// echo json_encode(['debug' => 'Antes do echo final', 'resposta_final' => $resposta]); exit;

// Envia a resposta final como JSON
echo json_encode($resposta);
// DEBUG: Depois do echo final (não deve ser alcançado se echo funcionar)
// echo json_encode(['debug' => 'Depois do echo final - ALGO ESTÁ ERRADO']); exit; 
?>