<?php
class Conexao
{
    private static $instance = null;

    // O construtor é privado para impedir que criem "new Conexao()" fora daqui
    private function __construct() {}

    public static function getConexao(): PDO
    {
        // 1) Preferir PDO já criado pelo bootstrap (para manter 1 única conexão)
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }

        // 2) Se existir a função pdo() (db.php), use ela
        if (function_exists('pdo')) {
            $pdo = pdo();
            $GLOBALS['pdo'] = $pdo;
            return $pdo;
        }

        // 3) Fallback: cria a conexão aqui (mantendo compatibilidade)
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        try {
            // As constantes (DB_HOST, etc.) vêm do config.php/bootstrap.php
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            $GLOBALS['pdo'] = self::$instance;
            return self::$instance;

        } catch (PDOException $e) {
            // Evitar vazar detalhes sensíveis
            error_log('[Quiosque] Erro de conexão: ' . $e->getMessage());
            die('Erro de conexão com o banco de dados.');
        }
    }
}
