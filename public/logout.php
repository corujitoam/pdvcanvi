<?php
require_once __DIR__ . '/../bootstrap.php';

// Limpa todas as variáveis da sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona o utilizador para a página de login
header('Location: login.php');
exit;
?>