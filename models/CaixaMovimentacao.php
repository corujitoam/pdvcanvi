<?php
// (Este ficheiro está em /models/)

// Sobe um nível (../) para encontrar o bootstrap
require_once __DIR__ . '/../bootstrap.php';
// --- CORREÇÃO AQUI ---
// Carrega o 'CaixaSessao.php' que está NO MESMO DIRETÓRIO (models/)
require_once __DIR__ . '/CaixaSessao.php'; 

class CaixaMovimentacao {
    private $db;
    public function __construct() {
        $this->db = Conexao::getConexao();
    }

    public function registrarMovimentacao($sessaoId, $utilizadorId, $tipo, $valor, $motivo) {
        $this->db->beginTransaction();
        try {
            // 1. Insere na tabela 'caixa_movimentacoes'
            $sql = "INSERT INTO caixa_movimentacoes 
                        (id_sessao, id_utilizador, tipo, valor, motivo, data_hora)
                    VALUES
                        (:sessao_id, :uid, :tipo, :valor, :motivo, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':sessao_id', $sessaoId, PDO::PARAM_INT);
            $stmt->bindParam(':uid', $utilizadorId, PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR); 
            $stmt->bindParam(':valor', $valor, PDO::PARAM_STR); 
            $stmt->bindParam(':motivo', $motivo, PDO::PARAM_STR);
            $stmt->execute();
            $novoId = $this->db->lastInsertId();

            // 2. Atualiza os totais na tabela 'caixa_sessoes'
            $sessaoModel = new CaixaSessao();
            $sessaoModel->atualizarTotaisMovimentacao($sessaoId, $valor, $tipo);
            
            $this->db->commit();
            return $novoId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erro de SQL ao registar movimentação: " . $e->getMessage());
        }
    }
}