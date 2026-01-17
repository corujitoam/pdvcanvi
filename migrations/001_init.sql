-- ============================================================
-- 001_init.sql (HostGator/cPanel friendly)
-- - NÃO use "USE ..." (o DB já é selecionado no PDO)
-- - Não tenta CREATE DATABASE (cPanel geralmente bloqueia)
-- - Cria o esquema mínimo completo para o Quiosque-php
-- ============================================================

-- ------------------------------
-- CLIENTES
-- ------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- PRODUTOS
-- ------------------------------
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estoque` INT NOT NULL DEFAULT 0,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `imagem` VARCHAR(255) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_produtos_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- UTILIZADORES
-- ------------------------------
CREATE TABLE IF NOT EXISTS `utilizadores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `login` VARCHAR(50) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL,
  `cargo` VARCHAR(50) NULL,
  `ativo` BOOLEAN NOT NULL DEFAULT TRUE,
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- PERMISSOES
-- ------------------------------
CREATE TABLE IF NOT EXISTS `permissoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_permissao` VARCHAR(50) NOT NULL UNIQUE,
  `descricao` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `utilizador_permissoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `utilizador_id` INT NOT NULL,
  `permissao_id` INT NOT NULL,
  UNIQUE KEY `uniq_user_perm` (`utilizador_id`, `permissao_id`),
  CONSTRAINT `fk_up_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_perm` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- CONFIGURACOES
-- ------------------------------
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` VARCHAR(50) NOT NULL PRIMARY KEY,
  `valor` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- FORNECEDORES
-- ------------------------------
CREATE TABLE IF NOT EXISTS `fornecedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `cpf_cnpj` VARCHAR(20) NULL,
  `telefone` VARCHAR(30) NULL,
  `email` VARCHAR(255) NULL,
  `endereco` VARCHAR(255) NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relação opcional produto->fornecedor (será criada apenas se a coluna existir)
-- OBS: em migração inicial, já criamos a coluna.
ALTER TABLE `produtos`
  ADD COLUMN `fornecedor_id` INT NULL DEFAULT NULL;

ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produto_fornecedor`
  FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------
-- CAIXA
-- ------------------------------
CREATE TABLE IF NOT EXISTS `caixa_sessoes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `caixa_movimentacoes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- MESAS
-- ------------------------------
CREATE TABLE IF NOT EXISTS `mesas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero` INT NOT NULL UNIQUE,
  `status` ENUM('livre', 'ocupada', 'em_fechamento', 'reatendimento') NOT NULL DEFAULT 'livre',
  `descricao` VARCHAR(100) DEFAULT NULL,
  `ativa` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Ativa,0=Inativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_ativa` ON `mesas` (`ativa`);

CREATE TABLE IF NOT EXISTS `mesa_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mesa_id` INT NULL,
  `produto_id` INT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  `adicionado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mesa_items_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mesa_items_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- PEDIDOS
-- ------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NULL,
  `data_pedido` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` VARCHAR(50) DEFAULT 'Não definido',
  `id_sessao` INT NULL DEFAULT NULL,
  CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pedido_sessao` FOREIGN KEY (`id_sessao`) REFERENCES `caixa_sessoes`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NULL,
  `produto_id` INT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  `preco_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_pedido_items_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedido_items_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------
-- DADOS PADRAO
-- ------------------------------
INSERT IGNORE INTO `configuracoes` (`chave`, `valor`) VALUES
('nome_sistema','Sistema Quiosque'),
('moeda_simbolo','R$'),
('impressora_nome',''),
('impressao_cabecalho','Recibo de Venda'),
('impressao_rodape','Obrigado e volte sempre!'),
('taxa_servico_padrao','0'),
('cliente_padrao_pdv','1'),
('numero_mesas','10'),
('alerta_estoque_baixo','5'),
('permitir_venda_sem_estoque','nao');

INSERT IGNORE INTO `permissoes` (`nome_permissao`,`descricao`) VALUES
('acessar_pdv','Acessar tela PDV'),
('acessar_dashboard','Acessar dashboard'),
('gerenciar_mesas','Gerenciar mesas'),
('visualizar_pedidos','Visualizar pedidos'),
('gerenciar_produtos','Gerenciar produtos'),
('gerenciar_clientes','Gerenciar clientes'),
('visualizar_relatorios','Visualizar relatórios'),
('gerenciar_utilizadores','Gerenciar utilizadores'),
('acessar_configuracoes','Acessar configurações');

-- Usuário admin e vínculo de permissões são criados pelo bootstrap.php
-- (porque a senha precisa de password_hash no PHP).

INSERT IGNORE INTO `clientes` (`id`,`nome`,`ativo`) VALUES
(1,'Consumidor Final',1);

-- Mesas padrão (1..10)
INSERT IGNORE INTO `mesas` (`numero`,`ativa`) VALUES
(1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1);

