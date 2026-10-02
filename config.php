<?php
$host = 'localhost';
$port = '3306';
$dbname = 'industria_db';
$usuario = 'root';
$senha = 'mysql';

try {

    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $senha
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die(
        "Erro ao conectar com o banco: "
        . $e->getMessage()
    );

}


if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


function verificarLogin()
{

    if (!isset($_SESSION["usuario_id"])) {

        header("Location: login.php");

        exit;

    }

}

?>