<?php

require_once 'Conexao.php';

/**
 * Classe responsável por gerar os dados para todos os relatórios.
 */
class Relatorio {

    private $db;

    public function __construct() {
        $this->db = Conexao::getConexao();
    }

    /**
     * MÉTODO 1: Vendas por Período
     */
    public function gerarVendasPorPeriodo($dataInicio, $dataFim) {
        try {
            // Ajusta as horas para pegar o dia completo (00:00 até 23:59)
            $inicio = $dataInicio . ' 00:00:00';
            $fim = $dataFim . ' 23:59:59';

            $sql = "SELECT 
                        p.id, 
                        p.data_pedido, 
                        p.valor_total, 
                        p.forma_pagamento,
                        c.nome as cliente_nome
                    FROM pedidos p
                    LEFT JOIN clientes c ON p.cliente_id = c.id
                    WHERE p.data_pedido BETWEEN :inicio AND :fim
                    ORDER BY p.data_pedido DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cálculos de resumo
            $faturamentoTotal = 0;
            $totalPedidos = count($pedidos);
            foreach ($pedidos as $pedido) {
                $faturamentoTotal += (float) $pedido['valor_total'];
            }
            $ticketMedio = ($totalPedidos > 0) ? ($faturamentoTotal / $totalPedidos) : 0;

            return [
                'resumo' => [
                    'faturamentoTotal' => $faturamentoTotal,
                    'totalPedidos' => $totalPedidos,
                    'ticketMedio' => $ticketMedio
                ],
                'pedidos' => $pedidos
            ];

        } catch (PDOException $e) {
            error_log("Erro Relatorio::gerarVendasPorPeriodo: " . $e->getMessage());
            throw new Exception("Erro ao gerar relatório de vendas.");
        }
    }

    /**
     * MÉTODO 2: Fechamento Simples (Por Pagamento)
     */
    public function gerarFechamentoSimples($dataInicio, $dataFim) {
        try {
            $inicio = $dataInicio . ' 00:00:00';
            $fim = $dataFim . ' 23:59:59';

            $sql = "SELECT 
                        forma_pagamento, 
                        COUNT(id) as quantidade_pedidos, 
                        SUM(valor_total) as valor_total
                    FROM pedidos
                    WHERE data_pedido BETWEEN :inicio AND :fim
                    GROUP BY forma_pagamento
                    ORDER BY valor_total DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            $detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Totais Gerais
            $faturamentoTotal = 0;
            $totalPedidos = 0;
            foreach ($detalhes as $pgto) {
                $faturamentoTotal += (float) $pgto['valor_total'];
                $totalPedidos += (int) $pgto['quantidade_pedidos'];
            }
            
            return [
                'total_geral' => [
                    'faturamento_total' => $faturamentoTotal,
                    'total_pedidos' => $totalPedidos
                ],
                'detalhes_pagamento' => $detalhes
            ];

        } catch (PDOException $e) {
            error_log("Erro Relatorio::gerarFechamentoSimples: " . $e->getMessage());
            throw new Exception("Erro ao gerar fechamento simples.");
        }
    }

    /**
     * MÉTODO 3: Ranking de Produtos (CORRIGIDO)
     * Correção: Alterado de 'pedido_itens' para 'pedido_items'
     */
    public function gerarFechamentoDetalhado($dataInicio, $dataFim) {
        try {
            $inicio = $dataInicio . ' 00:00:00';
            $fim = $dataFim . ' 23:59:59';

            // ATENÇÃO: A tabela no banco é 'pedido_items' (com M), não 'pedido_itens'
            $sql = "SELECT 
                        i.produto_id, 
                        p.nome as produto_nome, 
                        SUM(i.quantidade) as total_quantidade, 
                        SUM(i.preco_unitario * i.quantidade) as valor_total_vendido
                    FROM pedido_items i
                    JOIN produtos p ON i.produto_id = p.id
                    JOIN pedidos ped ON i.pedido_id = ped.id
                    WHERE ped.data_pedido BETWEEN :inicio AND :fim
                    GROUP BY i.produto_id, p.nome
                    ORDER BY total_quantidade DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            
            return [
                'produtos_vendidos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {
            error_log("Erro Relatorio::gerarFechamentoDetalhado: " . $e->getMessage());
            // Tenta dar uma dica melhor no erro
            throw new Exception("Erro ao consultar produtos: " . $e->getMessage());
        }
    }

    /**
     * MÉTODO 4: Relatório de Sessões de Caixa (NOVO)
     * Este método faltava e é necessário para a última aba do relatório
     */
    public function gerarRelatorioCaixa($dataInicio, $dataFim) {
        try {
            $inicio = $dataInicio . ' 00:00:00';
            $fim = $dataFim . ' 23:59:59';

            $sql = "SELECT 
                        c.id,
                        u.nome as operador_abertura,
                        c.data_abertura,
                        c.data_fechamento,
                        c.valor_abertura,
                        c.total_apurado_sistema,
                        c.diferenca,
                        c.status
                    FROM caixa_sessoes c
                    LEFT JOIN utilizadores u ON c.id_utilizador_abertura = u.id
                    WHERE c.data_abertura BETWEEN :inicio AND :fim
                    ORDER BY c.data_abertura DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            
            return [
                'sessoes_caixa' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {
            error_log("Erro Relatorio::gerarRelatorioCaixa: " . $e->getMessage());
            throw new Exception("Erro ao gerar relatório de caixa.");
        }
    }

}