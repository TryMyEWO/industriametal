<?php

require_once "config.php";

verificarLogin();

$mensagem = "";
$erro = "";
$alerta = "";


/* ==========================================
   REGISTRAR MOVIMENTAÇÃO
========================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $produtoId = intval($_POST["produto_id"] ?? 0);
    $tipo = intval($_POST["tipo"] ?? 0);
    $quantidade = intval($_POST["quantidade"] ?? 0);
    $data = $_POST["data"] ?? "";


    /* VALIDAÇÕES */

    if ($produtoId <= 0) {

        $erro = "Selecione um produto.";

    } elseif ($tipo != 1 && $tipo != 2) {

        $erro = "Selecione Entrada ou Saída.";

    } elseif ($quantidade <= 0) {

        $erro = "A quantidade deve ser maior que zero.";

    } elseif ($data == "") {

        $erro = "Informe a data da movimentação.";

    } else {

        try {

            $conn->beginTransaction();


            /* BUSCAR PRODUTO */

            $sql = "
                SELECT *
                FROM produto
                WHERE id = ?
                AND ativo = 1
                FOR UPDATE
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$produtoId]);

            $produto = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$produto) {

                throw new Exception(
                    "Produto não encontrado."
                );

            }


            /* SALDO ANTERIOR */

            $saldoAnterior = intval(
                $produto["estoque_atual"]
            );


            /* ==========================================
               ENTRADA
            ========================================== */

            if ($tipo == 1) {

                $novoSaldo =
                    $saldoAnterior + $quantidade;

            }


            /* ==========================================
               SAÍDA
            ========================================== */

            else {

                $novoSaldo =
                    $saldoAnterior - $quantidade;


                if ($novoSaldo < 0) {

                    throw new Exception(
                        "Não existe estoque suficiente para realizar esta saída."
                    );

                }

            }


            /* ==========================================
               REGISTRAR MOVIMENTAÇÃO
            ========================================== */

            $sql = "
                INSERT INTO movimentacao
                (
                    tipo,
                    data,
                    quantidade,
                    saldo_anterior,
                    produto_id,
                    usuario_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $tipo,
                $data,
                $quantidade,
                $saldoAnterior,
                $produtoId,
                $_SESSION["usuario_id"]
            ]);


            /* ==========================================
               ATUALIZAR ESTOQUE
            ========================================== */

            $sql = "
                UPDATE produto
                SET estoque_atual = ?
                WHERE id = ?
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $novoSaldo,
                $produtoId
            ]);


            $conn->commit();


            $mensagem = "Movimentação registrada com sucesso.";


            /* ==========================================
               VERIFICAR ESTOQUE MÍNIMO
            ========================================== */

            if ($novoSaldo <= $produto["estoque_minimo"]) {

                $alerta = sprintf(
                    "ATENÇÃO: o produto %s está com estoque igual ou abaixo do mínimo. Saldo atual: %d | Estoque mínimo: %d",
                    $produto["nome"],
                    $novoSaldo,
                    $produto["estoque_minimo"]
                );

            }


        } catch (Exception $e) {

            if ($conn->inTransaction()) {

                $conn->rollBack();

            }

            $erro = $e->getMessage();

        }

    }

}


/* ==========================================
   LISTAR PRODUTOS
========================================== */

$sql = "
    SELECT *
    FROM produto
    WHERE ativo = 1
    ORDER BY nome ASC
";

$produtos = $conn
    ->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   HISTÓRICO
========================================== */

$sql = "
    SELECT
        m.id,
        m.data,
        p.nome AS produto,
        m.tipo,
        m.quantidade,
        m.saldo_anterior,
        u.nome AS usuario

    FROM movimentacao m

    INNER JOIN produto p
        ON p.id = m.produto_id

    INNER JOIN usuario u
        ON u.id = m.usuario_id

    ORDER BY m.id DESC
";

$historico = $conn
    ->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>
        Gestão de Estoque - Sistema Industrial
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
            max-width: 1100px;
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
            overflow: hidden;
            border-radius: 8px;
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

        .sucesso, .erro, .alerta {
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

        .alerta {
            background: #fff4d6;
            color: #8a5b00;
            border: 1px solid #f5d28b;
        }

        a {
            color: #1f3a5f;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>

</head>

<body>

<div class="container">

<h1>Gestão de Estoque - Sistema Industrial</h1>


<p>

    <a href="index.php">
        ⬅ Voltar
    </a>

</p>


<hr>


<h2>Nova Movimentação de Estoque</h2>


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


<?php if ($alerta != ""): ?>

    <p class="alerta">

        <?= htmlspecialchars($alerta) ?>

    </p>

    <script>

        alert(
            <?= json_encode($alerta) ?>
        );

    </script>

<?php endif; ?>


<form method="POST">


    <p>

        Produto:

        <select
            name="produto_id"
            required
        >

            <option value="">
                Selecione...
            </option>


            <?php foreach ($produtos as $produto): ?>

                <option
                    value="<?= $produto["id"] ?>"
                >

                    <?= htmlspecialchars($produto["codigo"]) ?>
                    -
                    <?= htmlspecialchars($produto["nome"]) ?>
                    - Estoque: <?= $produto["estoque_atual"] ?>

                </option>

            <?php endforeach; ?>

        </select>

    </p>


    <p>

        Tipo:

        <label>

            <input
                type="radio"
                name="tipo"
                value="1"
                required
            >

            Entrada

        </label>


        <label>

            <input
                type="radio"
                name="tipo"
                value="2"
            >

            Saída

        </label>

    </p>


    <p>

        Quantidade:

        <input
            type="number"
            name="quantidade"
            min="1"
            required
        >

    </p>


    <p>

        Data:

        <input
            type="date"
            name="data"
            value="<?= date("Y-m-d") ?>"
            required
        >

    </p>


    <p>

        <button type="submit">
            Registrar Movimentação
        </button>

    </p>


</form>


<hr>


<div class="card">
    <h2>Lista de Produtos</h2>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Aviso</th>
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
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<hr>


<div class="card">
    <h2>Histórico de Movimentações</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Saldo Anterior</th>
                <th>Usuário</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($historico as $mov): ?>
                <tr>
                    <td><?= $mov["id"] ?></td>
                    <td><?= date("d/m/Y", strtotime($mov["data"])) ?></td>
                    <td><?= htmlspecialchars($mov["produto"]) ?></td>
                    <td>
                        <?php if ($mov["tipo"] == 1): ?>
                            Entrada
                        <?php else: ?>
                            Saída
                        <?php endif; ?>
                    </td>
                    <td><?= $mov["quantidade"] ?></td>
                    <td><?= $mov["saldo_anterior"] ?></td>
                    <td><?= htmlspecialchars($mov["usuario"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>

</body>

</html>