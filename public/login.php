<?php
// A página de login também precisa de iniciar o bootstrap para aceder à sessão.
require_once __DIR__ . '/../bootstrap.php';

$nomeSistema = 'Quiosque Manager';
try {
    if (class_exists('Configuracao')) {
        $cfgModel = new Configuracao();
        if (method_exists($cfgModel, 'carregarConfiguracoes')) {
            $cfg = $cfgModel->carregarConfiguracoes();
            if (!empty($cfg['nome_sistema'])) $nomeSistema = (string)$cfg['nome_sistema'];
        }
    }
} catch (Throwable $e) { /* silencioso */ }


// Lógica específica da página de login:
// Se o utilizador JÁ ESTIVER logado, não o deixes ver esta página.
// Redireciona-o para o dashboard.
if (isset($_SESSION['utilizador_logado'])) {
    // Ajuste o caminho se necessário para apontar corretamente para o index.php na raiz
    // Pode ser '../index.php' dependendo da sua estrutura de URL/servidor.
    header('Location: ../index.php'); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($nomeSistema); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos específicos da página de login */
        body.login-page { /* Adiciona classe para especificidade */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            overflow: auto; /* Permite scroll se o conteúdo for maior */
            padding: 20px; /* Evita que cole nas bordas em telas pequenas */
        }
        .login-container { 
            max-width: 400px; 
            width: 100%; 
            padding: 32px; 
            background: var(--card-bg-dark); 
            border-radius: var(--radius); 
            box-shadow: var(--shadow); 
            border: 1px solid var(--border-dark); /* Adiciona borda */
        }
        body.light .login-container { 
            background: var(--card-bg-light); 
            border-color: var(--border-light);
        }
        .login-container h1 { 
            text-align: center; 
            margin-top: 0; 
            margin-bottom: 24px; 
            font-size: 24px; /* Tamanho ajustado */
        }
        .login-container label { 
            font-size: 14px; 
            /* color: #aaa; -- Removido, usa cor padrão do body */
            margin-bottom: 8px; /* Adiciona espaço abaixo do label */
        }
         /* Sobrescreve margem padrão do input/select */
        .login-container input {
             margin-bottom: 16px; /* Aumenta espaço entre senha e botão */
        }
        .login-container button[type="submit"] {
            width: 100%; /* Botão ocupa largura total */
            margin-top: 8px; /* Espaço acima do botão */
        }
        .error-message { 
            background: rgba(248, 113, 113, 0.1); /* Fundo mais suave */
            color: var(--danger); 
            border: 1px solid var(--danger); 
            padding: 12px; 
            border-radius: var(--radius); 
            margin-bottom: 16px; 
            text-align: center; 
            font-size: 14px; /* Tamanho ajustado */
            display: none; /* Começa escondido */
        }
    </style>
</head>
<body class="login-page"> 
    <div class="login-container">
        <h1><?php echo htmlspecialchars($nomeSistema); ?></h1>
        <div id="error-message" class="error-message"></div>
        <form id="form-login">
            <label for="login">Utilizador (Login)</label>
            <input type="text" id="login" name="login" required autocomplete="username"> 
            
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required autocomplete="current-password">
            
            <button type="submit" class="btn btn-primary">Entrar</button> 
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-login');
        const errorMessageDiv = document.getElementById('error-message');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorMessageDiv.style.display = 'none'; // Esconde erro anterior
            errorMessageDiv.textContent = ''; // Limpa texto do erro anterior

            // Pega os dados do formulário
            const formData = new FormData(form);

            // Pega o botão e desabilita + mostra feedback (opcional)
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Aguarde...';

            try {
                const response = await fetch('api_login.php', { 
                    method: 'POST', 
                    body: formData 
                });

                // Verifica se a resposta da API foi OK (status 2xx)
                if (!response.ok) {
                    // Se não foi OK, tenta ler como texto para ver o erro PHP (se houver)
                    let errorText = `Erro HTTP: ${response.status} ${response.statusText}`;
                    try {
                        // Tenta pegar mais detalhes do corpo da resposta, se houver
                        const bodyText = await response.text();
                        // Evita mostrar HTML de erro PHP completo, pega só a mensagem principal
                        const match = bodyText.match(/<b>Fatal error<\/b>:(.*?)<br \/>/); 
                        if (match && match[1]) {
                             errorText = `Erro no servidor: ${match[1].trim()}`;
                        } else if (bodyText.length < 500) { // Mostra corpo se for curto
                             errorText += `\nDetalhes: ${bodyText}`;
                        }
                         console.error("Raw error response:", bodyText); // Loga erro completo no console
                    } catch (textError) {
                        // Ignora se não conseguir ler o corpo
                    }
                    throw new Error(errorText); // Lança o erro HTTP ou PHP
                }

                // Se a resposta foi OK, processa o JSON
                const resultado = await response.json();

                if (resultado.sucesso) {
                    window.location.href = '../index.php'; // Redireciona
                } else {
                    // Mostra a mensagem de erro vinda da API (ex: "Login ou senha inválidos")
                    errorMessageDiv.textContent = resultado.mensagem || 'Login ou senha inválidos.';
                    errorMessageDiv.style.display = 'block';
                }
            } catch (error) {
                // Captura erros de rede (fetch falhou) ou erros lançados acima
                console.error("Erro no fetch ou processamento:", error); // Loga o erro no console
                errorMessageDiv.textContent = error.message.includes("HTTP") || error.message.includes("servidor") 
                    ? error.message // Mostra erro HTTP ou do servidor pego acima
                    : 'Erro de conexão com o servidor. Verifique sua rede.'; // Erro genérico de rede
                errorMessageDiv.style.display = 'block';
            } finally {
                 // Reabilita o botão e restaura o texto, independentemente do resultado
                 submitButton.disabled = false;
                 submitButton.textContent = originalButtonText;
            }
        });
    });
    </script>
     </body>
</html>