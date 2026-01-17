<?php

/**
 * Classe Fornecedor
 * * Responsável por toda a lógica de negócio e interação
 * com a tabela `fornecedores` na base de dados.
 */
class Fornecedor
{
    /**
     * @var PDO A instância da conexão com a base de dados.
     */
    private $pdo;

    /**
     * Construtor da classe.
     * Pega a conexão PDO global, assim como a classe Produto faz.
     */
    public function __construct()
    {
        if (isset($GLOBALS['pdo'])) {
            $this->pdo = $GLOBALS['pdo'];
        } else {
            // Lança uma exceção se a conexão não for encontrada.
            // O bootstrap.php deve sempre garantir que $GLOBALS['pdo'] exista.
            throw new Exception("Falha ao obter conexão PDO na classe Fornecedor.");
        }
    }

    /**
     * Lista todos os fornecedores ativos.
     * Usado para preencher a lista de sugestões no formulário de produto.
     * @return array
     */
    public function listarAtivos(): array
    {
        // Seleciona apenas fornecedores ativos e ordena por nome
        $sql = "SELECT id, nome FROM fornecedores WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista TODOS os fornecedores para a gestão (modal).
     * @return array
     */
    public function listarTodos(): array
    {
        $sql = "SELECT * FROM fornecedores ORDER BY nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um único fornecedor pelo seu ID.
     * @param int $id O ID do fornecedor.
     * @return array|false
     */
    public function buscar(int $id)
    {
        $sql = "SELECT * FROM fornecedores WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Salva um fornecedor (cria um novo ou atualiza um existente).
     * @param array $dados Os dados vindos do formulário.
     * @return bool
     */
    public function salvar(array $dados): bool
    {
        // Validação básica
        if (empty(trim($dados['nome']))) {
            throw new Exception("O nome do fornecedor é obrigatório.");
        }

        // Trata os dados para evitar campos vazios no lugar de nulos
        $cpfCnpj = trim($dados['cpf_cnpj'] ?? '') ?: null;
        $telefone = trim($dados['telefone'] ?? '') ?: null;
        $email = trim($dados['email'] ?? '') ?: null;
        $endereco = trim($dados['endereco'] ?? '') ?: null;
        // Garante que 'ativo' seja 0 ou 1
        $ativo = isset($dados['ativo']) ? 1 : 0; 

        if (isset($dados['id']) && !empty($dados['id'])) {
            // --- ATUALIZAR FORNECEDOR ---
            $sql = "UPDATE fornecedores 
                    SET nome = ?, cpf_cnpj = ?, telefone = ?, email = ?, endereco = ?, ativo = ? 
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                trim($dados['nome']),
                $cpfCnpj,
                $telefone,
                $email,
                $endereco,
                $ativo,
                $dados['id']
            ]);
        } else {
            // --- CRIAR NOVO FORNECEDOR ---
            $sql = "INSERT INTO fornecedores (nome, cpf_cnpj, telefone, email, endereco, ativo) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                trim($dados['nome']),
                $cpfCnpj,
                $telefone,
                $email,
                $endereco,
                $ativo // Ao criar, 'ativo' vem do checkbox
            ]);
        }
    }

    /**
     * Exclui um fornecedor da base de dados.
     * NOTA: Graças ao "ON DELETE SET NULL" que definimos,
     * os produtos não serão excluídos, apenas desvinculados.
     * * @param int $id O ID do fornecedor a ser excluído.
     * @return bool
     */
    public function excluir(int $id): bool
    {
        $sql = "DELETE FROM fornecedores WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}