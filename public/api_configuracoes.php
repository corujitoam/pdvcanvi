<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../models/Configuracao.php';

if (!function_exists('json_response')) {
    function json_response($data, $sucesso = true, $erro = null) {
        echo json_encode(['sucesso' => $sucesso, 'dados' => $data, 'erro' => $erro]);
        exit;
    }
}

try {
    $configModel = new Configuracao();
    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'carregar':
            $dados = $configModel->carregarConfiguracoes();
            json_response($dados);
            // Sem break aqui, pois json_response mata o script

        case 'salvar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            
            $dados = $_POST;
            
            if ($configModel->salvarMultiplas($dados)) {
                // Sucesso manual para corresponder ao esperado pelo JS
                echo json_encode(['sucesso' => true, 'mensagem' => 'Configurações salvas com sucesso!']);
                exit;
            } else {
                throw new Exception("Erro ao salvar configurações.");
            }
            // Sem break aqui

        default:
            throw new Exception("Ação inválida.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>