<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../models/Mesa.php';

try {
    $mesaModel = new Mesa();
    
    // Captura dados JSON ou POST padrão
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_REQUEST;
    
    $acao = $input['acao'] ?? '';
    $resposta = ['sucesso' => false, 'erro' => 'Ação inválida.'];

    switch ($acao) {
        case 'listar':
            $resposta = ['sucesso' => true, 'dados' => $mesaModel->listar()];
            break;

        case 'buscar_itens':
            $mesaId = (int)($_GET['mesa_id'] ?? 0);
            
            // CORREÇÃO: Usa 'buscarPorId' e 'listarItens' conforme o Modelo
            $mesa = $mesaModel->buscarPorId($mesaId);
            
            if (!$mesa) {
                throw new Exception("Mesa não encontrada.");
            }
            
            $itens = $mesaModel->listarItens($mesaId);
            
            $total = 0;
            foreach ($itens as $item) {
                $total += $item['preco'] * $item['quantidade'];
            }
            
            $resposta = [
                'sucesso' => true, 
                'dados' => [
                    'mesa' => $mesa, 
                    'itens' => $itens, 
                    'total' => $total
                ]
            ];
            break;

        case 'adicionar_item':
            $mesaId = $input['mesa_id'];
            $prodId = $input['produto_id'];
            $qtd = $input['quantidade'];
            
            if ($mesaModel->adicionarItem($mesaId, $prodId, $qtd)) {
                // Se a mesa estava livre, muda para ocupada
                $mesaModel->atualizarStatus($mesaId, 'ocupada');
                $resposta = ['sucesso' => true];
            } else {
                throw new Exception("Erro ao adicionar item.");
            }
            break;

        case 'remover_item':
            $itemId = $input['item_id'];
            if ($mesaModel->removerItem($itemId)) {
                $resposta = ['sucesso' => true];
            }
            break;

        case 'salvar_descricao':
            $mesaId = $input['mesa_id'];
            $desc = $input['descricao'];
            if ($mesaModel->atualizarDescricao($mesaId, $desc)) {
                $resposta = ['sucesso' => true];
            }
            break;

        case 'mudar_status':
            $mesaId = $input['mesa_id'];
            $status = $input['novo_status'];
            if ($mesaModel->atualizarStatus($mesaId, $status)) {
                $resposta = ['sucesso' => true];
            }
            break;

        // AÇÃO DE TRANSFERÊNCIA (Para o PDV)
        case 'transferir_para_pdv':
            $mesaId = $input['mesa_id'];
            
            $itens = $mesaModel->listarItens($mesaId);
            if (empty($itens)) {
                throw new Exception("A mesa está vazia.");
            }

            $mesa = $mesaModel->buscarPorId($mesaId);
            $nomeCliente = "Mesa " . $mesa['numero'] . ($mesa['descricao'] ? " - " . $mesa['descricao'] : "");

            // Muda status para vermelho
            $mesaModel->atualizarStatus($mesaId, 'em_fechamento');

            // Retorna dados para o JS
            $resposta = [
                'sucesso' => true, 
                'dados' => [
                    'itens' => $itens,
                    'mesa_id' => $mesaId,
                    'cliente_nome' => $nomeCliente
                ]
            ];
            break;

        case 'cancelar_fechamento':
            $mesaId = $input['mesa_id'];
            if ($mesaModel->atualizarStatus($mesaId, 'ocupada')) {
                $resposta = ['sucesso' => true, 'mensagem' => 'Mesa reaberta.'];
            } else {
                throw new Exception("Erro ao reabrir mesa.");
            }
            break;

        default:
            throw new Exception("Ação não reconhecida: " . $acao);
    }

} catch (Exception $e) {
    // Retorna JSON mesmo em caso de erro, evitando "Erro de Conexão" genérico
    $resposta = ['sucesso' => false, 'erro' => $e->getMessage()];
}

echo json_encode($resposta);
?>