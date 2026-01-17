document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("form-produto");
    const imagemInput = document.querySelector("input[name='imagem']");
    const preview = document.getElementById("preview-imagem");

    form.addEventListener("submit", (e) => {
        const nome = form.nome.value.trim();
        const preco = parseFloat(form.preco.value);
        const estoque = parseInt(form.estoque.value);

        if (!nome || isNaN(preco) || isNaN(estoque)) {
            e.preventDefault();
            alert("Preencha os campos obrigatórios: Nome, Preço e Estoque.");
            return;
        }

        if (preco <= 0) {
            e.preventDefault();
            alert("O preço deve ser maior que zero.");
            return;
        }

        if (estoque < 0) {
            e.preventDefault();
            alert("O estoque não pode ser negativo.");
            return;
        }
    });

    // Pré-visualização da imagem
    imagemInput?.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                preview.src = ev.target.result;
                preview.style.display = "block";
            };
            reader.readAsDataURL(file);
        }
    });
});
