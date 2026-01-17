<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../models/Cliente.php';

// CORREÇÃO 2: Verifica se a função já existe antes de declarar
// Isso evita conflito com api_relatorios.php ou outros arquivos
if (!function_exists('json_response')) {
    function json_response($data, $sucesso = true, $erro = null) {
        echo json_encode(['sucesso' => $sucesso, 'dados' => $data, 'erro' => $erro]);
        exit;
    }
}

try {
    $clienteModel = new Cliente();
    

function only_digits($s) {
    return preg_replace('/\D/', '', (string)$s);
}

function is_valid_cpf($cpf) {
    $cpf = only_digits($cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(\d)\\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += intval($cpf[$i]) * (($t + 1) - $i);
        }
        $d = (10 * $sum) % 11;
        if ($d == 10) $d = 0;
        if ($cpf[$t] != $d) return false;
    }
    return true;
}

function is_valid_cnpj($cnpj) {
    $cnpj = only_digits($cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/^(\d)\\1{13}$/', $cnpj)) return false;
    $calc = function($base) {
        $len = strlen($base);
        $sum = 0;
        $pos = $len - 7;
        for ($i = $len; $i >= 1; $i--) {
            $sum += intval($base[$len - $i]) * $pos--;
            if ($pos < 2) $pos = 9;
        }
        $r = $sum % 11;
        return ($r < 2) ? 0 : (11 - $r);
    };
    $d1 = $calc(substr($cnpj, 0, 12));
    $d2 = $calc(substr($cnpj, 0, 12) . $d1);
    return substr($cnpj, -2) === (string)$d1 . (string)$d2;
}

function validate_document($doc) {
    $doc = only_digits($doc);
    if ($doc === '') return [true, ''];
    if (strlen($doc) == 11) return [is_valid_cpf($doc), $doc];
    if (strlen($doc) == 14) return [is_valid_cnpj($doc), $doc];
    return [false, $doc];
}

$acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            $termo = $_GET['termo'] ?? '';
            $clientes = $clienteModel->listar($termo);
            json_response($clientes);
            break;

        case 'detalhes':
            $id = $_GET['id'] ?? 0;
            if (!$id) throw new Exception("ID inválido.");
            $cliente = $clienteModel->buscarPorId($id);
            json_response($cliente);
            break;

        case 'salvar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            
            // Monta o array com os campos do formulário
            $dados = [
                'id' => $_POST['id'] ?? null,
                'nome' => $_POST['nome'] ?? '',
                'cpf' => $_POST['cpf'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'cep' => $_POST['cep'] ?? '',
                'logradouro' => $_POST['logradouro'] ?? '',
                'numero' => $_POST['numero'] ?? '',
                'bairro' => $_POST['bairro'] ?? '',
                'cidade' => $_POST['cidade'] ?? '',
                'uf' => $_POST['uf'] ?? ''
            ];

            if (empty($dados['nome'])) throw new Exception("O nome é obrigatório.");

            // Valida CPF/CNPJ (campo cpf agora aceita os dois)
            list($okDoc, $docLimpo) = validate_document($dados['cpf']);
            if (!$okDoc) throw new Exception("CPF/CNPJ inválido.");
            $dados['cpf'] = $docLimpo;

            if ($clienteModel->salvar($dados)) {
                json_response(null, true);
            } else {
                throw new Exception("Erro ao salvar no banco de dados.");
            }

        case 'excluir':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            $id = $_POST['id'] ?? 0;
            if ($clienteModel->excluir($id)) {
                json_response(null, true);
            } else {
                throw new Exception("Erro ao excluir cliente.");
            }

        default:
            throw new Exception("Ação inválida.");
    }

} catch (Exception $e) {
    http_response_code(500);
    // Se a função json_response não estiver disponível por algum motivo bizarro, usa echo simples
    if (function_exists('json_response')) {
        json_response(null, false, $e->getMessage());
    } else {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
}
?>