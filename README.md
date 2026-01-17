💻 Sobre o Projeto

O PDV CANVI é um sistema web full-stack criado para automatizar o fluxo de vendas e a gestão operacional de um quiosque/estabelecimento.

O projeto foi desenvolvido do zero em PHP 8, sem frameworks, utilizando PDO com boas práticas de segurança e uma organização inspirada em MVC (separação de Models, páginas e APIs internas).
A aplicação roda bem em hospedagem compartilhada (cPanel/HostGator) e foi pensada para ser simples de manter e evoluir.

🏷️ Versão Atual

✅ v1.4 (produção em pdv.ilha.tech)
Evolução focada em usabilidade no PDV, qualidade operacional (estoque), impressão sem “popups” e rastreabilidade (auditoria).

✨ Funcionalidades Principais
🛒 Frente de Caixa (PDV / Vendas)

Vendas ágeis com interface otimizada para lançamento rápido.

Bip de confirmação ao adicionar produto (feedback sonoro para operação rápida).

Pesquisa inteligente de produtos:

digita para filtrar,

ENTER adiciona o 1º resultado,

setas ↑/↓ navegam entre resultados.

Carrinho da Mesa: quando a mesa é enviada ao PDV, os itens são carregados automaticamente para finalizar a venda.

Pagamento com fluxo simples (dinheiro/cartão/pix).

Recibo/Impressão direta:

impressão sem abrir nova aba (sem “popup”),

experiência mais profissional e rápida no atendimento.

🍽 Gestão Operacional (Mesas)

Mapa de mesas em tempo real:

Livre / Ocupada / Em Pagamento / Chamar Garçom.

Comanda da mesa:

adicionar/remover itens,

subtotal atualizado,

descrição/cliente na mesa.

Extrato da mesa:

impressão direta (sem abrir outra aba),

ideal para pré-conta e conferência na mesa.

Fechar conta & ir ao PDV:

substitui alert/confirm por modal visual (mais profissional).

📦 Estoque e Produtos

Cadastro de produtos com preço e estoque.

Baixa automática de estoque ao finalizar venda no PDV.

Bloqueio de venda sem estoque (opcional):

se configurado, impede vender quando estoque = 0,

impede ultrapassar a quantidade disponível.

Preparado para evolução com alertas de nível baixo e fornecedores (próximas melhorias).

👥 Clientes (CPF e CNPJ)

Cadastro agora aceita CPF ou CNPJ no mesmo campo.

Validação real de CPF e CNPJ (evita cadastro errado/falso).

Auto-preenchimento via Receita (API gratuita):

ao informar CNPJ, o sistema consulta automaticamente,

preenche Razão Social / Nome e dados de endereço quando disponíveis.

💰 Caixa e Auditoria

Controle de sessão de caixa (abertura/fechamento).

Movimentações:

Entrada (suprimento),

Saída (sangria/despesa/outros).

Auditoria (Supervisor):

registros de movimentações com usuário + data/hora,

rastreabilidade operacional (quem fez o quê e quando).

🎨 Experiência do Usuário (UI/UX)

Tema escuro/claro persistente (LocalStorage).

Ajustes de contraste no tema claro, corrigindo cartões que ficavam “branco no branco” (ex.: card PDV).

Botão Tela Cheia no topo (ao lado do botão de tema), com ícone que alterna ao entrar/sair.

Interface com foco em operação rápida (toques mínimos, feedback visual/sonoro, modais profissionais).

⚙️ Diferenciais Técnicos (Atualizados)

Sem frameworks: PHP puro + JavaScript puro (Vanilla).

APIs internas em PHP consumidas por fetch() no front.

PDO com tratamento de erros e estrutura padronizada.

Migrações / criação de tabelas automatizada e controlada (bootstrap / db / migrations), adequada para cPanel.

Arquitetura organizada:

models/ (regras e acesso ao banco),

public/ (páginas do sistema),

public/api_*.php (endpoints internos),

migrations/ (estrutura do banco),

assets/ (CSS/JS),

public/som/ (feedback sonoro do PDV).

✅ Melhorias Recentes (changelog resumido)
v1.3

CPF/CNPJ em clientes + validação + consulta Receita (CNPJ).

Botão tela cheia no topo.

Login <title> igual ao Nome do Sistema nas configurações.

Correção de card/contraste no tema claro.

Impressão do extrato da mesa sem abrir nova aba.

Modal de confirmação (substitui alert/confirm) em operações críticas.

v1.4

Pesquisa no PDV com ENTER (adiciona) e navegação ↑/↓.

Bip ao adicionar item.

Estoque: baixa automática + bloqueio opcional sem estoque.

Recibo/Impressão direta sem popup.

Auditoria para supervisor (movimentações/sessões com usuário e horário).

📌 Observações Importantes

Em navegadores modernos, impressão “100% silenciosa” é bloqueada por segurança.
O sistema faz o melhor possível: abre direto o diálogo de impressão, sem abrir outra aba.

A consulta de CNPJ usa API pública gratuita, dependendo da disponibilidade do serviço.
