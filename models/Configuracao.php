<?php
require_once __DIR__ . '/Conexao.php';

class Configuracao {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getConexao();
    }

    // Carrega todas as configurações num array associativo
    public function carregarConfiguracoes() {
        $stmt = $this->pdo->query("SELECT chave, valor FROM configuracoes");
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $config = [];
        foreach ($resultados as $linha) {
            $config[$linha['chave']] = $linha['valor'];
        }
        return $config;
    }

    // Salva uma única configuração
    public function salvarConfiguracao($chave, $valor) {
        // Usa INSERT ... ON DUPLICATE KEY UPDATE para criar se não existir
        $sql = "INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor) 
                ON DUPLICATE KEY UPDATE valor = :valor_up";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'chave' => $chave, 
            'valor' => $valor,
            'valor_up' => $valor
        ]);
    }

    // Salva múltiplas configurações de uma vez
    public function salvarMultiplas($dados) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($dados as $chave => $valor) {
                // Ignora o campo 'acao' que vem do formulário
                if ($chave === 'acao') continue;
                
                $this->salvarConfiguracao($chave, $valor);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
?>