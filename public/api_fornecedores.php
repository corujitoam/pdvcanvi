<?php
// Inclui os ficheiros de inicialização e autenticação
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// Define o tipo de conteúdo da resposta como JSON
header('Content-Type: application/json');

/**
 * Função auxiliar para enviar respostas JSON padronizadas e terminar o script.
 * @param bool $sucesso - Indica se a operação foi bem-sucedida.
 * @param mixed $dados - Os dados de resposta (em caso de sucesso) ou a mensagem de erro (em caso de falha).
 * @param int $httpStatus - O código de status HTTP (opcional).
 */
function enviarResposta(bool $sucesso, $dados, int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    if ($sucesso) {
        echo json_encode(['sucesso' => true, 'dados' => $dados]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => $dados]);
    }
    exit;
}

// Determina a ação a ser executada (vinda de POST ou GET)
$acao = $_POST['acao'] ?? $_GET['acao'] ?? null;

try {
    // O autoloader do bootstrap.php carrega a classe 'Fornecedor' automaticamente
    $fornecedorModel = new Fornecedor();

    switch ($acao) {
        /**
         * Ação: 'listar_todos'
         * Retorna: Todos os fornecedores (ativos e inativos) para o modal de gestão.
         */
        case 'listar_todos':
            $fornecedores = $fornecedorModel->listarTodos();
            enviarResposta(true, $fornecedores);
            break;

        /**
         * Ação: 'listar_ativos'
         * Retorna: Apenas fornecedores ativos (para o campo de seleção no form de produto).
         */
        case 'listar_ativos':
            $fornecedores = $fornecedorModel->listarAtivos();
            enviarResposta(true, $fornecedores);
            break;

        /**
         * Ação: 'detalhes'
         * Parâmetro: id (via GET)
         * Retorna: Os dados de um fornecedor específico para edição.
         */
        case 'detalhes':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID do fornecedor é inválido.", 400);
            }
            $fornecedor = $fornecedorModel->buscar($id);
            if (!$fornecedor) {
                throw new Exception("Fornecedor não encontrado.", 404);
            }
            enviarResposta(true, $fornecedor);
            break;

        /**
         * Ação: 'salvar'
         * Parâmetros: dados do formulário (via POST)
         * Retorna: Mensagem de sucesso e o ID do fornecedor.
         */
        case 'salvar':
            // O método salvar() da classe Fornecedor já trata a diferença
            // entre criar (sem ID) e atualizar (com ID).
            $fornecedorModel->salvar($_POST);
            
            // Determina se foi uma criação ou atualização para a mensagem
            $isUpdate = !empty($_POST['id']);
            $mensagem = $isUpdate ? "Fornecedor atualizado com sucesso." : "Fornecedor criado com sucesso.";
            
            // Retorna o ID (se for atualização, usa o ID enviado; se for criação, pega o último ID inserido)
            $id = $isUpdate ? $_POST['id'] : $GLOBALS['pdo']->lastInsertId();
            
            enviarResposta(true, ['mensagem' => $mensagem, 'id' => $id]);
            break;

        /**
         * Ação: 'excluir'
         * Parâmetros: id (via POST)
         * Retorna: Mensagem de sucesso.
         */
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID do fornecedor é inválido para exclusão.", 400);
            }
            
            // Tenta excluir
            $sucesso = $fornecedorModel->excluir($id);
            
            if (!$sucesso) {
                 // Pode falhar se houver restrições de FK, embora tenhamos usado "SET NULL"
                throw new Exception("Falha ao excluir o fornecedor.", 500);
            }
            
            enviarResposta(true, ['mensagem' => 'Fornecedor excluído com sucesso.']);
            break;

        /**
         * Ação Padrão (Inválida)
         */
        default:
            throw new Exception("Ação desconhecida ou não fornecida: " . htmlspecialchars($acao ?? 'N/A'), 400);
    }

} catch (PDOException $e) {
    // Erro específico da base de dados
    error_log("Erro de PDO em api_fornecedores.php: " . $e->getMessage());
    enviarResposta(false, "Erro de base de dados. Contacte o administrador.", 500);

} catch (Exception $e) {
    // Erro geral da aplicação (ex: "Nome obrigatório", "ID inválido")
    // Usa o código HTTP da exceção, ou 400 (Bad Request) como padrão
    $codigoStatus = $e->getCode() >= 400 ? $e->getCode() : 400;
    enviarResposta(false, $e->getMessage(), $codigoStatus);
}