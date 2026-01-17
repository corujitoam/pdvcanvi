<?php
require_once __DIR__ . '/Conexao.php';

class Utilizador {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getConexao();
    }

    // Lista utilizadores
    public function listar($termo = '') {
        $sql = "SELECT id, nome, login, cargo, ativo FROM utilizadores";
        $params = [];
        if (!empty($termo)) {
            $sql .= " WHERE nome LIKE :termo OR login LIKE :termo";
            $params[':termo'] = "%{$termo}%";
        }
        $sql .= " ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca por ID com Permissões
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nome, login, cargo, ativo FROM utilizadores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $stmtPerm = $this->pdo->prepare("SELECT permissao_id FROM utilizador_permissoes WHERE utilizador_id = :id");
            $stmtPerm->execute([':id' => $id]);
            $user['permissoes'] = $stmtPerm->fetchAll(PDO::FETCH_COLUMN);
        }
        return $user;
    }

    // Lista todas as permissões
    public function listarTodasPermissoes() {
        $stmt = $this->pdo->query("SELECT id, nome_permissao, descricao FROM permissoes ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Salvar (Criar/Editar)
    public function salvar($dados) {
        try {
            $this->pdo->beginTransaction();

            $id = $dados['id'] ?? null;
            $nome = $dados['nome'];
            $login = $dados['login'];
            $cargo = $dados['cargo'] ?? '';
            $ativo = isset($dados['ativo']) ? 1 : 0;
            $permissoes = $dados['permissoes'] ?? [];

            if ($id) {
                $sql = "UPDATE utilizadores SET nome = :nome, login = :login, cargo = :cargo, ativo = :ativo WHERE id = :id";
                $params = ['nome' => $nome, 'login' => $login, 'cargo' => $cargo, 'ativo' => $ativo, 'id' => $id];
                
                if (!empty($dados['senha'])) {
                    $sql = "UPDATE utilizadores SET nome = :nome, login = :login, cargo = :cargo, ativo = :ativo, senha_hash = :senha WHERE id = :id";
                    $params['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
                }
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                if (empty($dados['senha'])) throw new Exception("Senha obrigatória.");
                $sql = "INSERT INTO utilizadores (nome, login, senha_hash, cargo, ativo) VALUES (:nome, :login, :senha, :cargo, :ativo)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'nome' => $nome, 'login' => $login, 
                    'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                    'cargo' => $cargo, 'ativo' => $ativo
                ]);
                $id = $this->pdo->lastInsertId();
            }

            if ($id) {
                $stmtDel = $this->pdo->prepare("DELETE FROM utilizador_permissoes WHERE utilizador_id = :id");
                $stmtDel->execute([':id' => $id]);

                if (!empty($permissoes)) {
                    $sqlPerm = "INSERT INTO utilizador_permissoes (utilizador_id, permissao_id) VALUES (:uid, :pid)";
                    $stmtPerm = $this->pdo->prepare($sqlPerm);
                    foreach ($permissoes as $pid) {
                        $stmtPerm->execute([':uid' => $id, ':pid' => $pid]);
                    }
                }
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Excluir
    public function excluir($id) {
        if ($id == 1) throw new Exception("Não é possível excluir o Administrador principal.");
        $stmt = $this->pdo->prepare("DELETE FROM utilizadores WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // --- AQUI ESTÁ A FUNÇÃO QUE FALTAVA PARA O LOGIN FUNCIONAR ---
    public function verificarLogin($login, $senha) {
        $stmt = $this->pdo->prepare("SELECT id, nome, login, senha_hash, cargo, ativo FROM utilizadores WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se existe, se está ativo e se a senha bate
        if ($user && $user['ativo'] == 1 && password_verify($senha, $user['senha_hash'])) {
            unset($user['senha_hash']); // Remove a senha por segurança
            
            // Busca permissões para guardar na sessão
            $stmtPerm = $this->pdo->prepare("SELECT p.nome_permissao FROM utilizador_permissoes up JOIN permissoes p ON up.permissao_id = p.id WHERE up.utilizador_id = :id");
            $stmtPerm->execute([':id' => $user['id']]);
            $user['permissoes'] = $stmtPerm->fetchAll(PDO::FETCH_COLUMN);
            
            return $user;
        }
        return false;
    }
}
?>