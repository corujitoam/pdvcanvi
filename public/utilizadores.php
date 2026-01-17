<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Utilizadores</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Específico para a Grid de Permissões */
        .permissoes-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Duas colunas */
            gap: 10px;
            background: rgba(0,0,0,0.1);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-dark);
        }
        .permissoes-grid label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: normal;
            cursor: pointer;
            margin-bottom: 0;
        }
        .permissoes-grid input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">GESTÃO DE UTILIZADORES</span>
            <div class="header-actions">
                <button class="btn btn-primary" id="btn-novo">+ Novo Utilizador</button>
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="gestao-container">
            
            <div class="lista-utilizadores">
                <input type="search" id="input-pesquisa" placeholder="Pesquisar utilizador..." autocomplete="off">
                <ul id="lista-resultados"></ul>
            </div>

            <div class="form-utilizador-wrapper">
                
                <div id="form-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                    </svg>
                    <h3>Selecione um utilizador para editar</h3>
                </div>

                <div id="form-container" style="display: none;">
                    <h2 id="form-titulo">Novo Utilizador</h2>
                    
                    <form id="form-utilizador">
                        <div class="form-body">
                            <input type="hidden" name="id" id="user-id">
                            
                            <fieldset>
                                <legend>Dados de Acesso</legend>
                                <div class="form-grid">
                                    <div class="full-width">
                                        <label>Nome Completo*</label>
                                        <input type="text" name="nome" id="nome" required>
                                    </div>
                                    <div>
                                        <label>Login (Usuário)*</label>
                                        <input type="text" name="login" id="login" required>
                                    </div>
                                    <div>
                                        <label>Cargo</label>
                                        <input type="text" name="cargo" id="cargo" placeholder="Ex: Gerente">
                                    </div>
                                    <div>
                                        <label>Senha</label>
                                        <input type="password" name="senha" id="senha" placeholder="Deixe em branco para não alterar">
                                    </div>
                                    <div style="display:flex; align-items:center;">
                                        <label class="checkbox-container">
                                            <input type="checkbox" name="ativo" id="ativo" value="1" checked>
                                            Utilizador Ativo
                                        </label>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset>
                                <legend>Permissões do Sistema</legend>
                                <div id="container-permissoes" class="permissoes-grid">
                                    <p style="color:#aaa;">A carregar permissões...</p>
                                </div>
                            </fieldset>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-danger" id="btn-excluir" style="display: none;">Excluir</button>
                            <button type="button" class="btn btn-secondary" id="btn-cancelar">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    
    <div id="toast"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const listaResultados = document.getElementById('lista-resultados');
        const inputPesquisa = document.getElementById('input-pesquisa');
        const containerPermissoes = document.getElementById('container-permissoes');
        const form = document.getElementById('form-utilizador');
        const formContainer = document.getElementById('form-container');
        const formPlaceholder = document.getElementById('form-placeholder');
        const formTitulo = document.getElementById('form-titulo');
        const btnNovo = document.getElementById('btn-novo');
        const btnCancelar = document.getElementById('btn-cancelar');
        const btnExcluir = document.getElementById('btn-excluir');
        const toast = document.getElementById('toast');
        let debounceTimer;

        // 1. Carregar Permissões (Checkboxes) ao iniciar
        async function carregarPermissoesDisponiveis() {
            try {
                const res = await fetch('api_utilizadores.php?acao=listar_permissoes');
                const json = await res.json();
                
                containerPermissoes.innerHTML = '';
                if (json.sucesso && json.dados) {
                    json.dados.forEach(p => {
                        const div = document.createElement('div');
                        // O name="permissoes[]" cria um array no PHP
                        div.innerHTML = `
                            <label>
                                <input type="checkbox" name="permissoes[]" value="${p.id}">
                                ${p.descricao || p.nome_permissao}
                            </label>
                        `;
                        containerPermissoes.appendChild(div);
                    });
                }
            } catch (error) {
                console.error("Erro ao carregar permissões", error);
            }
        }

        // 2. Carregar Lista de Utilizadores
        async function carregarUtilizadores(termo = '') {
            try {
                const res = await fetch(`api_utilizadores.php?acao=listar&termo=${encodeURIComponent(termo)}`);
                const json = await res.json();
                
                listaResultados.innerHTML = '';
                if (json.sucesso && json.dados) {
                    json.dados.forEach(u => {
                        const li = document.createElement('li');
                        li.className = `utilizador-item ${u.ativo == 0 ? 'inativo' : ''}`;
                        li.innerHTML = `
                            <span class="utilizador-item-nome">${u.nome}</span>
                            <small class="utilizador-item-detalhes">${u.cargo || 'Sem cargo'} | Login: ${u.login}</small>
                        `;
                        // Se inativo, muda cor (opcional no CSS)
                        if(u.ativo == 0) li.style.opacity = '0.6';
                        
                        li.addEventListener('click', () => carregarDetalhes(u.id));
                        listaResultados.appendChild(li);
                    });
                }
            } catch (error) {
                showToast("Erro ao listar.", true);
            }
        }

        // 3. Carregar Detalhes
        async function carregarDetalhes(id) {
            try {
                const res = await fetch(`api_utilizadores.php?acao=detalhes&id=${id}`);
                const json = await res.json();

                if (json.sucesso) {
                    const u = json.dados;
                    form.id.value = u.id;
                    form.nome.value = u.nome;
                    form.login.value = u.login;
                    form.cargo.value = u.cargo;
                    form.senha.value = ''; // Limpa senha
                    document.getElementById('ativo').checked = (u.ativo == 1);

                    // Resetar permissões
                    const checkboxes = containerPermissoes.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = false);

                    // Marcar permissões do utilizador
                    if (u.permissoes && Array.isArray(u.permissoes)) {
                        u.permissoes.forEach(pid => {
                            const cb = containerPermissoes.querySelector(`input[value="${pid}"]`);
                            if (cb) cb.checked = true;
                        });
                    }

                    mostrarFormulario(true);
                }
            } catch (error) {
                showToast("Erro ao carregar detalhes.", true);
            }
        }

        // 4. Salvar
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('acao', 'salvar');

            // Correção para checkbox desmarcado 'ativo'
            if (!document.getElementById('ativo').checked) {
                formData.append('ativo', '0');
            }

            try {
                const res = await fetch('api_utilizadores.php', { method: 'POST', body: formData });
                const json = await res.json();

                if (json.sucesso) {
                    showToast("Utilizador salvo!");
                    carregarUtilizadores(inputPesquisa.value);
                    esconderFormulario();
                } else {
                    showToast(json.erro || "Erro ao salvar.", true);
                }
            } catch (error) {
                showToast("Erro de conexão.", true);
            }
        });
        
        // Helpers de UI
        function mostrarFormulario(isEdicao) {
            formPlaceholder.style.display = 'none';
            formContainer.style.display = 'flex';
            formTitulo.textContent = isEdicao ? 'Editar Utilizador' : 'Novo Utilizador';
            btnExcluir.style.display = isEdicao ? 'inline-flex' : 'none';
            
            if (!isEdicao) {
                form.reset();
                form.id.value = '';
                // Reseta checkboxes
                containerPermissoes.querySelectorAll('input').forEach(cb => cb.checked = false);
            }
        }

        function esconderFormulario() {
            formContainer.style.display = 'none';
            formPlaceholder.style.display = 'block';
        }

        function showToast(msg, erro = false) {
            if(toast) { toast.textContent = msg; toast.className = erro ? 'show erro' : 'show sucesso'; setTimeout(() => toast.className = '', 3000); }
        }

        // Inicialização
        carregarPermissoesDisponiveis();
        carregarUtilizadores();
        
        // Listeners
        btnNovo.addEventListener('click', () => mostrarFormulario(false));
        btnCancelar.addEventListener('click', esconderFormulario);
        inputPesquisa.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => carregarUtilizadores(inputPesquisa.value), 300);
        });
    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>