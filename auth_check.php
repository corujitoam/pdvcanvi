<?php
// auth_check.php

// Garante que a sessão foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o utilizador tem o ID na sessão (definido no api_login.php)
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para o login
    header('Location: public/login.php');
    exit;
}

// Cria a variável $utilizadorLogado para ser usada nas páginas (index, dashboard, etc)
// Isso corrige o erro "value of type bool"
$utilizadorLogado = [
    'id' => $_SESSION['user_id'],
    'nome' => $_SESSION['user_nome'] ?? 'Utilizador',
    'cargo' => $_SESSION['user_cargo'] ?? 'Funcionário',
    'permissoes' => $_SESSION['user_permissoes'] ?? []
];
?>