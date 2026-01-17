<?php
// 1. Carrega o ambiente
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// 2. Inicializa variáveis
$produtos = [];
$moeda = 'R$';

try {
    $configModel = new Configuracao();
    $configuracoes = $configModel->carregarConfiguracoes();
    $moeda = $configuracoes['moeda_simbolo'] ?? 'R$';

    if (class_exists('Produto')) {
        $produtoModel = new Produto();
        $produtos = $produtoModel->listar(['ativo' => 1]);
    }
} catch (Exception $e) {
    error_log("Aviso em mesas.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Controle de Mesas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="header-inner header-inner--standard">
        <div class="header-start">
            <a href="../index.php" class="btn-header-voltar">Voltar</a>
            <a href="pdv.php" class="btn btn-secondary" style="margin-left: 10px;">Ir para PDV</a>
        </div>
        <span class="brand">CONTROLE DE MESAS</span>
        <div class="header-actions">
            <button class="fullscreen-toggle" id="fullscreen-toggle" title="Tela cheia" aria-pressed="false">⛶</button>
<button class="theme-toggle" id="theme-toggle"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
        </div>
    </div>
</header>

<div class="container">
    <div class="mesas-wrapper">
        <div id="mesas-grid" class="mesas-grid">
            <p style="text-align: center; color: #aaa;">A carregar mesas...</p>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-mesa">
    <div class="modal-box" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="modal-mesa-titulo">Mesa #0</h2>
            <button class="btn-fechar-modal" id="btn-fechar-x">&times;</button>
        </div>
        <div class="modal-body">

            <div id="aviso-fechamento" style="display:none; background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); color: var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                <strong style="display:block; margin-bottom:5px; font-size:1.1em;">⚠️ Mesa em Processo de Pagamento</strong>
                Esta mesa foi enviada para o PDV. Finalize a venda lá ou cancele aqui.
                <div style="margin-top: 15px;">
                    <button type="button" id="btn-cancelar-fechamento" class="btn btn-secondary" style="border: 1px solid var(--danger); color: var(--danger);">
                        🔓 Reabrir Mesa (Cancelar Fechamento)
                    </button>
                </div>
            </div>

            <div class="form-descricao" style="margin-bottom: 16px;">
                <label for="mesa-descricao">Descrição / Cliente</label>
                <input type="text" id="mesa-descricao" placeholder="Ex: João da Silva">
            </div>

            <h4 style="margin-top: 0; margin-bottom: 8px;">Itens Consumidos</h4>
            <div class="comanda-container" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-dark); border-radius: 4px; margin-bottom: 12px;">
                <table class="tabela-relatorio" style="margin-top: 0; margin-bottom:0; width: 100%;">
                    <thead>
                    <tr>
                        <th style="width: 60px; padding: 8px;">Qtd.</th>
                        <th style="padding: 8px;">Produto</th>
                        <th style="text-align: right; padding: 8px;">Total</th>
                        <th style="width: 40px; padding: 8px;"></th>
                    </tr>
                    </thead>
                    <tbody id="lista-itens-mesa"></tbody>
                </table>
            </div>

            <div class="subtotal" id="mesa-subtotal" style="text-align: right; font-weight: 600; margin-bottom: 16px; font-size: 1.2em; color: var(--success);">
                Subtotal: <?php echo $moeda; ?> 0,00
            </div>

            <form id="form-add-item" class="form-add-item" style="display: flex; gap: 10px; align-items: flex-end; border-top: 1px solid var(--border-dark); padding-top: 15px;">
                <div style="flex-grow: 1;">
                    <label for="produto-select">Adicionar Produto</label>
                    <select id="produto-select" style="margin-bottom: 0;">
                        <option value="">Selecione...</option>
                        <?php if (!empty($produtos)): foreach ($produtos as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['nome']); ?> -
                                <?php echo $moeda . ' ' . number_format($p['preco'], 2, ',', '.'); ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div style="width: 80px;">
                    <label for="item-quantidade">Qtd.</label>
                    <input type="number" id="item-quantidade" value="1" min="1" style="margin-bottom: 0;">
                </div>
                <button type="submit" class="btn btn-primary" style="height: 42px;">+</button>
            </form>
        </div>

        <div class="modal-footer" style="justify-content: space-between;">
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-secondary" id="btn-imprimir">🖨️ Extrato</button>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" id="btn-transferir">✅ Fechar Conta & Ir ao PDV</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL DE CONFIRMAÇÃO (substitui confirm/alert)
     ========================================================= -->
<div class="modal-overlay" id="modal-confirm" style="display:none;">
    <div class="modal-box" style="max-width: 520px;">
        <div class="modal-header">
            <h2 id="confirm-title" style="margin:0; font-size: 1.1em;">Confirmação</h2>
            <button class="btn-fechar-modal" id="btn-confirm-fechar">&times;</button>
        </div>
        <div class="modal-body">
            <div id="confirm-message" style="line-height: 1.4; color: var(--text);">
                Tem certeza?
            </div>
        </div>
        <div class="modal-footer" style="justify-content:flex-end; gap: 10px;">
            <button class="btn btn-secondary" id="btn-confirm-cancelar">Cancelar</button>
            <button class="btn btn-primary" id="btn-confirm-ok">Confirmar</button>
        </div>
    </div>
</div>



    <!-- Modal de confirmação (sem alert) -->
<div id="toast"></div>

<script>
const MOEDA = '<?php echo $moeda; ?>';

document.addEventListener('DOMContentLoaded', function() {
    const mesasGrid = document.getElementById('mesas-grid');
    const modal = document.getElementById('modal-mesa');
    const modalTitulo = document.getElementById('modal-mesa-titulo');
    const listaItens = document.getElementById('lista-itens-mesa');
    const subtotalDisplay = document.getElementById('mesa-subtotal');
    const formAdd = document.getElementById('form-add-item');
    const inputDesc = document.getElementById('mesa-descricao');
    const btnTransferir = document.getElementById('btn-transferir');
    const btnCancelarFecho = document.getElementById('btn-cancelar-fechamento');
    const divAvisoFechamento = document.getElementById('aviso-fechamento');

    let currentMesaId = null;

    // ==========================================================
    // 🖨️ EXTRATO SEM ABRIR ABA (impressão via iframe invisível)
    // ==========================================================
    function imprimirExtratoMesaSemAba(mesaId) {
        if (!mesaId) return;

        const old = document.getElementById('printFrameExtratoMesa');
        if (old) old.remove();

        const iframe = document.createElement('iframe');
        iframe.id = 'printFrameExtratoMesa';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.opacity = '0';
        iframe.style.pointerEvents = 'none';

        const url = "extrato_mesa.php?id=" + encodeURIComponent(mesaId);

        iframe.onload = function () {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                window.open(url, "_blank", "noopener,noreferrer");
            }

            setTimeout(() => {
                try { iframe.remove(); } catch (e) {}
            }, 15000);
        };

        iframe.src = url;
        document.body.appendChild(iframe);
    }

    const btnImprimir = document.getElementById("btn-imprimir");
        if (btnImprimir) {
            btnImprimir.addEventListener("click", () => {
                if (!currentMesaId) {
                    showToast("Selecione uma mesa primeiro.", true);
                    return;
                }
                const url = "extrato_mesa.php?id=" + encodeURIComponent(currentMesaId);
                printDirect(url); // imprime sem abrir nova aba
            });
        }

        function printDirect(url) {
            try {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.opacity = '0';
                iframe.src = url;
                document.body.appendChild(iframe);

                iframe.onload = () => {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    } catch (e) {}

                    // remove depois de um tempo (evita acumular iframes)
                    setTimeout(() => {
                        try { iframe.remove(); } catch (e) {}
                    }, 2000);
                };
            } catch (e) {
                // fallback: abre e imprime
                window.open(url, '_blank', 'noopener,noreferrer');
            }
        }

    // ==========================================================
    // MODAL CONFIRM (substitui confirm())
    // ==========================================================
    const modalConfirm = document.getElementById('modal-confirm');
    const confirmTitle = document.getElementById('confirm-title');
    const confirmMsg = document.getElementById('confirm-message');
    const btnConfirmOk = document.getElementById('btn-confirm-ok');
    const btnConfirmCancelar = document.getElementById('btn-confirm-cancelar');
    const btnConfirmFechar = document.getElementById('btn-confirm-fechar');

    let confirmResolver = null;

    function closeConfirm(result) {
        if (modalConfirm) modalConfirm.style.display = 'none';
        if (confirmResolver) {
            confirmResolver(result);
            confirmResolver = null;
        }
    }

    function setOkButtonStyle(tone) {
        // tone: 'primary' | 'danger'
        if (!btnConfirmOk) return;
        if (tone === 'danger') {
            btnConfirmOk.className = 'btn btn-danger';
        } else {
            btnConfirmOk.className = 'btn btn-primary';
        }
    }

    function confirmModal(opts) {
        // opts: { title, message, okText, cancelText, tone }
        const options = Object.assign({
            title: 'Confirmação',
            message: 'Tem certeza?',
            okText: 'Confirmar',
            cancelText: 'Cancelar',
            tone: 'primary'
        }, (opts || {}));

        return new Promise((resolve) => {
            confirmResolver = resolve;

            if (confirmTitle) confirmTitle.textContent = options.title;
            if (confirmMsg) confirmMsg.textContent = options.message;

            if (btnConfirmOk) btnConfirmOk.textContent = options.okText;
            if (btnConfirmCancelar) btnConfirmCancelar.textContent = options.cancelText;

            setOkButtonStyle(options.tone);

            modalConfirm.style.display = 'flex';

            setTimeout(() => {
                try { btnConfirmOk.focus(); } catch(e) {}
            }, 30);
        });
    }

    btnConfirmOk.addEventListener('click', () => closeConfirm(true));
    btnConfirmCancelar.addEventListener('click', () => closeConfirm(false));
    btnConfirmFechar.addEventListener('click', () => closeConfirm(false));

    modalConfirm.addEventListener('click', (e) => {
        if (e.target === modalConfirm) closeConfirm(false);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalConfirm.style.display === 'flex') {
            closeConfirm(false);
        }
    });

    async function carregarMesas() {
        try {
            const res = await fetch('api_mesas.php?acao=listar');
            const json = await res.json();
            mesasGrid.innerHTML = '';

            if (json.sucesso && json.dados) {
                if(json.dados.length === 0) {
                    mesasGrid.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:#aaa;">Nenhuma mesa configurada.</p>';
                    return;
                }

                json.dados.forEach(m => {
                    if(m.ativa === undefined || m.ativa == 1) {
                        const div = document.createElement('div');
                        let corBorda = 'var(--success)';
                        let corIcone = 'var(--success)';
                        let statusTexto = 'Livre';

                        if(m.status === 'ocupada') { corBorda = '#3b82f6'; corIcone = '#3b82f6'; statusTexto = 'Ocupada'; }
                        if(m.status === 'em_fechamento') { corBorda = 'var(--danger)'; corIcone = 'var(--danger)'; statusTexto = 'Em Pagamento'; }
                        if(m.status === 'reatendimento') { corBorda = 'var(--warn)'; corIcone = 'var(--warn)'; statusTexto = 'Chamar Garçom'; }

                        div.className = 'mesa-card';
                        div.style.borderColor = corBorda;
                        div.innerHTML = `
                            <svg class="mesa-icon" viewBox="0 0 24 24" fill="${corIcone}" style="width:40px; height:40px; margin:0 auto 10px auto; display:block;">
                                <path d="M2 7h20v2H2zM4 9v11h16V9H4zm2 2h12v7H6v-7z"/>
                            </svg>
                            <div class="numero" style="font-size: 1.2em;">Mesa ${m.numero}</div>
                            <div class="descricao" style="font-size: 0.85em; color: #aaa;">${m.descricao || statusTexto}</div>
                        `;
                        div.addEventListener('click', () => abrirMesa(m.id));
                        mesasGrid.appendChild(div);
                    }
                });
            }
        } catch (e) { console.error(e); }
    }

    async function abrirMesa(id) {
        currentMesaId = id;
        modal.style.display = 'flex';
        listaItens.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:10px;">A carregar...</td></tr>';

        try {
            const res = await fetch(`api_mesas.php?acao=buscar_itens&mesa_id=${id}`);
            const json = await res.json();

            if (json.sucesso) {
                const mesa = json.dados.mesa;
                const itens = json.dados.itens;
                const total = json.dados.total;

                modalTitulo.textContent = `Mesa #${mesa.numero}`;
                inputDesc.value = mesa.descricao || '';
                subtotalDisplay.textContent = `Subtotal: ${MOEDA} ${parseFloat(total).toFixed(2).replace('.', ',')}`;

                if (mesa.status === 'em_fechamento') {
                    divAvisoFechamento.style.display = 'block';
                    formAdd.style.display = 'none';
                    btnTransferir.style.display = 'none';
                } else {
                    divAvisoFechamento.style.display = 'none';
                    formAdd.style.display = 'flex';
                    btnTransferir.style.display = 'flex';
                }

                listaItens.innerHTML = '';
                if (itens.length > 0) {
                    itens.forEach(item => {
                        listaItens.innerHTML += `
                            <tr class="item-mesa-row">
                                <td style="padding:8px;">${item.quantidade}x</td>
                                <td style="padding:8px;">${item.nome}</td>
                                <td style="padding:8px; text-align:right;">${parseFloat(item.preco * item.quantidade).toFixed(2)}</td>
                                <td style="padding:8px;">
                                    <button type="button" onclick="removerItem(${item.item_id})" style="background:transparent; color:var(--danger); border:none; cursor:pointer; font-weight:bold; font-size:16px;">&times;</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    listaItens.innerHTML = '<tr class="empty-row"><td colspan="4" style="text-align:center; padding:15px; color:#777;">Mesa vazia.</td></tr>';
                }
            }
        } catch (e) { console.error(e); }
    }

    formAdd.addEventListener('submit', async (e) => {
        e.preventDefault();
        const prodId = document.getElementById('produto-select').value;
        const qtd = document.getElementById('item-quantidade').value;
        if(!prodId) return showToast('Selecione um produto.', true);
        try {
            const res = await fetch('api_mesas.php', {
                method: 'POST',
                body: JSON.stringify({ acao: 'adicionar_item', mesa_id: currentMesaId, produto_id: prodId, quantidade: qtd })
            });
            const json = await res.json();
            if(json.sucesso) {
                abrirMesa(currentMesaId);
                carregarMesas();
                document.getElementById('produto-select').value = "";
                document.getElementById('item-quantidade').value = "1";
            } else {
                showToast(json.erro, true);
            }
        } catch(e) { showToast('Erro ao adicionar.', true); }
    });

    // --- TRANSFERIR (modal mais PDV) ---
    btnTransferir.addEventListener('click', async () => {
        const temItens = listaItens.querySelectorAll('tr.item-mesa-row').length > 0;
        if(!temItens) {
            return showToast("Adicione itens antes de fechar.", true);
        }

        const ok = await confirmModal({
            title: 'Fechar conta desta mesa?',
            message: 'A mesa será enviada para o PDV para finalizar o pagamento.',
            okText: 'Ir para o PDV',
            cancelText: 'Voltar',
            tone: 'primary'
        });
        if (!ok) return;

        try {
            const res = await fetch('api_mesas.php', {
                method: 'POST',
                body: JSON.stringify({ acao: 'transferir_para_pdv', mesa_id: currentMesaId })
            });
            const json = await res.json();

            if (json.sucesso) {
                sessionStorage.setItem('carrinhoDaMesa', JSON.stringify(json.dados));
                window.location.href = 'pdv.php';
            } else {
                showToast(json.erro || 'Erro ao transferir.', true);
            }
        } catch (e) { showToast('Erro de conexão.', true); }
    });

    // --- REABRIR MESA (modal mais PDV) ---
    btnCancelarFecho.addEventListener('click', async () => {
        const ok = await confirmModal({
            title: 'Reabrir mesa?',
            message: 'Isso cancela o fechamento e a mesa volta para atendimento.',
            okText: 'Reabrir Mesa',
            cancelText: 'Manter em Pagamento',
            tone: 'danger'
        });
        if(!ok) return;

        try {
            const res = await fetch('api_mesas.php', {
                method: 'POST',
                body: JSON.stringify({ acao: 'cancelar_fechamento', mesa_id: currentMesaId })
            });
            const json = await res.json();
            if(json.sucesso) {
                showToast('Mesa reaberta.');
                abrirMesa(currentMesaId);
                carregarMesas();
            }
        } catch(e) {}
    });

    inputDesc.addEventListener('change', async () => {
        await fetch('api_mesas.php', {
            method: 'POST',
            body: JSON.stringify({ acao: 'salvar_descricao', mesa_id: currentMesaId, descricao: inputDesc.value })
        });
        carregarMesas();
    });

    window.removerItem = async (itemId) => {
        const ok = await confirmModal({
            title: 'Remover item da mesa?',
            message: 'Este item será removido da comanda.',
            okText: 'Remover Item',
            cancelText: 'Cancelar',
            tone: 'danger'
        });
        if(!ok) return;

        await fetch('api_mesas.php', { method: 'POST', body: JSON.stringify({ acao: 'remover_item', item_id: itemId }) });
        abrirMesa(currentMesaId);
        carregarMesas();
    }

    function showToast(msg, erro) {
        const t = document.getElementById('toast');
        if(t) {
            t.textContent = msg;
            t.className = erro ? 'show erro' : 'show sucesso';
            setTimeout(() => t.className = '', 3000);
        } else { alert(msg); }
    }

    document.getElementById('btn-fechar-x').addEventListener('click', () => {
        modal.style.display = 'none';
    });

    carregarMesas();
});
</script>
<script src="../assets/js/theme.js" defer></script>
</body>
</html>
