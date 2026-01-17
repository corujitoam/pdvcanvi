<?php
require_once __DIR__ . '/Conexao.php';

class Mesa {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getConexao();
    }

    // Lista todas as mesas (para o grid)
    public function listar() {
        // Ordena pelo número da mesa
        $stmt = $this->pdo->query("SELECT * FROM mesas ORDER BY numero ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca dados de UMA mesa específica
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM mesas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lista os produtos adicionados numa mesa
    public function listarItens($mesaId) {
        $sql = "SELECT 
                    mi.id as item_id,
                    mi.quantidade,
                    mi.produto_id,
                    p.nome,
                    p.preco
                FROM mesa_items mi
                JOIN produtos p ON mi.produto_id = p.id
                WHERE mi.mesa_id = :mesa_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mesa_id' => $mesaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Adiciona um produto à mesa
    public function adicionarItem($mesaId, $produtoId, $quantidade) {
        // Verifica se o item já existe para somar a quantidade (opcional, mas recomendado)
        $checkSql = "SELECT id, quantidade FROM mesa_items WHERE mesa_id = :mid AND produto_id = :pid";
        $stmtCheck = $this->pdo->prepare($checkSql);
        $stmtCheck->execute([':mid' => $mesaId, ':pid' => $produtoId]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            // Atualiza quantidade
            $novaQtd = $existente['quantidade'] + $quantidade;
            $sql = "UPDATE mesa_items SET quantidade = :qtd WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':qtd' => $novaQtd, ':id' => $existente['id']]);
        } else {
            // Insere novo
            $sql = "INSERT INTO mesa_items (mesa_id, produto_id, quantidade) VALUES (:mid, :pid, :qtd)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mid' => $mesaId, ':pid' => $produtoId, ':qtd' => $quantidade]);
            return $this->pdo->lastInsertId();
        }
    }

    // Remove um item da mesa
    public function removerItem($itemId) {
        $stmt = $this->pdo->prepare("DELETE FROM mesa_items WHERE id = :id");
        return $stmt->execute([':id' => $itemId]);
    }

    // Muda o status (livre, ocupada, em_fechamento)
    public function atualizarStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE mesas SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // Salva o nome do cliente ou descrição na mesa
    public function atualizarDescricao($id, $descricao) {
        $stmt = $this->pdo->prepare("UPDATE mesas SET descricao = :desc WHERE id = :id");
        return $stmt->execute([':desc' => $descricao, ':id' => $id]);
    }
    
    // Limpa a mesa (usado quando a venda é finalizada no PDV - lógica futura)
    public function limparMesa($id) {
        $this->pdo->beginTransaction();
        try {
            // Remove itens
            $stmt1 = $this->pdo->prepare("DELETE FROM mesa_items WHERE mesa_id = :id");
            $stmt1->execute([':id' => $id]);
            
            // Reseta mesa
            $stmt2 = $this->pdo->prepare("UPDATE mesas SET status = 'livre', descricao = NULL WHERE id = :id");
            $stmt2->execute([':id' => $id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
?>