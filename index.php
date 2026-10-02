<?php

require_once "config.php";

verificarLogin();

if (isset($_GET["sair"])) {

    session_destroy();

    header("Location: login.php");

    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Sistema de Controle de Estoque</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<h1>Sistema de Controle de Estoque Industrial</h1>

<p>
    Usuário Logado:
    <strong>
        <?= htmlspecialchars($_SESSION["usuario_nome"]) ?>
    </strong>
</p>

<hr>

<h2>Menu</h2>

<ul>

    <li>
        <a href="produtos.php">
            Cadastro de Produtos/Insumos
        </a>
    </li>

    <li>
        <a href="estoque.php">
            Gestão de Estoque
        </a>
    </li>

    <li>
        <a href="index.php?sair=1">
            Sair
        </a>
    </li>

</ul>


</body>

</html>