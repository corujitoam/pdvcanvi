<?php
require_once __DIR__ . '/Conexao.php';

class Cliente {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getConexao();
    }

    public function listar($termo = '') {
        $sql = "SELECT * FROM clientes WHERE ativo = 1";
        $params = [];

        if (!empty($termo)) {
            $sql .= " AND (nome LIKE :termo OR cpf LIKE :termo OR email LIKE :termo)";
            $params[':termo'] = "%{$termo}%";
        }

        $sql .= " ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar($dados) {
        // Prepara os parâmetros explicitamente para evitar erros com campos extras (ex: 'acao')
        $params = [
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'] ?? null,
            'email' => $dados['email'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
            'logradouro' => $dados['logradouro'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'numero' => $dados['numero'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'uf' => $dados['uf'] ?? null
        ];

        if (!empty($dados['id'])) {
            // ATUALIZAR
            $sql = "UPDATE clientes SET 
                    nome = :nome, cpf = :cpf, email = :email, telefone = :telefone,
                    logradouro = :logradouro, cep = :cep, numero = :numero, 
                    bairro = :bairro, cidade = :cidade, uf = :uf
                    WHERE id = :id";
            
            $params['id'] = $dados['id'];
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } else {
            // INSERIR (Novo)
            $sql = "INSERT INTO clientes (nome, cpf, email, telefone, logradouro, cep, numero, bairro, cidade, uf, ativo, criado_em) 
                    VALUES (:nome, :cpf, :email, :telefone, :logradouro, :cep, :numero, :bairro, :cidade, :uf, 1, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        }
    }

    public function excluir($id) {
        // Soft delete (apenas desativa)
        $stmt = $this->pdo->prepare("UPDATE clientes SET ativo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // --- NOVO MÉTODO PARA O DASHBOARD ---
    public function getTotalClientesHoje() {
        // Conta clientes criados hoje, ignorando o Consumidor Padrão (id 1) e inativos
        $sql = "SELECT COUNT(id) FROM clientes WHERE DATE(criado_em) = CURDATE() AND id > 1 AND ativo = 1";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }
}
?>