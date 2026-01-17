<?php
require_once __DIR__ . '/Conexao.php';

class Pedido {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getConexao();
    }

    // --- MÉTODOS DE VENDAS (PDV) ---

    /**
     * Cria um pedido (venda) e baixa o estoque de forma segura.
     *
     * Importante: esta função faz a validação de estoque no backend para evitar
     * vendas com estoque negativo (quando a configuração do sistema bloquear).
     */
    public function criar($cliente_id, $itens, $valor_total, $forma_pagamento, $id_sessao, $permitirVendaSemEstoque = true) {
        if (empty($itens)) { throw new Exception("Venda sem itens."); }
        if ((int)$id_sessao <= 0) { throw new Exception("Sessão inválida."); }

        try {
            $this->pdo->beginTransaction();

            // 1) Valida estoque (com lock) antes de inserir os itens
            $stmtSel = $this->pdo->prepare("SELECT id, nome, estoque FROM produtos WHERE id = ? FOR UPDATE");
            foreach ($itens as $item) {
                $pid = (int)($item['id'] ?? 0);
                $qtd = (int)($item['quantidade'] ?? 0);
                if ($pid <= 0 || $qtd <= 0) {
                    throw new Exception('Item inválido na venda.');
                }

                $stmtSel->execute([$pid]);
                $p = $stmtSel->fetch(PDO::FETCH_ASSOC);
                if (!$p) {
                    throw new Exception("Produto não encontrado (ID: {$pid}).");
                }

                $estoqueAtual = (int)($p['estoque'] ?? 0);
                if (!$permitirVendaSemEstoque && $estoqueAtual < $qtd) {
                    $nome = (string)($p['nome'] ?? 'Produto');
                    throw new Exception("Estoque insuficiente para '{$nome}'. Disponível: {$estoqueAtual}.");
                }
            }

            // 2) Insere o pedido
            $sql = "INSERT INTO pedidos (cliente_id, data_pedido, valor_total, forma_pagamento, id_sessao) 
                    VALUES (:cli, NOW(), :val, :pgto, :sessao)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':cli' => $cliente_id,
                ':val' => $valor_total,
                ':pgto' => $forma_pagamento,
                ':sessao' => $id_sessao
            ]);
            $pedidoId = (int)$this->pdo->lastInsertId();

            // 3) Insere itens e baixa estoque
            $stmtItem = $this->pdo->prepare("INSERT INTO pedido_items (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmtEstoque = $this->pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");

            foreach ($itens as $item) {
                $pid = (int)$item['id'];
                $qtd = (int)$item['quantidade'];
                $preco = (float)$item['preco'];

                $stmtItem->execute([ $pedidoId, $pid, $qtd, $preco ]);
                $stmtEstoque->execute([ $qtd, $pid ]);
            }

            $this->pdo->commit();
            return $pedidoId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // --- MÉTODO PARA O RECIBO (Este era o que faltava!) ---
    public function buscarPedidoComItens($pedidoId) {
        try {
            // 1. Busca os dados principais do pedido + nome do cliente
            $sqlPedido = "SELECT p.id, p.valor_total, p.data_pedido, p.forma_pagamento, c.nome AS cliente_nome
                          FROM pedidos p
                          LEFT JOIN clientes c ON p.cliente_id = c.id
                          WHERE p.id = :id";
            $stmt = $this->pdo->prepare($sqlPedido);
            $stmt->execute([':id' => $pedidoId]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) return null;

            // 2. Busca os itens do pedido
            // Nota: Usa 'pedido_items' (com M), conforme corrigimos antes
            $sqlItens = "SELECT pi.quantidade, pi.preco_unitario, pr.nome AS produto_nome
                         FROM pedido_items pi
                         JOIN produtos pr ON pi.produto_id = pr.id
                         WHERE pi.pedido_id = :id";
            $stmtItens = $this->pdo->prepare($sqlItens);
            $stmtItens->execute([':id' => $pedidoId]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            $pedido['itens'] = $itens;
            return $pedido;

        } catch (PDOException $e) {
            error_log("Erro ao buscar recibo: " . $e->getMessage());
            return null;
        }
    }

    // --- MÉTODOS DO DASHBOARD ---

    public function getFaturamentoHoje() {
        $sql = "SELECT SUM(valor_total) FROM pedidos WHERE DATE(data_pedido) = CURDATE()";
        return (float) $this->pdo->query($sql)->fetchColumn();
    }

    public function getTotalPedidosHoje() {
        $sql = "SELECT COUNT(id) FROM pedidos WHERE DATE(data_pedido) = CURDATE()";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function getUltimasVendas($limite = 5) {
        $sql = "SELECT p.id, c.nome as cliente, p.valor_total, p.data_pedido 
                FROM pedidos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id 
                ORDER BY p.data_pedido DESC LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVendasUltimos7Dias() {
        $sql = "SELECT DATE(data_pedido) as data, SUM(valor_total) as total 
                FROM pedidos 
                WHERE data_pedido >= DATE(NOW()) - INTERVAL 7 DAY 
                GROUP BY DATE(data_pedido) 
                ORDER BY data ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos() {
        $sql = "SELECT p.*, c.nome as cliente_nome 
                FROM pedidos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id 
                ORDER BY p.data_pedido DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>