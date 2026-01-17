<?php
require_once __DIR__ . '/Conexao.php';

class Produto {
    private $pdo;

    public function __construct() {
        // Padronização: Usa sempre a classe Conexao
        $this->pdo = Conexao::getConexao();
    }

    /**
     * Lista produtos com suporte a filtros (Ativo, Termo de Pesquisa)
     * @param array $filtros Array opcional ['ativo' => 1, 'termo' => 'cola']
     */
    public function listar($filtros = []) {
        $sql = "SELECT p.*, f.nome as fornecedor_nome 
                FROM produtos p 
                LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
                WHERE 1=1";
        
        $params = [];

        // Filtro por Status (Ativo/Inativo)
        if (isset($filtros['ativo'])) {
            $sql .= " AND p.ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }

        // Filtro por Termo de Pesquisa (Nome)
        if (!empty($filtros['termo'])) {
            $sql .= " AND p.nome LIKE :termo";
            $params[':termo'] = '%' . $filtros['termo'] . '%';
        }

        $sql .= " ORDER BY p.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $sql = "SELECT p.*, f.nome as fornecedor_nome 
                FROM produtos p 
                LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
                WHERE p.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Mantém compatibilidade com códigos antigos que chamem 'buscar'
    public function buscar($id) {
        return $this->buscarPorId($id);
    }

    public function salvar($dados) {
        // Validação básica
        if (empty($dados['nome']) || !isset($dados['preco']) || !isset($dados['estoque'])) {
             throw new Exception("Nome, Preço e Estoque são obrigatórios.");
        }

        // Tratamento do Fornecedor
        $fornecedorId = (!empty($dados['fornecedor_id']) && is_numeric($dados['fornecedor_id']) && $dados['fornecedor_id'] > 0)
                        ? (int)$dados['fornecedor_id']
                        : null;

        $id = $dados['id'] ?? null;
        
        try {
            if ($id) {
                // Atualizar
                $sql = "UPDATE produtos SET 
                        nome = :nome, 
                        descricao = :descricao, 
                        preco = :preco, 
                        estoque = :estoque, 
                        ativo = :ativo,
                        imagem = :imagem,
                        fornecedor_id = :fornecedor_id
                        WHERE id = :id";
                
                // Se a imagem for null (não enviada), mantém a antiga? 
                // Neste caso, assumimos que o controller já tratou isso ou envia a string antiga.
                // Se vier null do post, o ideal é não sobrescrever se não quiser apagar.
                // Mas seguindo a lógica padrão de CRUD simples:
                
                $params = [
                    'nome' => trim($dados['nome']),
                    'descricao' => trim($dados['descricao'] ?? '') ?: null,
                    'preco' => (float)$dados['preco'],
                    'estoque' => (int)$dados['estoque'],
                    'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
                    'imagem' => $dados['imagem'] ?? null,
                    'fornecedor_id' => $fornecedorId,
                    'id' => $id
                ];

                // Ajuste fino: Se imagem for null, não atualiza esse campo (para não apagar a foto existente ao editar só o nome)
                if ($dados['imagem'] === null) {
                    $sql = str_replace("imagem = :imagem,", "", $sql); // Remove da query
                    unset($params['imagem']); // Remove dos parametros
                }

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);

            } else {
                // Criar Novo
                $sql = "INSERT INTO produtos (nome, descricao, preco, estoque, fornecedor_id, imagem, ativo) 
                        VALUES (:nome, :descricao, :preco, :estoque, :fornecedor_id, :imagem, :ativo)";
                
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    'nome' => trim($dados['nome']),
                    'descricao' => trim($dados['descricao'] ?? '') ?: null,
                    'preco' => (float)$dados['preco'],
                    'estoque' => (int)$dados['estoque'],
                    'fornecedor_id' => $fornecedorId,
                    'imagem' => $dados['imagem'] ?? null,
                    'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
                ]);
            }
        } catch (PDOException $e) {
            error_log("Erro ao salvar produto: " . $e->getMessage());
            throw new Exception("Erro ao salvar produto no banco.");
        }
    }

    public function excluir($id) {
        // Soft delete
        $stmt = $this->pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    // --- MÉTODOS PARA O DASHBOARD ---

    public function getProdutosComEstoqueBaixo($limiteItens = 5) {
        try {
            $stmtConf = $this->pdo->query("SELECT valor FROM configuracoes WHERE chave = 'alerta_estoque_baixo'");
            $alerta = (int)($stmtConf->fetchColumn() ?: 5);
        } catch (Exception $e) { $alerta = 5; }

        $sql = "SELECT nome, estoque FROM produtos 
                WHERE estoque <= :alerta AND ativo = 1 
                ORDER BY estoque ASC LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':alerta', $alerta, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int)$limiteItens, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProdutosMaisVendidos($limite = 5) {
        $sql = "SELECT p.nome, SUM(pi.quantidade) as total_qtd
                FROM pedido_items pi
                JOIN produtos p ON pi.produto_id = p.id
                GROUP BY pi.produto_id, p.nome
                ORDER BY total_qtd DESC
                LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Método auxiliar para abater estoque
    public function atualizarEstoque($id, $quantidadeVendida) {
        $stmt = $this->pdo->prepare("UPDATE produtos SET estoque = estoque - :qtd WHERE id = :id");
        return $stmt->execute([':qtd' => $quantidadeVendida, ':id' => $id]);
    }
}
?>