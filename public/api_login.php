<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Debug controlado (em produção, não mostrar erro na tela)
$debug = defined('APP_DEBUG') && APP_DEBUG;
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/Utilizador.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $login = trim($_POST['login'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');

    if ($login === '' || $senha === '') {
        throw new Exception('Preencha o login e a senha.');
    }

    $userModel = new Utilizador();
    $usuario = $userModel->verificarLogin($login, $senha);

    if ($usuario) {
        // Evita session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Login OK: grava sessão (mesmas chaves do auth_check.php)
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_cargo'] = $usuario['cargo'];
        $_SESSION['user_permissoes'] = $usuario['permissoes'];

        // Compatibilidade com verificações antigas
        $_SESSION['utilizador_logado'] = true;

        echo json_encode(['sucesso' => true, 'mensagem' => 'Bem-vindo!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Utilizador ou senha incorretos.');

} catch (Throwable $e) {
    http_response_code(401);

    // Em produção, não exponha detalhes
    $msg = $debug ? $e->getMessage() : 'Falha no login.';
    echo json_encode(['sucesso' => false, 'mensagem' => $msg], JSON_UNESCAPED_UNICODE);
}
