<?php
// public/api_relatorios.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php'; 
require_once __DIR__ . '/../models/Relatorio.php';

function json_response($data, $sucesso = true, $erro = null) {
    $response = ['sucesso' => $sucesso];
    if ($sucesso) {
        $response['dados'] = $data;
    } else {
        $response['erro'] = $erro;
    }
    echo json_encode($response);
    exit;
}

try {
    $relatorioModel = new Relatorio();
    $acao = $_GET['acao'] ?? null;
    $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d');
    $dataFim = $_GET['data_fim'] ?? date('Y-m-d');

    if (!$acao) {
        throw new Exception("Nenhuma ação definida.");
    }

    switch ($acao) {
        case 'vendas_por_periodo':
            $dados = $relatorioModel->gerarVendasPorPeriodo($dataInicio, $dataFim);
            json_response($dados);
            break;

        case 'fechamento_simples':
            $dados = $relatorioModel->gerarFechamentoSimples($dataInicio, $dataFim);
            json_response($dados);
            break;

        case 'fechamento_detalhado':
            $dados = $relatorioModel->gerarFechamentoDetalhado($dataInicio, $dataFim);
            json_response($dados);
            break;

        // --- ESTE ERA O BLOCO QUE FALTAVA ---
        case 'relatorio_caixa':
            $dados = $relatorioModel->gerarRelatorioCaixa($dataInicio, $dataFim);
            json_response($dados);
            break;
        // ------------------------------------

        default:
            throw new Exception("Ação de relatório inválida ou não especificada: " . $acao);
    }

} catch (Exception $e) {
    http_response_code(500);
    json_response(null, false, $e->getMessage());
}
?>