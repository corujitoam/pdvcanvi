<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../models/Utilizador.php';

if (!function_exists('json_response')) {
    function json_response($data, $sucesso = true, $erro = null) {
        echo json_encode(['sucesso' => $sucesso, 'dados' => $data, 'erro' => $erro]);
        exit;
    }
}

try {
    $userModel = new Utilizador();
    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            $termo = $_GET['termo'] ?? '';
            json_response($userModel->listar($termo));
            break;

        case 'detalhes':
            $id = $_GET['id'] ?? 0;
            if (!$id) throw new Exception("ID inválido.");
            json_response($userModel->buscarPorId($id));
            break;
            
        // AÇÃO IMPORTANTE: Devolve a lista de todas as permissões possíveis
        case 'listar_permissoes':
            json_response($userModel->listarTodasPermissoes());
            break;

        case 'salvar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            
            // Monta dados
            $dados = [
                'id' => $_POST['id'] ?? null,
                'nome' => $_POST['nome'] ?? '',
                'login' => $_POST['login'] ?? '',
                'senha' => $_POST['senha'] ?? '',
                'cargo' => $_POST['cargo'] ?? '',
                'ativo' => $_POST['ativo'] ?? 0, // Se checkbox marcado, vem '1' (ou 'on' tratado depois)
                'permissoes' => $_POST['permissoes'] ?? [] // Array de checkboxes
            ];

            // Ajuste para checkbox do HTML que às vezes não envia nada se desmarcado
            if (isset($_POST['ativo']) && ($_POST['ativo'] == '1' || $_POST['ativo'] == 'on')) {
                $dados['ativo'] = 1;
            } else {
                $dados['ativo'] = 0;
            }

            if (empty($dados['nome']) || empty($dados['login'])) throw new Exception("Nome e Login obrigatórios.");

            if ($userModel->salvar($dados)) {
                json_response(null, true);
            }
            break;

        case 'excluir':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            $id = $_POST['id'] ?? 0;
            if ($userModel->excluir($id)) {
                json_response(null, true);
            }
            break;

        default:
            throw new Exception("Ação inválida.");
    }

} catch (Exception $e) {
    http_response_code(500);
    json_response(null, false, $e->getMessage());
}
?>