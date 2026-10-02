<?php

require_once "config.php";

verificarLogin();

$mensagem = "";
$erro = "";


/* =========================
   EXCLUIR PRODUTO
========================= */

if (isset($_GET["excluir"])) {

    $id = intval($_GET["excluir"]);

    try {

        /*
         * Exclusão lógica.
         * Assim não perdemos o histórico das movimentações.
         */

        $sql = "UPDATE produto
                SET ativo = 0
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->execute([$id]);

        $mensagem = "Produto excluído com sucesso.";

    } catch (PDOException $e) {

        $erro = "Não foi possível excluir o produto.";

    }

}


/* =========================
   SALVAR PRODUTO
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = intval($_POST["id"] ?? 0);

    $codigo = trim($_POST["codigo"]);
    $nome = trim($_POST["nome"]);
    $categoria = trim($_POST["categoria"]);
    $preco = str_replace(",", ".", $_POST["preco"]);
    $estoque = intval($_POST["estoque_atual"]);
    $estoqueMinimo = intval($_POST["estoque_minimo"]);


    if (
        $codigo == "" ||
        $nome == "" ||
        $categoria == "" ||
        $preco == ""
    ) {

        $erro = "Preencha todos os campos obrigatórios.";

    } elseif ($preco < 0) {

        $erro = "O preço não pode ser negativo.";

    } elseif ($estoque < 0 || $estoqueMinimo < 0) {

        $erro = "O estoque não pode ser negativo.";

    } else {

        try {

            if ($id > 0) {

                /* EDITAR */

                $sql = "
                    UPDATE produto SET
                        codigo = ?,
                        nome = ?,
                        categoria = ?,
                        preco = ?,
                        estoque_atual = ?,
                        estoque_minimo = ?
                    WHERE id = ?
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $codigo,
                    $nome,
                    $categoria,
                    $preco,
                    $estoque,
                    $estoqueMinimo,
                    $id
                ]);

                $mensagem = "Produto alterado com sucesso.";

            } else {

                /* NOVO */

                $sql = "
                    INSERT INTO produto
                    (
                        codigo,
                        nome,
                        categoria,
                        preco,
                        estoque_atual,
                        estoque_minimo,
                        ativo
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $codigo,
                    $nome,
                    $categoria,
                    $preco,
                    $estoque,
                    $estoqueMinimo
                ]);

                $mensagem = "Produto cadastrado com sucesso.";

            }

        } catch (PDOException $e) {

            if ($e->getCode() == "23000") {

                $erro = "O código informado já está cadastrado.";

            } else {

                $erro = "Erro ao salvar o produto.";

            }

        }

    }

}


/* =========================
   EDITAR
========================= */

$produtoEditar = null;

if (isset($_GET["editar"])) {

    $id = intval($_GET["editar"]);

    $sql = "
        SELECT *
        FROM produto
        WHERE id = ?
        AND ativo = 1
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([$id]);

    $produtoEditar = $stmt->fetch(PDO::FETCH_ASSOC);

}


/* =========================
   BUSCA
========================= */

$busca = trim($_GET["busca"] ?? "");

if ($busca != "") {

    $sql = "
        SELECT *
        FROM produto
        WHERE ativo = 1
        AND (
            codigo LIKE ?
            OR nome LIKE ?
            OR categoria LIKE ?
        )
        ORDER BY nome ASC
    ";

    $termo = "%" . $busca . "%";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $termo,
        $termo,
        $termo
    ]);

} else {

    $sql = "
        SELECT *
        FROM produto
        WHERE ativo = 1
        ORDER BY nome ASC
    ";

    $stmt = $conn->query($sql);

}

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>
        Cadastro de Produtos - Estoque Industrial
    </title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            background: #f5f7fb;
            color: #222;
        }

        h1, h2 {
            margin-bottom: 12px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border: 1px solid #dfe6ee;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: middle;
        }

        thead th {
            background: #1f3a5f;
            color: #fff;
            font-weight: 600;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        tbody tr:hover {
            background: #eef6ff;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            background: #e6f4ea;
            color: #166534;
        }

        .status-low {
            background: #fdecec;
            color: #b42318;
        }

        .sucesso, .erro {
            padding: 10px 12px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: 600;
        }

        .sucesso {
            background: #e9f9ef;
            color: #166534;
            border: 1px solid #b7e4c7;
        }

        .erro {
            background: #feeceb;
            color: #b42318;
            border: 1px solid #f5c2c7;
        }

        a {
            color: #1f3a5f;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        input, button {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #cfd8e3;
        }

        button {
            background: #1f3a5f;
            color: #fff;
            border: none;
            cursor: pointer;
        }
    </style>

</head>

<body>

<div class="container">

<h1>Cadastro de Produtos/Insumos - Estoque Industrial</h1>

<p>
    <a href="index.php">
        ⬅ Voltar
    </a>
</p>

<hr>

<h2>
    <?= $produtoEditar ? "Editar Produto" : "Novo Produto" ?>
</h2>

<?php if ($mensagem != ""): ?>

    <p class="sucesso">
        <?= htmlspecialchars($mensagem) ?>
    </p>

<?php endif; ?>

<?php if ($erro != ""): ?>

    <p class="erro">
        <?= htmlspecialchars($erro) ?>
    </p>

<?php endif; ?>


<form method="POST">

    <input
        type="hidden"
        name="id"
        value="<?= $produtoEditar["id"] ?? 0 ?>"
    >

    <p>
        Código: *
        <input
            type="text"
            name="codigo"
            required
            value="<?= htmlspecialchars($produtoEditar["codigo"] ?? "") ?>"
        >
    </p>

    <p>
        Nome: *
        <input
            type="text"
            name="nome"
            required
            value="<?= htmlspecialchars($produtoEditar["nome"] ?? "") ?>"
        >
    </p>

    <p>
        Categoria: *
        <input
            type="text"
            name="categoria"
            required
            value="<?= htmlspecialchars($produtoEditar["categoria"] ?? "") ?>"
        >
    </p>

    <p>
        Preço/Custo: *
        <input
            type="number"
            name="preco"
            step="0.01"
            min="0"
            required
            value="<?= htmlspecialchars($produtoEditar["preco"] ?? "") ?>"
        >
    </p>

    <p>
        Estoque Atual:
        <input
            type="number"
            name="estoque_atual"
            min="0"
            value="<?= htmlspecialchars($produtoEditar["estoque_atual"] ?? 0) ?>"
        >
    </p>

    <p>
        Estoque Mínimo:
        <input
            type="number"
            name="estoque_minimo"
            min="0"
            value="<?= htmlspecialchars($produtoEditar["estoque_minimo"] ?? 5) ?>"
        >
    </p>

    <p>

        <button type="submit">
            Salvar
        </button>

        <?php if ($produtoEditar): ?>

            <a href="produtos.php">
                Cancelar
            </a>

        <?php endif; ?>

    </p>

</form>

<hr>

<div class="card">
    <h2>Lista de Produtos/Insumos</h2>

    <form method="GET">
        <label>
            Buscar
            <input
                type="text"
                name="busca"
                placeholder="Nome, Código ou Categoria"
                value="<?= htmlspecialchars($busca) ?>"
            >
        </label>

        <button type="submit">
            Buscar
        </button>

        <a href="produtos.php">
            Limpar
        </a>
    </form>

    <br>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Estoque Mínimo</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <?php $baixoEstoque = $produto["estoque_atual"] <= $produto["estoque_minimo"]; ?>

                <tr>
                    <td><?= htmlspecialchars($produto["codigo"]) ?></td>
                    <td><?= htmlspecialchars($produto["nome"]) ?></td>
                    <td><?= htmlspecialchars($produto["categoria"]) ?></td>
                    <td>R$ <?= number_format($produto["preco"], 2, ",", ".") ?></td>
                    <td><?= $produto["estoque_atual"] ?></td>
                    <td><?= $produto["estoque_minimo"] ?></td>
                    <td>
                        <?php if ($baixoEstoque): ?>
                            <span class="status status-low">ESTOQUE BAIXO</span>
                        <?php else: ?>
                            <span class="status">Normal</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="produtos.php?editar=<?= $produto["id"] ?>">Editar</a>
                        |
                        <a href="produtos.php?excluir=<?= $produto["id"] ?>" onclick="return confirm('Deseja excluir este produto?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>

</body>

</html>