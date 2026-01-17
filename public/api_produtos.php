<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// 1. Carrega o sistema e segurança
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../models/Produto.php';

$acao = $_REQUEST['acao'] ?? '';
$resposta = ['sucesso' => false];

try {
    $produtoModel = new Produto();

    // --- LÓGICA DE PESQUISA ATUALIZADA ---
    if ($acao === 'pesquisar') {
        $termo = $_GET['termo'] ?? '';
        
        // CORREÇÃO: Usa o método listar() com filtro, pois o pesquisarPorNome foi removido
        if (!empty($termo)) {
            $resposta['dados'] = $produtoModel->listar(['termo' => $termo]);
        } else {
            $resposta['dados'] = $produtoModel->listar();
        }
        $resposta['sucesso'] = true;

    } elseif ($acao === 'detalhes' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $produto = $produtoModel->buscarPorId($id); // Usa o nome padronizado buscarPorId
        
        if ($produto) {
            $resposta['dados'] = $produto;
            $resposta['sucesso'] = true;
        } else {
            $resposta['erro'] = 'Produto não encontrado';
        }

    } elseif ($acao === 'salvar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $dados = $_POST;
        
        // Lógica de Upload de Imagem
        $caminhoImagem = $dados['imagem_existente'] ?? null;
        
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            // Garante que a pasta existe
            $diretorioUploads = __DIR__ . '/uploads/';
            if (!is_dir($diretorioUploads)) {
                mkdir($diretorioUploads, 0777, true);
            }
            
            $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $nomeArquivo = uniqid() . '.' . $extensao;
            $caminhoDestino = 'uploads/' . $nomeArquivo; // Caminho relativo para salvar no banco
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], __DIR__ . '/' . $caminhoDestino)) {
                $caminhoImagem = $caminhoDestino;
            }
        }
        
        $dados['imagem'] = $caminhoImagem;
        
        if ($produtoModel->salvar($dados)) {
            $resposta = ['sucesso' => true, 'mensagem' => 'Produto salvo com sucesso!'];
        } else {
            $resposta['erro'] = 'Falha ao salvar o produto no banco.';
        }

    } elseif ($acao === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? 0;
        if ($id > 0 && $produtoModel->excluir($id)) {
            $resposta = ['sucesso' => true, 'mensagem' => 'Produto excluído com sucesso!'];
        } else {
            $resposta['erro'] = 'Falha ao excluir o produto.';
        }
    } else {
        throw new Exception("Ação inválida: " . htmlspecialchars($acao));
    }

} catch (Exception $e) {
    http_response_code(500);
    $resposta['erro'] = 'Erro na API de Produtos: ' . $e->getMessage();
}

echo json_encode($resposta);
?>