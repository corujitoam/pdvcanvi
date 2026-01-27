<?php
// config.php
// Coloque este arquivo na mesma pasta do db.php

// No HostGator/cPanel normalmente é "localhost"
define('DB_HOST', 'localhost');

// Use o nome EXATO do banco que você criou no cPanel
// Geralmente é algo como: SEUUSUARIO_nomeDoBanco
define('DB_NAME', 'BANCO');

// Use o usuário EXATO do MySQL que você criou no cPanel
// Geralmente é algo como: SEUUSUARIO_quiosque_user
define('DB_USER', 'USUARIO');

// A senha pode ter @, !, #, etc — isso é normal.
// Só precisa estar ENTRE ASPAS.
define('DB_PASS', 'SENHA SENHA');

