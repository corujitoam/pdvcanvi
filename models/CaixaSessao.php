<?php
// (Este ficheiro está em /models/)

// Sobe um nível (../) para encontrar o bootstrap
require_once __DIR__ . '/../bootstrap.php';
// NOTA: O Conexao.php é carregado pelo bootstrap.php

class CaixaSessao {
    private $db;
    public function __construct() {
        $this->db = Conexao::getConexao();
    }

    // --- (Resto do código do CaixaSessao.php - Inalterado) ---
    
    public function abrir($utilizadorId, $valorAbertura) {
        try {
            $sqlCheck = "SELECT id FROM caixa_sessoes WHERE status = 'ABERTO'";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                throw new Exception("Já existe uma sessão de caixa aberta. Não é possível abrir outra.");
            }
            $sql = "INSERT INTO caixa_sessoes (id_utilizador_abertura, data_abertura, valor_abertura, status, total_suprimentos, total_sangrias, total_apurado_sistema, total_contado_dinheiro, total_contado_cartao, total_contado_outros, diferenca) VALUES (:uid, NOW(), :valor, 'ABERTO', 0, 0, 0, 0, 0, 0, 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':uid', $utilizadorId, PDO::PARAM_INT);
            $stmt->bindParam(':valor', $valorAbertura, PDO::PARAM_STR); 
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            } else {
                throw new Exception("Falha ao executar o INSERT para abrir a sessão.");
            }
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao abrir sessão: " . $e->getMessage());
        }
    }
    public function fechar($sessaoId, $utilizadorFechamentoId, $valores) {
        try {
            $sql = "UPDATE caixa_sessoes SET data_fechamento = NOW(), status = 'FECHADO', id_utilizador_fechamento = :uid_fechamento, total_apurado_sistema = :total_apurado, total_contado_dinheiro = :contado_dinheiro, total_contado_cartao = :contado_cartao, total_contado_outros = :contado_outros, diferenca = :diferenca, observacoes = :obs WHERE id = :sessao_id AND status = 'ABERTO'"; 
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':uid_fechamento', $utilizadorFechamentoId, PDO::PARAM_INT);
            $stmt->bindParam(':total_apurado', $valores['total_apurado_sistema'], PDO::PARAM_STR);
            $stmt->bindParam(':contado_dinheiro', $valores['contado_dinheiro'], PDO::PARAM_STR);
            $stmt->bindParam(':contado_cartao', $valores['contado_cartao'], PDO::PARAM_STR);
            $stmt->bindParam(':contado_outros', $valores['contado_outros'], PDO::PARAM_STR);
            $stmt->bindParam(':diferenca', $valores['diferenca'], PDO::PARAM_STR);
            $stmt->bindParam(':obs', $valores['observacoes'], PDO::PARAM_STR);
            $stmt->bindParam(':sessao_id', $sessaoId, PDO::PARAM_INT);
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            } else {
                throw new Exception("Falha ao executar o UPDATE para fechar a sessão.");
            }
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao fechar sessão: " . $e->getMessage());
        }
    }
    public function atualizarTotaisMovimentacao($sessaoId, $valor, $tipo) {
        try {
            $coluna = ($tipo === 'SUPRIMENTO') ? 'total_suprimentos' : 'total_sangrias';
            $sql = "UPDATE caixa_sessoes SET $coluna = $coluna + :valor WHERE id = :sessao_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
            $stmt->bindParam(':sessao_id', $sessaoId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                 throw new Exception("Falha ao executar o UPDATE para atualizar totais.");
            }
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao atualizar totais da sessão: " . $e->getMessage());
        }
    }
    public function listarFechadas() {
        try {
            $sql = "SELECT id, data_abertura, data_fechamento FROM caixa_sessoes WHERE status = 'FECHADO' ORDER BY data_fechamento DESC LIMIT 100";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao listar sessões fechadas: " . $e->getMessage());
        }
    }
    public function buscarPorId($id) {
        try {
            $sql = "SELECT * FROM caixa_sessoes WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$resultado) { throw new Exception("Nenhuma sessão encontrada com o ID: $id"); }
            return $resultado;
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao buscar sessão por ID: " . $e->getMessage());
        }
    }
    public function buscarDetalhesSessao($id) {
         try {
            $sql = "SELECT s.*, u_abertura.nome as utilizador_nome_abertura, u_fechamento.nome as utilizador_nome_fechamento FROM caixa_sessoes s LEFT JOIN utilizadores u_abertura ON s.id_utilizador_abertura = u_abertura.id LEFT JOIN utilizadores u_fechamento ON s.id_utilizador_fechamento = u_fechamento.id WHERE s.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$resultado) { throw new Exception("Nenhum detalhe de sessão encontrado para o ID: $id"); }
            return $resultado;
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao buscar detalhes da sessão: " . $e->getMessage());
        }
    }
    public function buscarSessaoAberta() {
        try {
            $sql = "SELECT * FROM caixa_sessoes WHERE status = 'ABERTO' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erro de SQL ao buscar sessão aberta: " . $e->getMessage());
        }
    }
}