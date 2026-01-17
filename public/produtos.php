<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Produtos - Quiosque</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Remove as bolinhas da lista e zera margens */
        #lista-fornecedores-modal {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px; /* Espaço entre cada fornecedor */
        }

        /* O Cartão do Fornecedor */
        .fornecedor-item {
            background-color: rgba(255, 255, 255, 0.03); /* Fundo subtil */
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease-in-out; /* Animação suave */
            position: relative;
            overflow: hidden;
        }

        /* Animação ao passar o rato (Hover) */
        .fornecedor-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateX(5px); /* Move ligeiramente para a direita */
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Estado Ativo (Quando selecionado) */
        .fornecedor-item.active {
            background-color: rgba(59, 130, 246, 0.15); /* Azul suave */
            border-color: #3b82f6; /* Borda Azul vibrante */
            box-shadow: 0 0 0 1px #3b82f6; /* Brilho extra na borda */
        }

        /* Nome do Fornecedor */
        .fornecedor-item-nome {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            color: #e2e8f0; /* Texto claro */
            margin-bottom: 4px;
        }

        /* Detalhes (Email/Telefone) */
        .fornecedor-item-detalhes {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8; /* Cinzento mais claro */
        }

        /* Tag de Inativo */
        .fornecedor-item-detalhes .inativo {
            color: #ef4444;
            font-weight: bold;
            background: rgba(239, 68, 68, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        /* Ajuste na barra de scroll */
        .lista-produtos::-webkit-scrollbar { width: 8px; }
        .lista-produtos::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
        .lista-produtos::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        .lista-produtos::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">GESTÃO DE PRODUTOS</span>
            <div class="header-actions">
                <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle" title="Alterar tema">
                    <span class="icon-sun">☀️</span>
                    <span class="icon-moon">🌙</span>
                </button>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="gestao-container">

            <div class="lista-produtos">
                <input type="search" id="input-pesquisa" placeholder="Pesquisar produto...">
                <ul id="lista-resultados"></ul>
            </div>

            <div class="form-produto">
                <h2 id="form-titulo">Selecione um produto para editar</h2>
                <form id="form-produto" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="produto-id">
                    <input type="hidden" name="imagem_existente" id="imagem-existente">
                    <fieldset>
                        <legend>Informações Principais</legend>
                        <label for="produto-nome">Nome do Produto*</label>
                        <input type="text" id="produto-nome" name="nome" required>
                        <label for="produto-preco">Preço*</label>
                        <input type="number" step="0.01" id="produto-preco" name="preco" required>
                    </fieldset>
                    <fieldset>
                        <legend>Detalhes Adicionais</legend>
                        <label for="produto-estoque">Estoque*</label>
                        <input type="number" id="produto-estoque" name="estoque" required>
                        <label for="produto-descricao">Descrição</label>
                        <textarea id="produto-descricao" name="descricao" rows="3"></textarea>
                        
                        <label for="produto-fornecedor-nome">Fornecedor</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                             <input type="text" id="produto-fornecedor-nome" name="fornecedor_nome" list="lista-fornecedores" placeholder="Digite para pesquisar..." autocomplete="off" style="flex-grow: 1;">
                             <button type="button" class="btn btn-secondary btn-small" id="btn-gerir-fornecedores" style="white-space: nowrap;">Gerir</button>
                        </div>
                        
                        <datalist id="lista-fornecedores"></datalist>
                        
                        <input type="hidden" id="produto-fornecedor-id" name="fornecedor_id">
                        
                        <label for="produto-imagem">Imagem do Produto</label>
                        <input type="file" id="produto-imagem" name="imagem" accept="image/*">
                        <img id="preview-imagem" src="#" alt="Preview" style="display: none;">
                    </fieldset>
                    <div class="botoes-acao">
                        <button type="button" class="btn btn-danger" id="btn-excluir">Excluir</button>
                        <button type="button" class="btn btn-secondary" id="btn-novo">Novo</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>

        </div> 
    </div> 
    
    <div id="toast"></div>

    <div id="modal-fornecedores" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gestão de Fornecedores</h2>
                <button id="btn-fechar-modal-fornecedores" class="modal-close" title="Fechar">&times;</button>
            </div>
            <div class="modal-body gestao-container">

                <div class="lista-produtos" style="max-height: 450px; overflow-y: auto;">
                    <ul id="lista-fornecedores-modal">
                        <li style="text-align: center; color: #aaa;">A carregar...</li>
                    </ul>
                </div>

                <div class="form-produto">
                    <h3 id="form-titulo-fornecedor">Novo Fornecedor</h3>
                    <form id="form-fornecedor-modal">
                        <fieldset>
                            <input type="hidden" name="id" id="fornecedor-id-modal">
                            
                            <label for="fornecedor-nome-modal">Nome*</label>
                            <input type="text" id="fornecedor-nome-modal" name="nome" required>
                            
                            <label for="fornecedor-cpf-cnpj-modal">CPF/CNPJ</label>
                            <input type="text" id="fornecedor-cpf-cnpj-modal" name="cpf_cnpj">
                            
                            <label for="fornecedor-telefone-modal">Telefone</label>
                            <input type="tel" id="fornecedor-telefone-modal" name="telefone">

                            <label for="fornecedor-email-modal">Email</label>
                            <input type="email" id="fornecedor-email-modal" name="email">

                            <label for="fornecedor-endereco-modal">Endereço</label>
                            <input type="text" id="fornecedor-endereco-modal" name="endereco">

                            <label class="checkbox-container">
                                <input type="checkbox" id="fornecedor-ativo-modal" name="ativo" checked>
                                Fornecedor Ativo
                            </label>
                        </fieldset>
                        
                        <div class="botoes-acao">
                            <button type="button" class="btn btn-danger" id="btn-excluir-fornecedor">Excluir</button>
                            <button type="button" class="btn btn-secondary" id="btn-novo-fornecedor">Novo</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>

            </div> 
        </div> 
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- CONSTANTES (Elementos do Formulário de Produto) ---
    const form = document.getElementById('form-produto');
    const listaResultados = document.getElementById('lista-resultados');
    const formTitulo = document.getElementById('form-titulo');
    const btnNovo = document.getElementById('btn-novo');
    const btnExcluir = document.getElementById('btn-excluir');
    const imagemInput = document.getElementById("produto-imagem");
    const preview = document.getElementById("preview-imagem");
    const toast = document.getElementById('toast');
    const inputPesquisa = document.getElementById('input-pesquisa');
    let itemAtivo = null;
    let debounceTimer;

    // --- NOVAS CONSTANTES (Gestão de Fornecedores) ---
    const btnGerirFornecedores = document.getElementById('btn-gerir-fornecedores');
    const modalFornecedores = document.getElementById('modal-fornecedores');
    const btnFecharModalFornecedores = document.getElementById('btn-fechar-modal-fornecedores');
    const formFornecedorModal = document.getElementById('form-fornecedor-modal');
    const listaFornecedoresModal = document.getElementById('lista-fornecedores-modal');
    const formTituloFornecedor = document.getElementById('form-titulo-fornecedor');
    const btnNovoFornecedor = document.getElementById('btn-novo-fornecedor');
    const btnExcluirFornecedor = document.getElementById('btn-excluir-fornecedor');
    let itemFornecedorAtivo = null;

    // --- NOVAS CONSTANTES (Campo Datalist de Fornecedor no Produto) ---
    const inputFornecedorNome = document.getElementById('produto-fornecedor-nome');
    const inputFornecedorId = document.getElementById('produto-fornecedor-id');
    const datalistFornecedores = document.getElementById('lista-fornecedores');

    // ==========================================================
    // FUNÇÕES DE GESTÃO DE PRODUTOS
    // ==========================================================

    inputPesquisa.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            carregarProdutos(inputPesquisa.value);
        }, 300);
    });

    async function carregarProdutos(termo = '') {
        try {
            const url = `api_produtos.php?acao=pesquisar&termo=${encodeURIComponent(termo)}`;
            const response = await fetch(url);
            const res = await response.json();
            
            listaResultados.innerHTML = '';
            if (res.sucesso && res.dados) {
                if (res.dados.length === 0) {
                    listaResultados.innerHTML = '<li style="text-align: center; color: #aaa;">Nenhum produto encontrado.</li>';
                } else {
                    res.dados.forEach(produto => {
                        const li = document.createElement('li');
                        li.className = 'produto-item';
                        li.dataset.id = produto.id;
                        li.innerHTML = `
                            <span class="produto-item-nome">${produto.nome}</span>
                            <small class="produto-item-detalhes">PVP: R$ ${parseFloat(produto.preco).toFixed(2)} | Estoque: ${produto.estoque}</small>
                        `;
                        li.addEventListener('click', (e) => carregarDetalhesProduto(produto.id, e.currentTarget));
                        listaResultados.appendChild(li);
                    });
                }
            } else if (res.erro) {
                showToast(res.erro, true);
            }
        } catch (error) {
            showToast("Erro de conexão ao carregar produtos.", true);
        }
    }

    async function carregarDetalhesProduto(id, targetElement) {
        try {
            const response = await fetch(`api_produtos.php?acao=detalhes&id=${id}`);
            const res = await response.json();
            if (res.sucesso && res.dados) {
                const produto = res.dados;
                formTitulo.textContent = `Editando: ${produto.nome}`;
                form.id.value = produto.id;
                form.nome.value = produto.nome;
                form.preco.value = produto.preco;
                form.estoque.value = produto.estoque;
                form.descricao.value = produto.descricao || '';
                form.imagem_existente.value = produto.imagem || '';
                
                // Preenche fornecedor
                form.fornecedor_id.value = produto.fornecedor_id || '';
                form.fornecedor_nome.value = produto.fornecedor_nome || '';

                if (produto.imagem) {
                    preview.src = `../${produto.imagem}`;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
                if (itemAtivo) itemAtivo.classList.remove('active');
                itemAtivo = targetElement;
                itemAtivo.classList.add('active');
            } else {
                showToast(res.erro, true);
            }
        } catch (error) {
            showToast("Erro de conexão ao carregar detalhes.", true);
        }
    }
    
    function limparFormulario() {
        formTitulo.textContent = 'Cadastrar Novo Produto';
        form.reset();
        form.id.value = '';
        preview.style.display = 'none';
        
        form.fornecedor_id.value = '';
        form.fornecedor_nome.value = '';
        
        if (itemAtivo) itemAtivo.classList.remove('active');
        itemAtivo = null;
    }

    function showToast(mensagem, isErro = false) {
        toast.textContent = mensagem;
        toast.className = `show ${isErro ? 'erro' : 'sucesso'}`;
        setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000);
    }

    btnNovo.addEventListener('click', limparFormulario);

    imagemInput.addEventListener("change", e => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                preview.src = ev.target.result;
                preview.style.display = "block";
            };
            reader.readAsDataURL(file);
        }
    });

    form.addEventListener("submit", async e => {
        e.preventDefault();
        if (!form.nome.value.trim() || !form.preco.value || form.estoque.value === '') {
            showToast("Preencha os campos obrigatórios: Nome, Preço e Estoque.", true);
            return;
        }
        
        // Validação do fornecedor
        if (form.fornecedor_nome.value.trim() === '') {
            form.fornecedor_id.value = '';
        } else {
            let idEncontrado = '';
            for (const option of datalistFornecedores.options) {
                if (option.value === form.fornecedor_nome.value) {
                    idEncontrado = option.dataset.id;
                    break;
                }
            }
            inputFornecedorId.value = idEncontrado;
        }

        const formData = new FormData(form);
        formData.append('acao', 'salvar');
        
        try {
            const response = await fetch('api_produtos.php', { method: 'POST', body: formData });
            const resultado = await response.json();
            if (resultado.sucesso) {
                showToast(resultado.mensagem);
                const eraNovo = !form.id.value;
                if(eraNovo) {
                    limparFormulario();
                }
                carregarProdutos(inputPesquisa.value);
            } else {
                showToast(resultado.erro, true);
            }
        } catch (error) {
            showToast("Erro de conexão ao salvar.", true);
        }
    });

    btnExcluir.addEventListener('click', async () => {
        const id = form.id.value;
        if (!id) {
            showToast('Nenhum produto selecionado para excluir.', true);
            return;
        }
        if (confirm('Tem a certeza que deseja excluir este produto? A ação não pode ser desfeita.')) {
            const formData = new FormData();
            formData.append('acao', 'excluir');
            formData.append('id', id);
            try {
                const response = await fetch('api_produtos.php', { method: 'POST', body: formData });
                const resultado = await response.json();
                if (resultado.sucesso) {
                    showToast(resultado.mensagem);
                    limparFormulario();
                    carregarProdutos();
                } else {
                    showToast(resultado.erro, true);
                }
            } catch (error) {
                showToast("Erro de conexão ao excluir.", true);
            }
        }
    });
    
    // ==========================================================
    // --- FUNÇÕES DE GESTÃO DE FORNECEDORES ---
    // ==========================================================

    async function carregarDatalistFornecedores() {
        try {
            const response = await fetch('api_fornecedores.php?acao=listar_ativos');
            const res = await response.json();
            
            datalistFornecedores.innerHTML = '';
            if (res.sucesso && res.dados) {
                res.dados.forEach(f => {
                    const option = document.createElement('option');
                    option.value = f.nome;
                    option.dataset.id = f.id;
                    datalistFornecedores.appendChild(option);
                });
            }
        } catch (error) {
            console.error("Erro datalist:", error);
        }
    }

    inputFornecedorNome.addEventListener('input', () => {
        const nome = inputFornecedorNome.value;
        let id = '';
        for (const option of datalistFornecedores.options) {
            if (option.value === nome) {
                id = option.dataset.id;
                break;
            }
        }
        inputFornecedorId.value = id;
        if (nome.trim() === '') {
            inputFornecedorId.value = '';
        }
    });

    // ==========================================================
    // --- FUNÇÕES DO MODAL DE FORNECEDORES ---
    // ==========================================================

    function abrirModalFornecedores() {
        modalFornecedores.style.display = 'flex';
        setTimeout(() => modalFornecedores.classList.add('show'), 10);
        limparFormularioFornecedor();
        carregarFornecedoresNoModal();
    }

    function fecharModalFornecedores() {
        modalFornecedores.classList.remove('show');
        setTimeout(() => modalFornecedores.style.display = 'none', 300);
    }
    
    async function carregarFornecedoresNoModal() {
        try {
            listaFornecedoresModal.innerHTML = '<li style="text-align: center; color: #aaa;">A carregar...</li>';
            const response = await fetch('api_fornecedores.php?acao=listar_todos');
            const res = await response.json();
            
            listaFornecedoresModal.innerHTML = '';
            if (res.sucesso && res.dados) {
                if (res.dados.length === 0) {
                     listaFornecedoresModal.innerHTML = '<li style="text-align: center; color: #aaa;">Nenhum fornecedor cadastrado.</li>';
                } else {
                    res.dados.forEach(f => {
                        const li = document.createElement('li');
                        li.className = 'fornecedor-item';
                        li.dataset.id = f.id;
                        
                        let status = f.ativo == 1 
                            ? `<span>Email: ${f.email || 'N/A'} | Tel: ${f.telefone || 'N/A'}</span>`
                            : `<span class="inativo">INATIVO</span>`;

                        li.innerHTML = `
                            <span class="fornecedor-item-nome">${f.nome}</span>
                            <small class="fornecedor-item-detalhes">${status}</small>
                        `;
                        li.addEventListener('click', (e) => carregarDetalhesFornecedorModal(f.id, e.currentTarget));
                        listaFornecedoresModal.appendChild(li);
                    });
                }
            } else {
                showToast(res.erro, true);
                listaFornecedoresModal.innerHTML = '<li style="text-align: center; color: #f00;">Erro ao carregar.</li>';
            }
        } catch (error) {
            showToast("Erro de conexão ao carregar fornecedores.", true);
        }
    }

    async function carregarDetalhesFornecedorModal(id, targetElement) {
        try {
            const response = await fetch(`api_fornecedores.php?acao=detalhes&id=${id}`);
            const res = await response.json();
            if (res.sucesso && res.dados) {
                const f = res.dados;
                formTituloFornecedor.textContent = `Editando: ${f.nome}`;
                formFornecedorModal.id.value = f.id;
                formFornecedorModal.nome.value = f.nome;
                formFornecedorModal.cpf_cnpj.value = f.cpf_cnpj || '';
                formFornecedorModal.telefone.value = f.telefone || '';
                formFornecedorModal.email.value = f.email || '';
                formFornecedorModal.endereco.value = f.endereco || '';
                formFornecedorModal.ativo.checked = (f.ativo == 1);

                if (itemFornecedorAtivo) itemFornecedorAtivo.classList.remove('active');
                itemFornecedorAtivo = targetElement;
                itemFornecedorAtivo.classList.add('active');
            } else {
                showToast(res.erro, true);
            }
        } catch (error) {
            showToast("Erro de conexão ao carregar detalhes do fornecedor.", true);
        }
    }
    
    function limparFormularioFornecedor() {
        formTituloFornecedor.textContent = 'Novo Fornecedor';
        formFornecedorModal.reset();
        formFornecedorModal.id.value = '';
        formFornecedorModal.ativo.checked = true;
        if (itemFornecedorAtivo) itemFornecedorAtivo.classList.remove('active');
        itemFornecedorAtivo = null;
    }

    async function salvarFornecedor(e) {
        e.preventDefault();
        if (!formFornecedorModal.nome.value.trim()) {
            showToast("O campo Nome é obrigatório.", true);
            return;
        }
        
        const formData = new FormData(formFornecedorModal);
        formData.append('acao', 'salvar');
        
        if (!formFornecedorModal.ativo.checked) {
            formData.set('ativo', '0');
        } else {
            formData.set('ativo', '1');
        }
        
        try {
            const response = await fetch('api_fornecedores.php', { method: 'POST', body: formData });
            const resultado = await response.json();
            
            if (resultado.sucesso) {
                showToast(resultado.dados.mensagem);
                limparFormularioFornecedor();
                carregarFornecedoresNoModal(); 
                carregarDatalistFornecedores(); 
            } else {
                showToast(resultado.erro, true);
            }
        } catch (error) {
            showToast("Erro de conexão ao salvar fornecedor.", true);
        }
    }

    async function excluirFornecedor() {
        const id = formFornecedorModal.id.value;
        if (!id) {
            showToast('Nenhum fornecedor selecionado para excluir.', true);
            return;
        }
        
        if (confirm('Tem a certeza que deseja excluir este fornecedor? \n\nOs produtos associados a ele NÃO serão excluídos, apenas desvinculados.')) {
            const formData = new FormData();
            formData.append('acao', 'excluir');
            formData.append('id', id);
            
            try {
                const response = await fetch('api_fornecedores.php', { method: 'POST', body: formData });
                const resultado = await response.json();
                
                if (resultado.sucesso) {
                    showToast(resultado.dados.mensagem);
                    limparFormularioFornecedor();
                    carregarFornecedoresNoModal();
                    carregarDatalistFornecedores();
                } else {
                    showToast(resultado.erro, true);
                }
            } catch (error) {
                showToast("Erro de conexão ao excluir fornecedor.", true);
            }
        }
    }

    // --- LISTENERS ---
    btnGerirFornecedores.addEventListener('click', abrirModalFornecedores);
    btnFecharModalFornecedores.addEventListener('click', fecharModalFornecedores);
    formFornecedorModal.addEventListener('submit', salvarFornecedor);
    btnNovoFornecedor.addEventListener('click', limparFormularioFornecedor);
    btnExcluirFornecedor.addEventListener('click', excluirFornecedor);
    
    modalFornecedores.addEventListener('click', e => {
        if (e.target === modalFornecedores) {
            fecharModalFornecedores();
        }
    });

    carregarProdutos();
    carregarDatalistFornecedores();
    limparFormulario();
});
</script>
<script src="../assets/js/theme.js" defer></script>
</body>
</html>