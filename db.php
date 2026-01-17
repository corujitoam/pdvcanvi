<?php
// db.php
// Conexão PDO + utilitários de migração
// - Em hospedagem compartilhada (cPanel), NÃO tentamos CREATE DATABASE.

require_once __DIR__ . '/config.php';

/**
 * Retorna uma conexão PDO única (singleton).
 */
function pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

/**
 * Executa a migração inicial se o sistema ainda não foi inicializado.
 *
 * Regra de detecção: se a tabela 'utilizadores' existe, consideramos inicializado.
 */
function ensure_migrated(): void {
    try {
        $db = pdo();

        $exists = $db->query("SHOW TABLES LIKE 'utilizadores'")->fetchColumn();
        if ($exists) return;

        $sqlFile = __DIR__ . '/migrations/001_init.sql';
        if (!file_exists($sqlFile)) {
            error_log('[Quiosque] Migration não encontrada: ' . $sqlFile);
            return;
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false || trim($sql) === '') {
            error_log('[Quiosque] Migration vazia ou não foi possível ler: ' . $sqlFile);
            return;
        }

        $db->exec($sql);

    } catch (Throwable $e) {
        // Não quebra a página com detalhes sensíveis
        error_log('[Quiosque] Erro ao migrar: ' . $e->getMessage());
    }
}
