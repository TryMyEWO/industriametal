<?php

require_once "config.php";

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $senha = trim($_POST["senha"]);

    if ($email == "" || $senha == "") {

        $erro = "Informe o e-mail e a senha.";

    } else {

        $sql = "SELECT * FROM usuario 
                WHERE email = ? 
                AND ativo = 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {

            $erro = "Usuário não encontrado ou está inativo.";

        } else {

            /*
             * Para a atividade, as senhas do banco estão como texto.
             * Se posteriormente usar password_hash(), esta parte também
             * poderá ser adaptada.
             */

            if ($senha === $usuario["senha"]) {

                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nome"] = $usuario["nome"];

                header("Location: index.php");
                exit;

            } else {

                $erro = "Senha incorreta.";

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Login - Controle de Estoque</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="pagina-login">

    <h1>Sistema de Controle de Estoque</h1>

    <hr>

    <h2>Login</h2>

    <?php if ($erro != ""): ?>

        <p class="erro">
            <?= htmlspecialchars($erro) ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <p>
            <label>
                E-mail:
                <input
                    type="email"
                    name="email"
                    required
                >
            </label>
        </p>

        <p>
            <label>
                Senha:
                <input
                    type="password"
                    name="senha"
                    required
                >
            </label>
        </p>

        <button type="submit">
            Entrar
        </button>

    </form>

</div>

</body>

</html>