<?php
// bootstrap.php
// Inicialização do sistema (sessão, timezone, autoload) + DB unificado (config.php + db.php)

// --- INICIALIZAÇÃO DA APLICAÇÃO ---
ob_start();

// Cookies de sessão um pouco mais seguros (mantém compatibilidade)
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- ERROS (em produção, não exibimos na tela) ---
error_reporting(E_ALL);
if (defined('APP_DEBUG') && APP_DEBUG) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

// --- CAMINHOS ---
define('ROOT_PATH', __DIR__);

// --- CONFIG E DB (SEM credenciais hardcoded aqui) ---
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/db.php';

// --- IDIOMA / TIMEZONE ---
mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Manaus');

// --- AUTOLOADER DE CLASSES ---
spl_autoload_register(function ($className) {
    $file = ROOT_PATH . '/models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// --- HELPERS DE SCHEMA ---
function _qi_column_exists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME = :t
              AND COLUMN_NAME = :c";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['db' => DB_NAME, 't' => $table, 'c' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function _qi_constraint_exists(PDO $pdo, string $table, string $constraintName): bool {
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = :db
              AND TABLE_NAME = :t
              AND CONSTRAINT_NAME = :cn";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['db' => DB_NAME, 't' => $table, 'cn' => $constraintName]);
    return (int)$stmt->fetchColumn() > 0;
}

function _qi_index_exists(PDO $pdo, string $table, string $indexName): bool {
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME = :t
              AND INDEX_NAME = :i";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['db' => DB_NAME, 't' => $table, 'i' => $indexName]);
    return (int)$stmt->fetchColumn() > 0;
}

function _qi_safe_exec(PDO $pdo, string $sql): void {
    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        error_log('[Quiosque] SQL falhou: ' . $e->getMessage() . ' | SQL: ' . $sql);
        // Não mata o sistema aqui: algumas instalações antigas podem ter diferenças.
    }
}

function _qi_ensure_schema(PDO $pdo): void {
    // Cria tabelas principais (IF NOT EXISTS)
    $creates = [
        // Clientes
        "CREATE TABLE IF NOT EXISTS `clientes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `cpf` VARCHAR(20) NULL,
            `email` VARCHAR(255) NULL UNIQUE,
            `telefone` VARCHAR(30) NULL,
            `logradouro` VARCHAR(255) NULL,
            `cep` VARCHAR(10) NULL,
            `numero` VARCHAR(20) NULL,
            `bairro` VARCHAR(100) NULL,
            `cidade` VARCHAR(100) NULL,
            `uf` VARCHAR(2) NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Produtos
        "CREATE TABLE IF NOT EXISTS `produtos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `descricao` TEXT NULL,
            `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `estoque` INT NOT NULL DEFAULT 0,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `imagem` VARCHAR(255) NULL,
            `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Utilizadores
        "CREATE TABLE IF NOT EXISTS `utilizadores` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(100) NOT NULL,
            `login` VARCHAR(50) NOT NULL UNIQUE,
            `senha_hash` VARCHAR(255) NOT NULL,
            `cargo` VARCHAR(50) NULL,
            `ativo` BOOLEAN NOT NULL DEFAULT TRUE,
            `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Permissões
        "CREATE TABLE IF NOT EXISTS `permissoes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome_permissao` VARCHAR(50) NOT NULL UNIQUE,
            `descricao` VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS `utilizador_permissoes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `utilizador_id` INT NOT NULL,
            `permissao_id` INT NOT NULL,
            UNIQUE KEY `uniq_user_perm` (`utilizador_id`, `permissao_id`),
            CONSTRAINT `fk_up_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_up_perm` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Pedidos
        "CREATE TABLE IF NOT EXISTS `pedidos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `cliente_id` INT NULL,
            `data_pedido` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `forma_pagamento` VARCHAR(50) DEFAULT 'Não definido',
            CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS `pedido_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `pedido_id` INT NULL,
            `produto_id` INT NULL,
            `quantidade` INT NOT NULL DEFAULT 1,
            `preco_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            CONSTRAINT `fk_pedido_items_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pedido_items_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Configurações
        "CREATE TABLE IF NOT EXISTS `configuracoes` (
            `chave` VARCHAR(50) NOT NULL PRIMARY KEY,
            `valor` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Fornecedores
        "CREATE TABLE IF NOT EXISTS `fornecedores` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `cpf_cnpj` VARCHAR(20) NULL,
            `telefone` VARCHAR(30) NULL,
            `email` VARCHAR(255) NULL,
            `endereco` VARCHAR(255) NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Caixa
        "CREATE TABLE IF NOT EXISTS `caixa_sessoes` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `id_utilizador_abertura` INT NOT NULL,
            `data_abertura` DATETIME NOT NULL,
            `valor_abertura` DECIMAL(10,2) NOT NULL,
            `total_suprimentos` DECIMAL(10,2) NULL DEFAULT 0.00,
            `total_sangrias` DECIMAL(10,2) NULL DEFAULT 0.00,
            `id_utilizador_fechamento` INT NULL,
            `data_fechamento` DATETIME NULL,
            `total_apurado_sistema` DECIMAL(10,2) NULL,
            `total_contado_dinheiro` DECIMAL(10,2) NULL,
            `total_contado_cartao` DECIMAL(10,2) NULL,
            `total_contado_outros` DECIMAL(10,2) NULL,
            `diferenca` DECIMAL(10,2) NULL,
            `observacoes` TEXT NULL,
            `status` ENUM('ABERTO', 'FECHADO') NOT NULL DEFAULT 'ABERTO',
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_caixa_abertura` FOREIGN KEY (`id_utilizador_abertura`) REFERENCES `utilizadores`(`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_caixa_fechamento` FOREIGN KEY (`id_utilizador_fechamento`) REFERENCES `utilizadores`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS `caixa_movimentacoes` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `id_sessao` INT NOT NULL,
            `id_utilizador` INT NOT NULL,
            `tipo` ENUM('SUPRIMENTO', 'SANGRIA') NOT NULL,
            `valor` DECIMAL(10,2) NOT NULL,
            `motivo` VARCHAR(255) NULL,
            `data_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_mov_sessao` FOREIGN KEY (`id_sessao`) REFERENCES `caixa_sessoes`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_mov_user` FOREIGN KEY (`id_utilizador`) REFERENCES `utilizadores`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Mesas
        "CREATE TABLE IF NOT EXISTS `mesas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `numero` INT NOT NULL UNIQUE,
            `status` ENUM('livre', 'ocupada', 'em_fechamento', 'reatendimento') NOT NULL DEFAULT 'livre',
            `descricao` VARCHAR(100) DEFAULT NULL,
            `ativa` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Visível/Ativa, 0 = Oculta/Inativa'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS `mesa_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `mesa_id` INT NULL,
            `produto_id` INT NULL,
            `quantidade` INT NOT NULL DEFAULT 1,
            `adicionado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_mesa_items_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_mesa_items_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ];

    foreach ($creates as $sql) {
        _qi_safe_exec($pdo, $sql);
    }

    // --- Compatibilidade com instalações antigas (tabelas criadas "simples") ---
    // clientes.ativo é usado em vários pontos (ex.: cliente padrão do PDV)
    if (!_qi_column_exists($pdo, 'clientes', 'ativo')) {
        _qi_safe_exec($pdo, "ALTER TABLE `clientes` ADD COLUMN `ativo` TINYINT(1) NOT NULL DEFAULT 1");
    }
    // Campos opcionais (evita erros em telas que esperam esses campos)
    $clienteCols = [
        'cpf'        => "ALTER TABLE `clientes` ADD COLUMN `cpf` VARCHAR(20) NULL AFTER `nome`",
        'telefone'   => "ALTER TABLE `clientes` ADD COLUMN `telefone` VARCHAR(30) NULL",
        'logradouro' => "ALTER TABLE `clientes` ADD COLUMN `logradouro` VARCHAR(255) NULL",
        'cep'        => "ALTER TABLE `clientes` ADD COLUMN `cep` VARCHAR(10) NULL",
        'numero'     => "ALTER TABLE `clientes` ADD COLUMN `numero` VARCHAR(20) NULL",
        'bairro'     => "ALTER TABLE `clientes` ADD COLUMN `bairro` VARCHAR(100) NULL",
        'cidade'     => "ALTER TABLE `clientes` ADD COLUMN `cidade` VARCHAR(100) NULL",
        'uf'         => "ALTER TABLE `clientes` ADD COLUMN `uf` VARCHAR(2) NULL",
    ];
    foreach ($clienteCols as $col => $sql) {
        if (!_qi_column_exists($pdo, 'clientes', $col)) {
            _qi_safe_exec($pdo, $sql);
        }
    }

    // produtos: garantir colunas básicas usadas no PDV/cadastro
    $produtoCols = [
        'descricao' => "ALTER TABLE `produtos` ADD COLUMN `descricao` TEXT NULL AFTER `nome`",
        'ativo'     => "ALTER TABLE `produtos` ADD COLUMN `ativo` TINYINT(1) NOT NULL DEFAULT 1",
        'imagem'    => "ALTER TABLE `produtos` ADD COLUMN `imagem` VARCHAR(255) NULL",
    ];
    foreach ($produtoCols as $col => $sql) {
        if (!_qi_column_exists($pdo, 'produtos', $col)) {
            _qi_safe_exec($pdo, $sql);
        }
    }

    // Índice para mesas.ativa
    if (!_qi_index_exists($pdo, 'mesas', 'idx_ativa')) {
        _qi_safe_exec($pdo, "ALTER TABLE `mesas` ADD INDEX `idx_ativa` (`ativa`)");
    }

    // Colunas/relacionamentos opcionais (compat)
    if (!_qi_column_exists($pdo, 'produtos', 'fornecedor_id')) {
        _qi_safe_exec($pdo, "ALTER TABLE `produtos` ADD COLUMN `fornecedor_id` INT NULL DEFAULT NULL AFTER `imagem`");
    }
    if (!_qi_constraint_exists($pdo, 'produtos', 'fk_produto_fornecedor')) {
        _qi_safe_exec($pdo, "ALTER TABLE `produtos` ADD CONSTRAINT `fk_produto_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    // pedidos: algumas instalações antigas podem ter apenas id/cliente_id/criado_em
    // O sistema (PDV/relatórios/dashboard) espera: data_pedido, valor_total, forma_pagamento, id_sessao.
    $pedidoCols = [
        'data_pedido'     => "ALTER TABLE `pedidos` ADD COLUMN `data_pedido` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `cliente_id`",
        'valor_total'     => "ALTER TABLE `pedidos` ADD COLUMN `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `data_pedido`",
        'forma_pagamento' => "ALTER TABLE `pedidos` ADD COLUMN `forma_pagamento` VARCHAR(50) DEFAULT 'Não definido' AFTER `valor_total`",
    ];
    foreach ($pedidoCols as $col => $sql) {
        if (!_qi_column_exists($pdo, 'pedidos', $col)) {
            _qi_safe_exec($pdo, $sql);
        }
    }

    if (!_qi_column_exists($pdo, 'pedidos', 'id_sessao')) {
        _qi_safe_exec($pdo, "ALTER TABLE `pedidos` ADD COLUMN `id_sessao` INT NULL DEFAULT NULL");
    }
    if (!_qi_constraint_exists($pdo, 'pedidos', 'fk_pedido_sessao')) {
        _qi_safe_exec($pdo, "ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedido_sessao` FOREIGN KEY (`id_sessao`) REFERENCES `caixa_sessoes`(`id`) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    // Inserções padrão (todas seguras)
    try {
        $configuracoesPadrao = [
            'nome_sistema' => 'Sistema Quiosque',
            'moeda_simbolo' => 'R$',
            'impressora_nome' => '',
            'impressao_cabecalho' => 'Recibo de Venda',
            'impressao_rodape' => 'Obrigado e volte sempre!',
            'taxa_servico_padrao' => '0',
            'cliente_padrao_pdv' => '1',
            'numero_mesas' => '10',
            'alerta_estoque_baixo' => '5',
            'permitir_venda_sem_estoque' => 'nao',
        ];

        $stmtConfig = $pdo->prepare("INSERT IGNORE INTO `configuracoes` (chave, valor) VALUES (:chave, :valor)");
        foreach ($configuracoesPadrao as $chave => $valor) {
            $stmtConfig->execute(['chave' => $chave, 'valor' => $valor]);
        }

        $permissoesPadrao = [
            ['acessar_pdv', 'Acessar tela PDV'],
            ['acessar_dashboard', 'Acessar dashboard'],
            ['gerenciar_mesas', 'Gerenciar mesas'],
            ['visualizar_pedidos', 'Visualizar pedidos'],
            ['gerenciar_produtos', 'Gerenciar produtos'],
            ['gerenciar_clientes', 'Gerenciar clientes'],
            ['visualizar_relatorios', 'Visualizar relatórios'],
            ['gerenciar_utilizadores', 'Gerenciar utilizadores'],
            ['acessar_configuracoes', 'Acessar configurações'],
        ];

        $stmtPerm = $pdo->prepare("INSERT IGNORE INTO `permissoes` (nome_permissao, descricao) VALUES (:nome, :desc)");
        foreach ($permissoesPadrao as $perm) {
            $stmtPerm->execute(['nome' => $perm[0], 'desc' => $perm[1]]);
        }

        // Admin padrão (login: admin / senha: admin)
        $adminSenhaHash = password_hash('admin', PASSWORD_DEFAULT);
        $stmtAdmin = $pdo->prepare("INSERT IGNORE INTO `utilizadores` (`id`, `nome`, `login`, `senha_hash`, `cargo`, `ativo`) VALUES (1, 'Administrador', 'admin', :senha_hash, 'Admin', TRUE)");
        $stmtAdmin->execute(['senha_hash' => $adminSenhaHash]);

        // Vincula todas permissões ao admin
        _qi_safe_exec($pdo, "INSERT IGNORE INTO `utilizador_permissoes` (utilizador_id, permissao_id) SELECT 1, id FROM permissoes");

        // Cliente padrão
        _qi_safe_exec($pdo, "INSERT IGNORE INTO `clientes` (`id`, `nome`, `ativo`) VALUES (1, 'Consumidor Final', 1)");

    } catch (Throwable $e) {
        error_log('[Quiosque] Erro em inserções padrão: ' . $e->getMessage());
    }

    // Ajuste automático de mesas ativas conforme configuracao numero_mesas
    try {
        $stmtGetNumMesas = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'numero_mesas'");
        $stmtGetNumMesas->execute();
        $numMesasConfigStr = $stmtGetNumMesas->fetchColumn();
        $numMesasConfig = (int)($numMesasConfigStr ?: 10);
        if ($numMesasConfig <= 0) $numMesasConfig = 10;

        $stmtGetMaxMesa = $pdo->query("SELECT MAX(numero) FROM mesas");
        $maxNumeroExistente = (int)($stmtGetMaxMesa->fetchColumn() ?: 0);

        if ($numMesasConfig > $maxNumeroExistente) {
            $pdo->beginTransaction();
            $stmtInsertMesa = $pdo->prepare("INSERT IGNORE INTO mesas (numero, ativa) VALUES (:numero, 1)");
            for ($i = $maxNumeroExistente + 1; $i <= $numMesasConfig; $i++) {
                $stmtInsertMesa->execute([':numero' => $i]);
            }
            $pdo->commit();
        }

        $stmtUpdateDesativar = $pdo->prepare("UPDATE mesas SET ativa = 0 WHERE numero > :n");
        $stmtUpdateDesativar->execute([':n' => $numMesasConfig]);

        $stmtUpdateAtivar = $pdo->prepare("UPDATE mesas SET ativa = 1 WHERE numero <= :n");
        $stmtUpdateAtivar->execute([':n' => $numMesasConfig]);

    } catch (Throwable $e) {
        error_log('[Quiosque] Erro ao ajustar mesas: ' . $e->getMessage());
    }
}

// --- CONEXÃO E BOOT FINAL ---
try {
    // Migração inicial (se necessário)
    ensure_migrated();

    // Conecta
    $pdo = pdo();
    $GLOBALS['pdo'] = $pdo;

    // Garante esquema mínimo para o sistema funcionar
    _qi_ensure_schema($pdo);

} catch (Throwable $e) {
    error_log('[Quiosque] Erro crítico no bootstrap: ' . $e->getMessage());
    die('Erro ao conectar ou inicializar o banco de dados. Contacte o suporte.');
}
