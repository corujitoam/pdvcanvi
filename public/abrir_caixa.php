<?php
// public/abrir_caixa.php

// 1. Configurações de Erro (para debug)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Carregamento dos ficheiros principais
// Usa realpath para garantir que o caminho existe
$bootstrapPath = __DIR__ . '/../bootstrap.php';
$authPath = __DIR__ . '/../auth_check.php';

if (file_exists($bootstrapPath)) require_once $bootstrapPath;
if (file_exists($authPath)) require_once $authPath;

// 3. Carrega Models
require_once __DIR__ . '/../models/CaixaSessao.php';
require_once __DIR__ . '/../models/Configuracao.php';

$sessaoModel = new CaixaSessao();
$configModel = new Configuracao();

// 4. CORREÇÃO: Garante que temos o ID do utilizador
// Se a variável $utilizadorLogado não vier do auth_check, tentamos pegar da sessão
$id_utilizador = $utilizadorLogado['id'] ?? $_SESSION['user_id'] ?? null;

if (!$id_utilizador) {
    // Se não tiver utilizador, manda pro login
    header("Location: login.php");
    exit;
}

$configuracoes = $configModel->carregarConfiguracoes();
$moeda = $configuracoes['moeda_simbolo'] ?? 'R$';

$erro = null;

// --- FUNÇÃO DE REDIRECIONAMENTO SEGURO ---
// (Funciona mesmo se o PHP já tiver enviado cabeçalhos)
function redirecionar($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href='" . $url . "';</script>";
        exit;
    }
}

// --- 5. Lógica de Auto-Recuperação ---
try {
    $sessaoAberta = $sessaoModel->buscarSessaoAberta();
    if ($sessaoAberta) {
        $_SESSION['id_sessao_ativa'] = $sessaoAberta['id'];
        redirecionar("../index.php");
    }
} catch (Exception $e) {
    // Se der erro ao buscar sessão (tabela não existe, etc), apenas segue
    error_log("Aviso ao buscar sessão: " . $e->getMessage());
}

// --- 6. Processamento do Formulário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $valor_abertura_str = str_replace([$moeda, ' ', ','], ['', '', '.'], $_POST['valor_abertura']);
        $valor_abertura = (float) $valor_abertura_str;

        if ($valor_abertura < 0) {
            throw new InvalidArgumentException("Valor inválido.");
        }

        $novo_id_sessao = $sessaoModel->abrir($id_utilizador, $valor_abertura);

        if ($novo_id_sessao) {
            $_SESSION['id_sessao_ativa'] = $novo_id_sessao;
            redirecionar("../index.php");
        } else {
            throw new Exception("Erro ao abrir sessão.");
        }

    } catch (Exception $e) {
        // Auto-recuperação final
        $sessaoAberta = $sessaoModel->buscarSessaoAberta();
        if ($sessaoAberta) {
            $_SESSION['id_sessao_ativa'] = $sessaoAberta['id'];
            redirecionar("../index.php");
        }
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Abrir Caixa</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body, html { height: 100%; display: flex; align-items: center; justify-content: center; background-color: #0f172a; color: #fff; font-family: sans-serif; margin: 0; }
        .container-box { background: #1e293b; padding: 30px; border-radius: 10px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { margin-top: 0; font-size: 1.5rem; }
        input { width: 100%; padding: 10px; margin: 15px 0; border-radius: 5px; border: 1px solid #334155; background: #0f172a; color: #fff; font-size: 1.2rem; text-align: center; }
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: bold; }
        button:hover { background: #2563eb; }
        .erro { background: #ef4444; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container-box">
        <h1>Abrir Caixa</h1>
        <p style="color: #94a3b8; margin-bottom: 20px;">Insira o fundo de troco inicial.</p>

        <?php if ($erro): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="valor">Valor (<?php echo $moeda; ?>)</label>
            <input type="text" name="valor_abertura" value="0,00" autofocus onfocus="this.select()">
            <button type="submit">Iniciar Turno</button>
        </form>
    </div>
</body>
</html>